<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/recaptcha-captcha/qa-recaptcha-captcha.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Captcha module for reCAPTCHA


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


	class qa_recaptcha_captcha {
	
		var $directory;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
		}


		function admin_form()
		{
			$saved=false;
			
			if (qa_clicked('recaptcha_save_button')) {
				qa_opt('recaptcha_public_key', qa_post_text('recaptcha_public_key_field'));
				qa_opt('recaptcha_private_key', qa_post_text('recaptcha_private_key_field'));
				
				$saved=true;
			}
			
			$form=array(
				'ok' => $saved ? 'reCAPTCHA settings saved' : null,
				
				'fields' => array(
					'public' => array(
						'label' => 'reCAPTCHA public key:',
						'value' => qa_opt('recaptcha_public_key'),
						'tags' => 'name="recaptcha_public_key_field"',
					),

					'private' => array(
						'label' => 'reCAPTCHA private key:',
						'value' => qa_opt('recaptcha_private_key'),
						'tags' => 'name="recaptcha_private_key_field"',
						'error' => $this->recaptcha_error_html(),
					),
				),

				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'name="recaptcha_save_button"',
					),
				),
			);
			
			return $form;
		}
		

		function recaptcha_error_html()
		{
			if (!function_exists('fsockopen'))
				return 'To use reCAPTCHA, the fsockopen() PHP function must be enabled on your server. Please check with your system administrator.';
			
			elseif ( (!strlen(trim(qa_opt('recaptcha_public_key')))) || (!strlen(trim(qa_opt('recaptcha_private_key')))) ) {
				require_once $this->directory.'recaptchalib.php';
				
				$url=recaptcha_get_signup_url(@$_SERVER['HTTP_HOST'], qa_opt('site_title'));
				
				return 'To use reCAPTCHA, you must <a href="'.qa_html($url).'">sign up</a> to get these keys.';
			}
			
			return null;				
		}
	
	
		function allow_captcha()
		{
			return function_exists('fsockopen') && strlen(trim(qa_opt('recaptcha_public_key'))) && strlen(trim(qa_opt('recaptcha_private_key')));
		}

		
		function form_html(&$qa_content, $error)
		{
			require_once $this->directory.'recaptchalib.php';
			
			$language=qa_opt('site_language');
			if (strpos('|en|nl|fr|de|pt|ru|es|tr|', '|'.$language.'|')===false) // supported as of 3/2010
				$language='en';
				
			$qa_content['script_lines'][]=array(
				"var RecaptchaOptions={",
				"\ttheme:'white',",
				"\tlang:".qa_js($language),
				"};",
			);
		
			return recaptcha_get_html(qa_opt('recaptcha_public_key'), $error, qa_is_https_probably());
		}


		function validate_post(&$error)
		{
			if ( (!empty($_POST['recaptcha_challenge_field'])) && (!empty($_POST['recaptcha_response_field'])) ) {
				require_once $this->directory.'recaptchalib.php';
				
				$answer=recaptcha_check_answer(
					qa_opt('recaptcha_private_key'),
					qa_remote_ip_address(),
					$_POST['recaptcha_challenge_field'],
					$_POST['recaptcha_response_field']
				);
				
				if ($answer->is_valid)
					return true;

				$error=@$answer->error;
			}
			
			return false;
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/