<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-register.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for register page


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

	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	require_once QA_INCLUDE_DIR.'qa-db-users.php';


//	Check we're not using single-sign on integration, that we're not logged in, and we're not blocked

	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User registration is handled by external code');
		
	if (qa_is_logged_in())
		qa_redirect('');


//	Get information about possible additional fields

	$userfields=qa_db_select_with_pending(
		qa_db_userfields_selectspec()
	);
	
	foreach ($userfields as $index => $userfield)
		if (!($userfield['flags'] & QA_FIELD_FLAGS_ON_REGISTER))
			unset($userfields[$index]);


//	Check we haven't suspended registration, and this IP isn't blocked
	
	if (qa_opt('suspend_register_users')) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/register_suspended');
		return $qa_content;
	}
	
	if (qa_user_permit_error()) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}

	
//	Process submitted form

	if (qa_clicked('doregister')) {
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		if (qa_user_limits_remaining(QA_LIMIT_REGISTRATIONS)) {
			require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
			
			$inemail=qa_post_text('email');
			$inpassword=qa_post_text('password');
			$inhandle=qa_post_text('handle');
			
			$inprofile=array();
			foreach ($userfields as $userfield)
				$inprofile[$userfield['fieldid']]=qa_post_text('field_'.$userfield['fieldid']);		
			
			if (!qa_check_form_security_code('register', qa_post_text('code')))
				$pageerror=qa_lang_html('misc/form_security_again');
				
			else {
				$errors=array_merge(
					qa_handle_email_filter($inhandle, $inemail),
					qa_password_validate($inpassword)
				);
				
				if (count($inprofile)) {
					$filtermodules=qa_load_modules_with('filter', 'filter_profile');
					foreach ($filtermodules as $filtermodule)
						$filtermodule->filter_profile($inprofile, $errors, null, null);
				}
				
				if (qa_opt('captcha_on_register'))
					qa_captcha_validate_post($errors);
			
				if (empty($errors)) { // register and redirect
					qa_limits_increment(null, QA_LIMIT_REGISTRATIONS);
	
					$userid=qa_create_new_user($inemail, $inpassword, $inhandle);
					
					foreach ($userfields as $userfield)
						qa_db_user_profile_set($userid, $userfield['title'], $inprofile[$userfield['fieldid']]);
					
					qa_set_logged_in_user($userid, $inhandle);
		
					$topath=qa_get('to');
					
					if (isset($topath))
						qa_redirect_raw(qa_path_to_root().$topath); // path already provided as URL fragment
					else
						qa_redirect('');
				}
			}
			
		} else
			$pageerror=qa_lang('users/register_limit');
	}


//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('users/register_title');
	
	$qa_content['error']=@$pageerror;

	$custom=qa_opt('show_custom_register') ? trim(qa_opt('custom_register')) : '';
	
	$qa_content['form']=array(
		'tags' => 'method="post" action="'.qa_self_html().'"',
		
		'style' => 'tall',
		
		'fields' => array(
			'custom' => array(
				'type' => 'custom',
				'note' => $custom,
			),
			
			'handle' => array(
				'label' => qa_lang_html('users/handle_label'),
				'tags' => 'name="handle" id="handle"',
				'value' => qa_html(@$inhandle),
				'error' => qa_html(@$errors['handle']),
			),
			
			'password' => array(
				'type' => 'password',
				'label' => qa_lang_html('users/password_label'),
				'tags' => 'name="password" id="password"',
				'value' => qa_html(@$inpassword),
				'error' => qa_html(@$errors['password']),
			),

			'email' => array(
				'label' => qa_lang_html('users/email_label'),
				'tags' => 'name="email" id="email"',
				'value' => qa_html(@$inemail),
				'note' => qa_opt('email_privacy'),
				'error' => qa_html(@$errors['email']),
			),
		),
		
		'buttons' => array(
			'register' => array(
				'tags' => 'onclick="qa_show_waiting_after(this, false);"',
				'label' => qa_lang_html('users/register_button'),
			),
		),
		
		'hidden' => array(
			'doregister' => '1',
			'code' => qa_get_form_security_code('register'),
		),
	);
	
	if (!strlen($custom))
		unset($qa_content['form']['fields']['custom']);
	
	foreach ($userfields as $userfield) {
		$value=@$inprofile[$userfield['fieldid']];	
		
		$label=trim(qa_user_userfield_label($userfield), ':');
		if (strlen($label))
			$label.=':';
			
		$qa_content['form']['fields'][$userfield['title']]=array(
			'label' => qa_html($label),
			'tags' => 'name="field_'.$userfield['fieldid'].'"',
			'value' => qa_html($value),
			'error' => qa_html(@$errors[$userfield['fieldid']]),
			'rows' => ($userfield['flags'] & QA_FIELD_FLAGS_MULTI_LINE) ? 8 : null,
		);
	}
	
	if (qa_opt('captcha_on_register'))
		qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors);
	
	$loginmodules=qa_load_modules_with('login', 'login_html');
	
	foreach ($loginmodules as $module) {
		ob_start();
		$module->login_html(qa_opt('site_url').qa_get('to'), 'register');
		$html=ob_get_clean();
		
		if (strlen($html))
			@$qa_content['custom'].='<br>'.$html.'<br>';
	}

	$qa_content['focusid']=isset($errors['handle']) ? 'handle'
		: (isset($errors['password']) ? 'password'
			: (isset($errors['email']) ? 'email' : 'handle'));

			
	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/