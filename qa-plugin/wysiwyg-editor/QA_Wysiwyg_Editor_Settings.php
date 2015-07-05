<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/basic-adsense/qa-basic-adsense.php
	Description: Widget module class for AdSense widget plugin


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

class QA_Wysiwyg_Editor_Settings extends Q2A_Plugin_Module_Settings
{
	public function getInternalId()
	{
		return 'QA_Wysiwyg_Editor_Settings';
	}

	public function getDefaultValue($option)
	{
		switch ($option) {
			case 'wysiwyg_editor_upload_max_size':
				return 2 * 1024 * 1024;
			default:
				return null;
		}
	}

	public function getForm(&$qa_content)
	{
		require_once QA_INCLUDE_DIR.'app/upload.php';
		$saved = false;
		if (qa_clicked('wysiwyg_editor_save_button')) {
			qa_opt('wysiwyg_editor_upload_images', (int)qa_post_text('wysiwyg_editor_upload_images_field'));
			qa_opt('wysiwyg_editor_upload_all', (int)qa_post_text('wysiwyg_editor_upload_all_field'));
			qa_opt('wysiwyg_editor_upload_max_size', min(qa_get_max_upload_size(), 1048576*(float)qa_post_text('wysiwyg_editor_upload_max_size_field')));
			$saved = true;
		}
		qa_set_display_rules($qa_content, array(
			'wysiwyg_editor_upload_all_display' => 'wysiwyg_editor_upload_images_field',
			'wysiwyg_editor_upload_max_size_display' => 'wysiwyg_editor_upload_images_field',
		));
		// handle AJAX requests to 'wysiwyg-editor-ajax'
		$js = array(
			'function wysiwyg_editor_ajax(totalEdited) {',
			'	$.ajax({',
			'		url: ' . qa_js(qa_path('wysiwyg-editor-ajax')) . ',',
			'		success: function(response) {',
			'			var postsEdited = parseInt(response, 10);',
			'			var $btn = $("#wysiwyg_editor_ajax");',
			'			if (isNaN(postsEdited)) {',
			'				$btn.text("ERROR");',
			'			}',
			'			else if (postsEdited < 5) {',
			'				$btn.text("All posts converted.");',
			'			}',
			'			else {',
			'				totalEdited += postsEdited;',
			'				$btn.text("Updating posts... " + totalEdited)',
			'				window.setTimeout(function() {',
			'					wysiwyg_editor_ajax(totalEdited);',
			'				}, 1000);',
			'			}',
			'		}',
			'	});',
			'}',
			'$("#wysiwyg_editor_ajax").click(function() {',
			'	wysiwyg_editor_ajax(0);',
			'	return false;',
			'});',
		);
		$ajaxHtml = 'Update broken images from old CKeditor Smiley plugin: ' .
			'<button id="wysiwyg_editor_ajax">click here</button> ' .
			'<script>' . implode("\n", $js) . '</script>';
		return array(
			'ok' => $saved ? 'WYSIWYG editor settings saved' : null,
			'fields' => array(
				array(
					'label' => 'Allow images to be uploaded',
					'type' => 'checkbox',
					'value' => (int)qa_opt('wysiwyg_editor_upload_images'),
					'tags' => 'name="wysiwyg_editor_upload_images_field" id="wysiwyg_editor_upload_images_field"',
				),
				array(
					'id' => 'wysiwyg_editor_upload_all_display',
					'label' => 'Allow other content to be uploaded, e.g. Flash, PDF',
					'type' => 'checkbox',
					'value' => (int)qa_opt('wysiwyg_editor_upload_all'),
					'tags' => 'name="wysiwyg_editor_upload_all_field"',
				),
				array(
					'id' => 'wysiwyg_editor_upload_max_size_display',
					'label' => 'Maximum size of uploads:',
					'suffix' => 'MB (max '.$this->bytes_to_mega_html(qa_get_max_upload_size()).')',
					'type' => 'number',
					'value' => $this->bytes_to_mega_html(qa_opt('wysiwyg_editor_upload_max_size')),
					'tags' => 'name="wysiwyg_editor_upload_max_size_field"',
				),
				array(
					'type' => 'custom',
					'html' => $ajaxHtml,
				),
			),
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="wysiwyg_editor_save_button"',
				),
			),
		);
	}

	public function bytes_to_mega_html($bytes)
	{
		return qa_html(number_format($bytes/1048576, 1));
	}
}
