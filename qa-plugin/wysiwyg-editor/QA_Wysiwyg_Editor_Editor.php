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

class QA_Wysiwyg_Editor_Editor extends Q2A_Plugin_Module_Editor
{
	public function getInternalId()
	{
		return 'QA_Wysiwyg_Editor_Editor';
	}

	public function getDisplayName() {
		return 'QA Wysiwyg Editor';
	}

	public function option_default($option)
	{
		if ($option == 'wysiwyg_editor_upload_max_size') {
			require_once QA_INCLUDE_DIR.'app/upload.php';

			return min(qa_get_max_upload_size(), 1048576);
		}
	}

	public function calculateQuality($content, $format)
	{
		if ($format == 'html')
			return 1.0;
		elseif ($format == '')
			return 0.8;
		else
			return 0;
	}

	public function getField(&$qa_content, $content, $format, $fieldname, $rows)
	{
		$scriptsrc = $this->getPluginUrlToRoot() . 'ckeditor/ckeditor.js?' . QA_VERSION;
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

	public function getLoadScript($fieldName)
	{
		return
			"if (qa_ckeditor_".$fieldName." = CKEDITOR.replace(".qa_js($fieldName).", qa_wysiwyg_editor_config)) { " .
				"qa_ckeditor_".$fieldName.".setData(document.getElementById(".qa_js($fieldName.'_ckeditor_data').").value); " .
				"document.getElementById(".qa_js($fieldName.'_ckeditor_ok').").value = 1; " .
			"}";
	}

	public function getFocusScript($fieldName)
	{
		return sprintf("if (qa_ckeditor_%s) qa_ckeditor_%s.focus();", $fieldName, $fieldName);
	}

	public function getUpdateScript($fieldName)
	{
		return sprintf("if (qa_ckeditor_%s) qa_ckeditor_%s.updateElement();", $fieldName, $fieldName);
	}

	public function readPost($fieldName)
	{
		if (qa_post_text($fieldName.'_ckeditor_ok')) {
			// CKEditor was loaded successfully
			$html = qa_post_text($fieldName);

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
				return array(
					'format' => '',
					'content' => $this->html_to_text($html),
				);
			}
		} else {
			// CKEditor was not loaded so treat it as plain text
			return array(
				'format' => '',
				'content' => qa_post_text($fieldName),
			);
		}
	}

	private function html_to_text($html)
	{
		global $pluginManager;

		$moduleId = Q2A_Plugin_BasePlugin::getModuleId('Q2A_System_Plugin', 'Q2A_Viewer_Basic');
		$viewerModule = $pluginManager->getModuleById($moduleId);
		return $viewerModule->getText($html, 'html', array());
	}
}
