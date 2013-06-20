<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-confirm.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for email confirmation page (can also request a new code)


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


//	Check we're not using single-sign on integration, that we're not already confirmed, and that we're not blocked
	
	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User login is handled by external code');


//	Check if we've been asked to send a new link or have a successful email confirmation

	$incode=trim(qa_get('c')); // trim to prevent passing in blank values to match uninitiated DB rows
	$inhandle=qa_get('u');
	$loginuserid=qa_get_logged_in_userid();
	$useremailed=false;
	$userconfirmed=false;
	
	if (isset($loginuserid) && qa_clicked('dosendconfirm')) { // button clicked to send a link
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		if (!qa_check_form_security_code('confirm', qa_post_text('code')))
			$pageerror=qa_lang_html('misc/form_security_again');
		
		else {
			qa_send_new_confirm($loginuserid);
			$useremailed=true;
		}
	
	} elseif (strlen($incode)) { // non-empty code detected from the URL
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
	
		if (!empty($inhandle)) { // match based on code and handle provided on URL
			$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($inhandle, false));
	
			if (strtolower(trim(@$userinfo['emailcode']))==strtolower($incode)) {
				qa_complete_confirm($userinfo['userid'], $userinfo['email'], $userinfo['handle']);
				$userconfirmed=true;
			}
		}
		
		if ((!$userconfirmed) && isset($loginuserid)) { // as a backup, also match code on URL against logged in user
			$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($loginuserid, true));
			$flags=$userinfo['flags'];
			
			if ( ($flags & QA_USER_FLAGS_EMAIL_CONFIRMED) && !($flags & QA_USER_FLAGS_MUST_CONFIRM) )
				$userconfirmed=true; // if they confirmed before, just show message as if it happened now
			
			elseif (strtolower(trim($userinfo['emailcode']))==strtolower($incode)) {
				qa_complete_confirm($userinfo['userid'], $userinfo['email'], $userinfo['handle']);
				$userconfirmed=true;
			}
		}
	}


//	Prepare content for theme
	
	$qa_content=qa_content_prepare();
	
	$qa_content['title']=qa_lang_html('users/confirm_title');
	$qa_content['error']=@$pageerror;

	if ($useremailed)
		$qa_content['error']=qa_lang_html('users/confirm_emailed'); // not an error, but display it prominently anyway
	
	elseif ($userconfirmed) {
		$qa_content['error']=qa_lang_html('users/confirm_complete');
		
		if (!isset($loginuserid))
			$qa_content['suggest_next']=strtr(
				qa_lang_html('users/log_in_to_access'),
				
				array(
					'^1' => '<a href="'.qa_path_html('login', array('e' => $inhandle)).'">',
					'^2' => '</a>',
				)
			);

	} elseif (isset($loginuserid)) { // if logged in, allow sending a fresh link
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		if (strlen($incode))
			$qa_content['error']=qa_lang_html('users/confirm_wrong_resend');
			
		$email=qa_get_logged_in_email();
		
		$qa_content['form']=array(
			'tags' => 'method="post" action="'.qa_path_html('confirm').'"',
			
			'style' => 'tall',
			
			'fields' => array(
				'email' => array(
					'label' => qa_lang_html('users/email_label'),
					'value' => qa_html($email).strtr(qa_lang_html('users/change_email_link'), array(
						'^1' => '<a href="'.qa_path_html('account').'">',
						'^2' => '</a>',
					)),
					'type' => 'static',
				),
			),
			
			'buttons' => array(
				'send' => array(
					'tags' => 'name="dosendconfirm"',
					'label' => qa_lang_html('users/send_confirm_button'),
				),
			),

			'hidden' => array(
				'code' => qa_get_form_security_code('confirm'),
			),
		);
		
		if (!qa_email_validate($email)) {
			$qa_content['error']=qa_lang_html('users/email_invalid');
			unset($qa_content['form']['buttons']['send']);
		}

	} else
		$qa_content['error']=qa_insert_login_links(qa_lang_html('users/confirm_wrong_log_in'), 'confirm');

		
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/