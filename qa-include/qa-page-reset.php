<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-reset.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for password reset page (comes after forgot page)


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


//	Check we're not using single-sign on integration and that we're not logged in
	
	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User login is handled by external code');
		
	if (qa_is_logged_in())
		qa_redirect('');
		

//	Process incoming form

	if (qa_clicked('doreset')) {
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
	
		$inemailhandle=qa_post_text('emailhandle');
		$incode=trim(qa_post_text('code')); // trim to prevent passing in blank values to match uninitiated DB rows
		
		$errors=array();

		if (!qa_check_form_security_code('reset', qa_post_text('formcode')))
			$errors['page']=qa_lang_html('misc/form_security_again');
		
		else {
			if (qa_opt('allow_login_email_only') || (strpos($inemailhandle, '@')!==false)) // handles can't contain @ symbols
				$matchusers=qa_db_user_find_by_email($inemailhandle);
			else
				$matchusers=qa_db_user_find_by_handle($inemailhandle);
	
			if (count($matchusers)==1) { // if match more than one (should be impossible), consider it a non-match
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	
				$inuserid=$matchusers[0];
				$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($inuserid, true));
				
				// strlen() check is vital otherwise we can reset code for most users by entering the empty string
				if (strlen($incode) && (strtolower(trim($userinfo['emailcode'])) == strtolower($incode))) {
					qa_complete_reset_user($inuserid);
					qa_redirect('login', array('e' => $inemailhandle, 'ps' => '1')); // redirect to login page
		
				} else
					$errors['code']=qa_lang('users/reset_code_wrong');
				
			} else
				$errors['emailhandle']=qa_lang('users/user_not_found');
		}

	} else {
		$inemailhandle=qa_get('e');
		$incode=qa_get('c');
	}
	
	
//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('users/reset_title');
	$qa_content['error']=@$errors['page'];

	if (empty($inemailhandle) || isset($errors['emailhandle']))
		$forgotpath=qa_path('forgot');
	else
		$forgotpath=qa_path('forgot',  array('e' => $inemailhandle));
	
	$qa_content['form']=array(
		'tags' => 'method="post" action="'.qa_self_html().'"',
		
		'style' => 'tall',
		
		'ok' => empty($incode) ? qa_lang_html('users/reset_code_emailed') : null,
		
		'fields' => array(
			'email_handle' => array(
				'label' => qa_opt('allow_login_email_only') ? qa_lang_html('users/email_label') : qa_lang_html('users/email_handle_label'),
				'tags' => 'name="emailhandle" id="emailhandle"',
				'value' => qa_html(@$inemailhandle),
				'error' => qa_html(@$errors['emailhandle']),
			),

			'code' => array(
				'label' => qa_lang_html('users/reset_code_label'),
				'tags' => 'name="code" id="code"',
				'value' => qa_html(@$incode),
				'error' => qa_html(@$errors['code']),
				'note' => qa_lang_html('users/reset_code_emailed').' - '.
					'<a href="'.qa_html($forgotpath).'">'.qa_lang_html('users/reset_code_another').'</a>',
			),
		),
		
		'buttons' => array(
			'reset' => array(
				'label' => qa_lang_html('users/send_password_button'),
			),
		),
		
		'hidden' => array(
			'doreset' => '1',
			'formcode' => qa_get_form_security_code('reset'),
		),
	);
	
	$qa_content['focusid']=(isset($errors['emailhandle']) || !strlen(@$inemailhandle)) ? 'emailhandle' : 'code';

	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/