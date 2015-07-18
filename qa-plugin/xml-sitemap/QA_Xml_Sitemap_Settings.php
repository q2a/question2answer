<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/tag-cloud-widget/qa-tag-cloud.php
	Description: Widget module class for tag cloud plugin


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

class QA_Xml_Sitemap_Settings extends Q2A_Plugin_Module_Settings
{
	public function getInternalId()
	{
		return 'QA_Xml_Sitemap_Settings';
	}

	public function getDefaultValue($option)
	{
		switch ($option) {
			case 'xml_sitemap_show_questions':
			case 'xml_sitemap_show_users':
			case 'xml_sitemap_show_tag_qs':
			case 'xml_sitemap_show_category_qs':
			case 'xml_sitemap_show_categories':
				return true;
			default:
				return null;
		}
	}

	public function getForm(&$qa_content)
	{
		require_once QA_INCLUDE_DIR.'util/sort.php';

		$saved=false;

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

			$saved=true;
		}

		$form=array(
			'ok' => $saved ? 'XML sitemap settings saved' : null,

			'fields' => array(
				'questions' => array(
					'label' => 'Include question pages',
					'type' => 'checkbox',
					'value' => (int)qa_opt('xml_sitemap_show_questions'),
					'tags' => 'name="xml_sitemap_show_questions_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="xml_sitemap_save_button"',
				),
			),
		);

		if (!QA_FINAL_EXTERNAL_USERS)
			$form['fields']['users']=array(
				'label' => 'Include user pages',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_users'),
				'tags' => 'name="xml_sitemap_show_users_field"',
			);

		if (qa_using_tags())
			$form['fields']['tagqs']=array(
				'label' => 'Include question list for each tag',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_tag_qs'),
				'tags' => 'name="xml_sitemap_show_tag_qs_field"',
			);

		if (qa_using_categories()) {
			$form['fields']['categoryqs']=array(
				'label' => 'Include question list for each category',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_category_qs'),
				'tags' => 'name="xml_sitemap_show_category_qs_field"',
			);

			$form['fields']['categories']=array(
				'label' => 'Include category browser',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_categories'),
				'tags' => 'name="xml_sitemap_show_categories_field"',
			);
		}

		return $form;
	}
}
