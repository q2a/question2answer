<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/wysiwyg-editor/qa-wysiwyg-editor.php
	Version: See define()s at top of qa-include/qa-base.php
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


	class qa_wysiwyg_editor {
		
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->urltoroot=$urltoroot;
		}

		
		function option_default($option)
		{
			if ($option=='wysiwyg_editor_upload_max_size') {
				require_once QA_INCLUDE_DIR.'qa-app-upload.php';
				
				return min(qa_get_max_upload_size(), 1048576);
			}
		}
	
	
		function bytes_to_mega_html($bytes)
		{
			return qa_html(number_format($bytes/1048576, 1));
		}
	
	
		function admin_form(&$qa_content)
		{
			require_once QA_INCLUDE_DIR.'qa-app-upload.php';
			
			$saved=false;
			
			if (qa_clicked('wysiwyg_editor_save_button')) {
				qa_opt('wysiwyg_editor_upload_images', (int)qa_post_text('wysiwyg_editor_upload_images_field'));
				qa_opt('wysiwyg_editor_upload_all', (int)qa_post_text('wysiwyg_editor_upload_all_field'));
				qa_opt('wysiwyg_editor_upload_max_size', min(qa_get_max_upload_size(), 1048576*(float)qa_post_text('wysiwyg_editor_upload_max_size_field')));
				$saved=true;
			}
			
			qa_set_display_rules($qa_content, array(
				'wysiwyg_editor_upload_all_display' => 'wysiwyg_editor_upload_images_field',
				'wysiwyg_editor_upload_max_size_display' => 'wysiwyg_editor_upload_images_field',
			));

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
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'name="wysiwyg_editor_save_button"',
					),
				),
			);
		}
		
	
		function calc_quality($content, $format)
		{
			if ($format=='html')
				return 1.0;
			elseif ($format=='')
				return 0.8;
			else
				return 0;
		}

		
		function get_field(&$qa_content, $content, $format, $fieldname, $rows /* $autofocus parameter deprecated */)
		{
			$scriptsrc=$this->urltoroot.'ckeditor.js?'.QA_VERSION;			
			$alreadyadded=false;

			if (isset($qa_content['script_src']))
				foreach ($qa_content['script_src'] as $testscriptsrc)
					if ($testscriptsrc==$scriptsrc)
						$alreadyadded=true;
					
			if (!$alreadyadded) {
				$uploadimages=qa_opt('wysiwyg_editor_upload_images');
				$uploadall=$uploadimages && qa_opt('wysiwyg_editor_upload_all');
				
				$qa_content['script_src'][]=$scriptsrc;
				$qa_content['script_lines'][]=array(
					"qa_wysiwyg_editor_config={toolbar:[".
						"['Bold','Italic','Underline','Strike'],".
						"['Font','FontSize'],".
						"['TextColor','BGColor'],".
						"['Link','Unlink'],".
						"'/',".
						"['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],".
						"['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],".
						"['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar'],".
						"['RemoveFormat', 'Maximize']".
					"]".
					", defaultLanguage:".qa_js(qa_opt('site_language')).
					", skin:'v2'".
					", toolbarCanCollapse:false".
					", removePlugins:'elementspath'".
					", resize_enabled:false".
					", autogrow:false".
					", entities:false".
					($uploadimages ? (", filebrowserImageUploadUrl:".qa_js(qa_path('wysiwyg-editor-upload', array('qa_only_image' => true)))) : "").
					($uploadall ? (", filebrowserUploadUrl:".qa_js(qa_path('wysiwyg-editor-upload'))) : "").
					"};"
				);
			}		
				
			if ($format=='html') {
				$html=$content;
				$text=$this->html_to_text($content);
			} else {
				$text=$content;
				$html=qa_html($content, true);
			}
			
			return array(
				'tags' => 'name="'.$fieldname.'"',
				'value' => qa_html($text),
				'rows' => $rows,
				'html_prefix' => '<input name="'.$fieldname.'_ckeditor_ok" id="'.$fieldname.'_ckeditor_ok" type="hidden" value="0"><input name="'.$fieldname.'_ckeditor_data" id="'.$fieldname.'_ckeditor_data" type="hidden" value="'.qa_html($html).'">',
			);
		}
	
	
		function load_script($fieldname)
		{
			return "if (qa_ckeditor_".$fieldname."=CKEDITOR.replace(".qa_js($fieldname).", window.qa_wysiwyg_editor_config)) { qa_ckeditor_".$fieldname.".setData(document.getElementById(".qa_js($fieldname.'_ckeditor_data').").value); document.getElementById(".qa_js($fieldname.'_ckeditor_ok').").value=1; }";
		}

		
		function focus_script($fieldname)
		{
			return "if (qa_ckeditor_".$fieldname.") qa_ckeditor_".$fieldname.".focus();";
		}

		
		function update_script($fieldname)
		{
			return "if (qa_ckeditor_".$fieldname.") qa_ckeditor_".$fieldname.".updateElement();";
		}

		
		function read_post($fieldname)
		{
			if (qa_post_text($fieldname.'_ckeditor_ok')) { // CKEditor was loaded successfully
				$html=qa_post_text($fieldname);
			
				$htmlformatting=preg_replace('/<\s*\/?\s*(br|p)\s*\/?\s*>/i', '', $html); // remove <p>, <br>, etc... since those are OK in text
			
				if (preg_match('/<.+>/', $htmlformatting)) // if still some other tags, it's worth keeping in HTML
					return array(
						'format' => 'html',
						'content' => qa_sanitize_html($html, false, true), // qa_sanitize_html() is ESSENTIAL for security
					);
			
				else { // convert to text
					$viewer=qa_load_module('viewer', '');

					return array(
						'format' => '',
						'content' => $this->html_to_text($html),
					);
				}

			} else // CKEditor was not loaded so treat it as plain text
				return array(
					'format' => '',
					'content' => qa_post_text($fieldname),
				);
		}
		
		function html_to_text($html)
		{
			$viewer=qa_load_module('viewer', '');

			return $viewer->get_text($html, 'html', array());
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/