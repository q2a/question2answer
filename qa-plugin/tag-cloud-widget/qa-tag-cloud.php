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

class qa_tag_cloud
{
	public function option_default($option)
	{
		if ($option === 'tag_cloud_count_tags')
			return 100;
		if ($option === 'tag_cloud_font_size')
			return 24;
		if ($option === 'tag_cloud_minimal_font_size')
			return 8;
		if ($option === 'tag_cloud_size_popular')
			return true;
	}


	public function admin_form()
	{
		$saved = qa_clicked('tag_cloud_save_button');

		if ($saved) {
			qa_opt('tag_cloud_count_tags', (int) qa_post_text('tag_cloud_count_tags_field'));
			qa_opt('tag_cloud_font_size', (int) qa_post_text('tag_cloud_font_size_field'));
			qa_opt('tag_cloud_minimal_font_size', (int) qa_post_text('tag_cloud_minimal_font_size_field'));
			qa_opt('tag_cloud_size_popular', (int) qa_post_text('tag_cloud_size_popular_field'));
		}

		return array(
			'ok' => $saved ? 'Tag cloud settings saved' : null,

			'fields' => array(
				array(
					'label' => 'Maximum tags to show:',
					'type' => 'number',
					'value' => (int) qa_opt('tag_cloud_count_tags'),
					'suffix' => 'tags',
					'tags' => 'name="tag_cloud_count_tags_field"',
				),

				array(
					'label' => 'Biggest font size:',
					'suffix' => 'pixels',
					'type' => 'number',
					'value' => (int) qa_opt('tag_cloud_font_size'),
					'tags' => 'name="tag_cloud_font_size_field"',
				),

				array(
					'label' => 'Smallest allowed font size:',
					'suffix' => 'pixels',
					'type' => 'number',
					'value' => (int) qa_opt('tag_cloud_minimal_font_size'),
					'tags' => 'name="tag_cloud_minimal_font_size_field"',
				),

				array(
					'label' => 'Font size represents tag popularity',
					'type' => 'checkbox',
					'value' => qa_opt('tag_cloud_size_popular'),
					'tags' => 'name="tag_cloud_size_popular_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="tag_cloud_save_button"',
				),
			),
		);
	}


	public function allow_template($template)
	{
		$allowed = array(
			'activity', 'qa', 'questions', 'hot', 'ask', 'categories', 'question',
			'tag', 'tags', 'unanswered', 'user', 'users', 'search', 'admin', 'custom',
		);
		return in_array($template, $allowed);
	}


	public function allow_region($region)
	{
		return ($region === 'side');
	}


	public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		require_once QA_INCLUDE_DIR.'db/selects.php';

		$populartags = qa_db_single_select(qa_db_popular_tags_selectspec(0, (int) qa_opt('tag_cloud_count_tags')));

		$maxcount = reset($populartags);

		$themeobject->output(sprintf('<h2 style="margin-top: 0; padding-top: 0;">%s</h2>', qa_lang_html('main/popular_tags')));

		$themeobject->output('<div style="font-size: 10px;">');

		$maxsize = qa_opt('tag_cloud_font_size');
		$minsize = qa_opt('tag_cloud_minimal_font_size');
		$scale = qa_opt('tag_cloud_size_popular');
		$blockwordspreg = qa_get_block_words_preg();

		foreach ($populartags as $tag => $count) {
			$matches = qa_block_words_match_all($tag, $blockwordspreg);
			if (empty($matches)) {
				if ($scale) {
					$size = number_format($maxsize * $count / $maxcount, 1);
					if ($size < $minsize)
						$size = $minsize;
				} else
					$size = $maxsize;

				$themeobject->output(sprintf('<a href="%s" style="font-size: %dpx; vertical-align: baseline;">%s</a>', qa_path_html('tag/' . $tag), $size, qa_html($tag)));
			}
		}

		$themeobject->output('</div>');
	}
}
