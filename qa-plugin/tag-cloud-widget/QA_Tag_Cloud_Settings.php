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

class QA_Tag_Cloud_Settings extends Q2A_Plugin_Module_Settings
{
	public function getInternalId()
	{
		return 'QA_Tag_Cloud_Settings';
	}

	public function getDefaultValue($option)
	{
		switch ($option) {
			case 'tag_cloud_count_tags':
				return 100;
			case 'tag_cloud_font_size':
				return 24;
			case 'tag_cloud_minimal_font_size':
				return 8;
			case 'tag_cloud_size_popular':
				return true;
			default:
				return null;
		}
	}

	public function getForm(&$qa_content)
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
}
