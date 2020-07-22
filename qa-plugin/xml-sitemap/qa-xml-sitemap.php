<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/xml-sitemap/qa-xml-sitemap.php
	Description: Page module class for XML sitemap plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_xml_sitemap
{
	public function option_default($option)
	{
		switch ($option) {
			case 'xml_sitemap_show_questions':
			case 'xml_sitemap_show_users':
			case 'xml_sitemap_show_tag_qs':
			case 'xml_sitemap_show_category_qs':
			case 'xml_sitemap_show_categories':
				return true;
		}
	}


	public function admin_form()
	{
		require_once QA_INCLUDE_DIR . 'util/sort.php';

		$saved = false;

		if (qa_clicked('xml_sitemap_save_button')) {
			qa_opt('xml_sitemap_show_questions', (int)qa_post_text('xml_sitemap_show_questions_field'));

			if (!QA_FINAL_EXTERNAL_USERS)
				qa_opt('xml_sitemap_show_users', (int)qa_post_text('xml_sitemap_show_users_field'));

			if (qa_using_tags())
				qa_opt('xml_sitemap_show_tag_qs', (int)qa_post_text('xml_sitemap_show_tag_qs_field'));

			if (qa_using_categories()) {
				qa_opt('xml_sitemap_show_category_qs', (int)qa_post_text('xml_sitemap_show_category_qs_field'));
				qa_opt('xml_sitemap_show_categories', (int)qa_post_text('xml_sitemap_show_categories_field'));
			}

			$saved = true;
		}

		$form = array(
			'ok' => $saved ? qa_lang_html('admin/options_saved') : null,

			'fields' => array(
				'questions' => array(
					'label' => qa_lang_html('xml_sitemap/include_q_pages'),
					'type' => 'checkbox',
					'value' => (int)qa_opt('xml_sitemap_show_questions'),
					'tags' => 'name="xml_sitemap_show_questions_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => qa_lang_html('main/save_button'),
					'tags' => 'name="xml_sitemap_save_button"',
				),
			),
		);

		if (!QA_FINAL_EXTERNAL_USERS) {
			$form['fields']['users'] = array(
				'label' => qa_lang_html('xml_sitemap/include_user_pages'),
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_users'),
				'tags' => 'name="xml_sitemap_show_users_field"',
			);
		}

		if (qa_using_tags()) {
			$form['fields']['tagqs'] = array(
				'label' => qa_lang_html('xml_sitemap/include_ql_tag'),
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_tag_qs'),
				'tags' => 'name="xml_sitemap_show_tag_qs_field"',
			);
		}

		if (qa_using_categories()) {
			$form['fields']['categoryqs'] = array(
				'label' => qa_lang_html('xml_sitemap/include_ql_category'),
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_category_qs'),
				'tags' => 'name="xml_sitemap_show_category_qs_field"',
			);

			$form['fields']['categories'] = array(
				'label' => qa_lang_html('xml_sitemap/include_cat_browser'),
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_categories'),
				'tags' => 'name="xml_sitemap_show_categories_field"',
			);
		}

		return $form;
	}


	public function suggest_requests()
	{
		return array(
			array(
				'title' => qa_lang_html('xml_sitemap/xml_sitemap'),
				'request' => 'sitemap.xml',
				'nav' => null, // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}


	public function match_request($request)
	{
		return ($request == 'sitemap.xml');
	}


	public function process_request($request)
	{
		@ini_set('display_errors', 0); // we don't want to show PHP errors inside XML
		$db = qa_service('database');

		header('Content-type: text/xml; charset=utf-8');

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";


		// Question pages

		if (qa_opt('xml_sitemap_show_questions')) {
			$hotstats = $db->query(
				"SELECT MIN(hotness) AS base, MAX(hotness)-MIN(hotness) AS spread FROM ^posts WHERE type='Q'"
			)->fetchNextAssocOrFail();

			$nextpostid = 0;

			while (1) {
				$questions = $db->query(
					"SELECT postid, title, hotness FROM ^posts WHERE postid>=? AND type='Q' ORDER BY postid LIMIT 100",
					[$nextpostid]
				)->fetchAllAssoc();

				if (!count($questions))
					break;

				foreach ($questions as $question) {
					$this->sitemap_output(qa_q_request($question['postid'], $question['title']),
						0.1 + 0.9 * ($question['hotness'] - $hotstats['base']) / (1 + $hotstats['spread']));
					$nextpostid = max($nextpostid, $question['postid'] + 1);
				}
			}
		}


		// User pages

		if (!QA_FINAL_EXTERNAL_USERS && qa_opt('xml_sitemap_show_users')) {
			$nextuserid = 0;

			while (1) {
				$users = $db->query(
					"SELECT userid, handle FROM ^users WHERE userid>=? ORDER BY userid LIMIT 100",
					[$nextuserid]
				)->fetchAllAssoc();

				if (!count($users))
					break;

				foreach ($users as $user) {
					$this->sitemap_output('user/' . $user['handle'], 0.25);
					$nextuserid = max($nextuserid, $user['userid'] + 1);
				}
			}
		}


		// Tag pages

		if (qa_using_tags() && qa_opt('xml_sitemap_show_tag_qs')) {
			$nextwordid = 0;

			while (1) {
				$tagwords = $db->query(
					"SELECT wordid, word, tagcount FROM ^words WHERE wordid>=? AND tagcount>0 ORDER BY wordid LIMIT 100",
					[$nextwordid]
				)->fetchAllAssoc();

				if (!count($tagwords))
					break;

				foreach ($tagwords as $tagword) {
					$this->sitemap_output('tag/' . $tagword['word'], 0.5 / (1 + (1 / $tagword['tagcount']))); // priority between 0.25 and 0.5 depending on tag frequency
					$nextwordid = max($nextwordid, $tagword['wordid'] + 1);
				}
			}
		}


		// Question list for each category

		if (qa_using_categories() && qa_opt('xml_sitemap_show_category_qs')) {
			$nextcategoryid = 0;

			while (1) {
				$categories = $db->query(
					"SELECT categoryid, backpath FROM ^categories WHERE categoryid>=? AND qcount>0 ORDER BY categoryid LIMIT 2",
					[$nextcategoryid]
				)->fetchAllAssoc();

				if (!count($categories))
					break;

				foreach ($categories as $category) {
					$this->sitemap_output('questions/' . implode('/', array_reverse(explode('/', $category['backpath']))), 0.5);
					$nextcategoryid = max($nextcategoryid, $category['categoryid'] + 1);
				}
			}
		}


		// Pages in category browser

		if (qa_using_categories() && qa_opt('xml_sitemap_show_categories')) {
			$this->sitemap_output('categories', 0.5);

			$nextcategoryid = 0;

			while (1) { // only find categories with a child
				$categories = $db->query(
					"SELECT parent.categoryid, parent.backpath FROM ^categories AS parent " .
					"JOIN ^categories AS child ON child.parentid=parent.categoryid WHERE parent.categoryid>=# GROUP BY parent.categoryid LIMIT 100",
					[$nextcategoryid]
				)->fetchAllAssoc();

				if (!count($categories))
					break;

				foreach ($categories as $category) {
					$this->sitemap_output('categories/' . implode('/', array_reverse(explode('/', $category['backpath']))), 0.5);
					$nextcategoryid = max($nextcategoryid, $category['categoryid'] + 1);
				}
			}
		}

		echo "</urlset>\n";

		return null;
	}


	private function sitemap_output($request, $priority)
	{
		echo "\t<url>\n" .
			"\t\t<loc>" . qa_xml(qa_path($request, null, qa_opt('site_url'))) . "</loc>\n" .
			"\t\t<priority>" . max(0, min(1.0, $priority)) . "</priority>\n" .
			"\t</url>\n";
	}
}
