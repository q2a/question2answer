<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-feedback.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for feedback page


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
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';


//	Get useful information on the logged in user

	$userid=qa_get_logged_in_userid();

	if (isset($userid) && !QA_FINAL_EXTERNAL_USERS)
		list($useraccount, $userprofile)=qa_db_select_with_pending(
			qa_db_user_account_selectspec($userid, true),
			qa_db_user_profile_selectspec($userid, true)
		);

	$usecaptcha=qa_opt('captcha_on_feedback') && qa_user_use_captcha();


//	Check feedback is enabled and the person isn't blocked

	if (!qa_opt('feedback_enabled'))
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';

	if (qa_user_permit_error()) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}


//	Send the feedback form
	
	$feedbacksent=false;
	
	if (qa_clicked('dofeedback')) {
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$inmessage=qa_post_text('message');
		$inname=qa_post_text('name');
		$inemail=qa_post_text('email');
		$inreferer=qa_post_text('referer');
		
		if (!qa_check_form_security_code('feedback', qa_post_text('code')))
			$pageerror=qa_lang_html('misc/form_security_again');
		
		else {
			if (empty($inmessage))
				$errors['message']=qa_lang('misc/feedback_empty');
			
			if ($usecaptcha)
				qa_captcha_validate_post($errors);
	
			if (empty($errors)) {
				$subs=array(
					'^message' => $inmessage,
					'^name' => empty($inname) ? '-' : $inname,
					'^email' => empty($inemail) ? '-' : $inemail,
					'^previous' => empty($inreferer) ? '-' : $inreferer,
					'^url' => isset($userid) ? qa_path_absolute('user/'.qa_get_logged_in_handle()) : '-',
					'^ip' => qa_remote_ip_address(),
					'^browser' => @$_SERVER['HTTP_USER_AGENT'],
				);
				
				if (qa_send_email(array(
					'fromemail' => qa_email_validate(@$inemail) ? $inemail : qa_opt('from_email'),
					'fromname' => $inname,
					'toemail' => qa_opt('feedback_email'),
					'toname' => qa_opt('site_title'),
					'subject' => qa_lang_sub('emails/feedback_subject', qa_opt('site_title')),
					'body' => strtr(qa_lang('emails/feedback_body'), $subs),
					'html' => false,
				)))
					$feedbacksent=true;
				else
					$pageerror=qa_lang_html('main/general_error');
					
				qa_report_event('feedback', $userid, qa_get_logged_in_handle(), qa_cookie_get(), array(
					'email' => $inemail,
					'name' => $inname,
					'message' => $inmessage,
					'previous' => $inreferer,
					'browser' => @$_SERVER['HTTP_USER_AGENT'],
				));
			}
		}
	}
	
	
//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('misc/feedback_title');
	
	$qa_content['error']=@$pageerror;

	$qa_content['form']=array(
		'tags' => 'method="post" action="'.qa_self_html().'"',
		
		'style' => 'tall',
		
		'fields' => array(
			'message' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_html_sub('misc/feedback_message', qa_opt('site_title')),
				'tags' => 'name="message" id="message"',
				'value' => qa_html(@$inmessage),
				'rows' => 8,
				'error' => qa_html(@$errors['message']),
			),

			'name' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_html('misc/feedback_name'),
				'tags' => 'name="name"',
				'value' => qa_html(isset($inname) ? $inname : @$userprofile['name']),
			),

			'email' => array(
				'type' => $feedbacksent ? 'static' : '',
				'label' => qa_lang_html('misc/feedback_email'),
				'tags' => 'name="email"',
				'value' => qa_html(isset($inemail) ? $inemail : qa_get_logged_in_email()),
				'note' => $feedbacksent ? null : qa_opt('email_privacy'),
			),
		),
		
		'buttons' => array(
			'send' => array(
				'label' => qa_lang_html('main/send_button'),
			),
		),
		
		'hidden' => array(
			'dofeedback' => '1',
			'code' => qa_get_form_security_code('feedback'),
			'referer' => qa_html(isset($inreferer) ? $inreferer : @$_SERVER['HTTP_REFERER']),
		),
	);
	
	if ($usecaptcha && !$feedbacksent)
		qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors);


	$qa_content['focusid']='message';
	
	if ($feedbacksent) {
		$qa_content['form']['ok']=qa_lang_html('misc/feedback_sent');
		unset($qa_content['form']['buttons']);
	}

	
	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/