<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-captcha.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Wrapper functions and utilities for captcha modules


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	function qa_captcha_available()
/*
	Return whether a captcha module has been selected and it indicates that it is fully set up to go
*/
	{
		$module=qa_load_module('captcha', qa_opt('captcha_module'));
		
		return isset($module) && ( (!method_exists($module, 'allow_captcha')) || $module->allow_captcha());
	}
	
	
	function qa_captcha_reason_note($captchareason)
/*
	Return an HTML string explaining $captchareason (from qa_user_captcha_reason()) to the user about why they are seeing a captcha
*/
	{
		$notehtml=null;
		
		switch ($captchareason) {
			case 'login':
				$notehtml=qa_insert_login_links(qa_lang_html('misc/captcha_login_fix'));
				break;
				
			case 'confirm':
				$notehtml=qa_insert_login_links(qa_lang_html('misc/captcha_confirm_fix'));
				break;
				
			case 'approve':
				$notehtml=qa_lang_html('misc/captcha_approve_fix');
				break;		
		}
		
		return $notehtml;
	}


	function qa_set_up_captcha_field(&$qa_content, &$fields, $errors, $note=null)
/*
	Prepare $qa_content for showing a captcha, adding the element to $fields, given previous $errors, and a $note to display
*/
	{
		if (qa_captcha_available()) {
			$captcha=qa_load_module('captcha', qa_opt('captcha_module'));
			
			$count=@++$qa_content['qa_captcha_count']; // work around fact that reCAPTCHA can only display per page
			
			if ($count>1)
				$html='[captcha placeholder]'; // single captcha will be moved about the page, to replace this
			else {
				$qa_content['script_var']['qa_captcha_in']='qa_captcha_div_1';
				$html=$captcha->form_html($qa_content, @$errors['captcha']);
			}
			
			$fields['captcha']=array(
				'type' => 'custom',
				'label' => qa_lang_html('misc/captcha_label'),
				'html' => '<div id="qa_captcha_div_'.$count.'">'.$html.'</div>',
				'error' => @array_key_exists('captcha', $errors) ? qa_lang_html('misc/captcha_error') : null,
				'note' => $note,
			);
					
			return "if (qa_captcha_in!='qa_captcha_div_".$count."') { document.getElementById('qa_captcha_div_".$count."').innerHTML=document.getElementById(qa_captcha_in).innerHTML; document.getElementById(qa_captcha_in).innerHTML=''; qa_captcha_in='qa_captcha_div_".$count."'; }";
		}
		
		return '';
	}


	function qa_captcha_validate_post(&$errors)
/*
	Check if captcha is submitted correctly, and if not, set $errors['captcha'] to a descriptive string
*/
	{
		if (qa_captcha_available()) {
			$captcha=qa_load_module('captcha', qa_opt('captcha_module'));
			
			if (!$captcha->validate_post($error)) {
				$errors['captcha']=$error;
				return false;
			}
		}
		
		return true;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/