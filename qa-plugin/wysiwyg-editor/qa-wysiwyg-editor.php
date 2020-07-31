<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/wysiwyg-editor/qa-wysiwyg-editor.php
	Description: Editor module class for WYSIWYG editor plugin


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


class qa_wysiwyg_editor
{
	private $urltoroot;

	public function load_module($directory, $urltoroot)
	{
		$this->urltoroot = $urltoroot;
	}

	public function option_default($option)
	{
		if ($option == 'wysiwyg_editor_upload_max_size') {
			require_once QA_INCLUDE_DIR.'app/upload.php';

			return min(qa_get_max_upload_size(), 1048576);
		}
	}

	public function admin_form(&$qa_content)
	{
		require_once QA_INCLUDE_DIR.'app/upload.php';

		$saved = false;

		if (qa_clicked('wysiwyg_editor_save_button')) {
			qa_opt('wysiwyg_editor_upload_images', (int)qa_post_text('wysiwyg_editor_upload_images_field'));
			qa_opt('wysiwyg_editor_upload_all', (int)qa_post_text('wysiwyg_editor_upload_all_field'));
			qa_opt('wysiwyg_editor_upload_max_size', min(qa_get_max_upload_size(), 1048576 * (float)qa_post_text('wysiwyg_editor_upload_max_size_field')));
			$saved = true;
		}

		qa_set_display_rules($qa_content, array(
			'wysiwyg_editor_upload_all_display' => 'wysiwyg_editor_upload_images_field',
			'wysiwyg_editor_upload_max_size_display' => 'wysiwyg_editor_upload_images_field',
		));

		return array(
			'ok' => $saved ? qa_lang_html('admin/options_saved') : null,

			'fields' => array(
				array(
					'label' => qa_lang_html('wysiwyg/allow_images'),
					'type' => 'checkbox',
					'value' => (int)qa_opt('wysiwyg_editor_upload_images'),
					'tags' => 'name="wysiwyg_editor_upload_images_field" id="wysiwyg_editor_upload_images_field"',
				),

				array(
					'id' => 'wysiwyg_editor_upload_all_display',
					'label' => qa_lang_html('wysiwyg/allow_other_content'),
					'type' => 'checkbox',
					'value' => (int)qa_opt('wysiwyg_editor_upload_all'),
					'tags' => 'name="wysiwyg_editor_upload_all_field"',
				),

				array(
					'id' => 'wysiwyg_editor_upload_max_size_display',
					'label' => qa_lang_html('wysiwyg/maximum_size'),
					'suffix' => qa_lang_html_sub('wysiwyg/mb_max_x', qa_html(number_format($this->bytes_to_mega(qa_get_max_upload_size()), 1))),
					'type' => 'number',
					'value' => qa_html(number_format($this->bytes_to_mega(qa_opt('wysiwyg_editor_upload_max_size')), 1)),
					'tags' => 'name="wysiwyg_editor_upload_max_size_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => qa_lang_html('main/save_button'),
					'tags' => 'name="wysiwyg_editor_save_button"',
				),
			),
		);
	}

	public function calc_quality($content, $format)
	{
		if ($format == 'html')
			return 1.0;
		elseif ($format == '')
			return 0.8;
		else
			return 0;
	}

	public function get_field(&$qa_content, $content, $format, $fieldname, $rows)
	{
		$scriptsrc = $this->urltoroot.'ckeditor/ckeditor.js?'.QA_VERSION;
		$alreadyadded = false;

		if (isset($qa_content['script_src'])) {
			foreach ($qa_content['script_src'] as $testscriptsrc) {
				if ($testscriptsrc == $scriptsrc)
					$alreadyadded = true;
			}
		}

		if (!$alreadyadded) {
			$uploadimages = qa_opt('wysiwyg_editor_upload_images');
			$uploadall = $uploadimages && qa_opt('wysiwyg_editor_upload_all');
			$imageUploadUrl = qa_js( qa_path('wysiwyg-editor-upload', array('qa_only_image' => true)) );
			$fileUploadUrl = qa_js( qa_path('wysiwyg-editor-upload') );

			$qa_content['script_src'][] = $scriptsrc;
			$qa_content['script_lines'][] = array(
				// Most CKeditor config occurs in ckeditor/config.js
				"var qa_wysiwyg_editor_config = {",

				// File uploads
				($uploadimages ? "	filebrowserImageUploadUrl: $imageUploadUrl," : ""),
				($uploadall ? "	filebrowserUploadUrl: $fileUploadUrl," : ""),
				"	filebrowserUploadMethod: 'form',", // Use form upload instead of XHR

				// Set language to Q2A site language, falling back to English if not available.
				"	defaultLanguage: 'en',",
				"	language: " . qa_js(qa_opt('site_language')) . "",

				"};",
			);
		}

		if ($format == 'html') {
			$html = $content;
			$text = $this->html_to_text($content);
		}
		else {
			$text = $content;
			$html = qa_html($content, true);
		}

		return array(
			'tags' => 'name="'.$fieldname.'"',
			'value' => qa_html($text),
			'rows' => $rows,
			'html_prefix' => '<input name="'.$fieldname.'_ckeditor_ok" id="'.$fieldname.'_ckeditor_ok" type="hidden" value="0"><input name="'.$fieldname.'_ckeditor_data" id="'.$fieldname.'_ckeditor_data" type="hidden" value="'.qa_html($html).'">',
		);
	}

	public function load_script($fieldname)
	{
		return
			"if (qa_ckeditor_".$fieldname." = CKEDITOR.replace(".qa_js($fieldname).", qa_wysiwyg_editor_config)) { " .
				"qa_ckeditor_".$fieldname.".setData(document.getElementById(".qa_js($fieldname.'_ckeditor_data').").value); " .
				"document.getElementById(".qa_js($fieldname.'_ckeditor_ok').").value = 1; " .
			"}";
	}

	public function focus_script($fieldname)
	{
		return "if (qa_ckeditor_".$fieldname.") qa_ckeditor_".$fieldname.".focus();";
	}

	public function update_script($fieldname)
	{
		return "if (qa_ckeditor_".$fieldname.") qa_ckeditor_".$fieldname.".updateElement();";
	}

	public function read_post($fieldname)
	{
		if (qa_post_text($fieldname.'_ckeditor_ok')) {
			// CKEditor was loaded successfully
			$html = qa_post_text($fieldname);

			// remove <p>, <br>, etc... since those are OK in text
			$htmlformatting = preg_replace('/<\s*\/?\s*(br|p)\s*\/?\s*>/i', '', $html);

			if (preg_match('/<.+>/', $htmlformatting)) {
				// if still some other tags, it's worth keeping in HTML
				// qa_sanitize_html() is ESSENTIAL for security
				return array(
					'format' => 'html',
					'content' => qa_sanitize_html($html, false, true),
				);
			}
			else {
				// convert to text
				qa_load_module('viewer', '');

				return array(
					'format' => '',
					'content' => $this->html_to_text($html),
				);
			}
		} else {
			// CKEditor was not loaded so treat it as plain text
			return array(
				'format' => '',
				'content' => qa_post_text($fieldname),
			);
		}
	}


	private function html_to_text($html)
	{
		$viewer = qa_load_module('viewer', '');
		return $viewer->get_text($html, 'html', array());
	}

	private function bytes_to_mega($bytes)
	{
		return $bytes / 1048576;
	}
}
