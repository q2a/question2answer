<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-message.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for private messaging page


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	
	$handle=qa_request_part(1);
	$loginuserid=qa_get_logged_in_userid();


//	Check we have a handle, we're not using Q2A's single-sign on integration and that we're logged in

	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');
	
	if (!strlen($handle))
		qa_redirect('users');

	if (!isset($loginuserid)) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_insert_login_links(qa_lang_html('misc/message_must_login'), qa_request());
		return $qa_content;
	}


//	Find the user profile and questions and answers for this handle
	
	list($toaccount, $torecent, $fromrecent)=qa_db_select_with_pending(
		qa_db_user_account_selectspec($handle, false),
		qa_db_recent_messages_selectspec($loginuserid, true, $handle, false),
		qa_db_recent_messages_selectspec($handle, false, $loginuserid, true)
	);


//	Check the user exists and work out what can and can't be set (if not using single sign-on)
	
	if ( (!qa_opt('allow_private_messages')) || (!is_array($toaccount)) || ($toaccount['flags'] & QA_USER_FLAGS_NO_MESSAGES) )
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';
	

//	Process sending a message to user

	$messagesent=false;
	
	if (qa_post_text('domessage')) {
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
	
	//	Check that we haven't been blocked on volume
	
		$errorhtml=null;
		
		switch (qa_user_permit_error(null, QA_LIMIT_MESSAGES)) {
			case 'limit':
				$errorhtml=qa_lang_html('misc/message_limit');
				break;
				
			case false:
				break;
				
			default:
				$errorhtml=qa_lang_html('users/no_permission');
				break;
		}
	
		if (isset($errorhtml)) {
			$qa_content=qa_content_prepare();
			$qa_content['error']=$errorhtml;
			return $qa_content;
		}
	

	//	Proceed...
	
		$inmessage=qa_post_text('message');
		
		if (empty($inmessage))
			$errors['message']=qa_lang('misc/message_empty');
		
		if (empty($errors)) {
			require_once QA_INCLUDE_DIR.'qa-db-messages.php';
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';

			if (qa_opt('show_message_history'))
				$messageid=qa_db_message_create($loginuserid, $toaccount['userid'], $inmessage, '');
			else
				$messageid=null;

			$fromhandle=qa_get_logged_in_handle();
			$canreply=!(qa_get_logged_in_flags() & QA_USER_FLAGS_NO_MESSAGES);
			
			$more=strtr(qa_lang($canreply ? 'emails/private_message_reply' : 'emails/private_message_info'), array(
				'^f_handle' => $fromhandle,
				'^url' => qa_path($canreply ? ('message/'.$fromhandle) : ('user/'.$fromhandle), null, qa_opt('site_url')),
			));

			$subs=array(
				'^message' => $inmessage,
				'^f_handle' => $fromhandle,
				'^f_url' => qa_path('user/'.$fromhandle, null, qa_opt('site_url')),
				'^more' => $more,
				'^a_url' => qa_path_html('account', null, qa_opt('site_url')),
			);
			
			if (qa_send_notification($toaccount['userid'], $toaccount['email'], $toaccount['handle'],
					qa_lang('emails/private_message_subject'), qa_lang('emails/private_message_body'), $subs))
				$messagesent=true;
			else
				$page_error=qa_lang_html('main/general_error');

			qa_report_event('u_message', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), array(
				'userid' => $toaccount['userid'],
				'handle' => $toaccount['handle'],
				'message' => $inmessage,
			));
		}
	}


//	Prepare content for theme
	
	$qa_content=qa_content_prepare();
	
	$qa_content['title']=qa_lang_html('misc/private_message_title');

	$qa_content['error']=@$page_error;

	$qa_content['form_message']=array(
		'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
		
		'style' => 'tall',
		
		'fields' => array(
			'message' => array(
				'type' => $messagesent ? 'static' : '',
				'label' => qa_lang_html_sub('misc/message_for_x', qa_get_one_user_html($handle, false)),
				'tags' => 'NAME="message" ID="message"',
				'value' => qa_html(@$inmessage, $messagesent),
				'rows' => 8,
				'note' => qa_lang_html_sub('misc/message_explanation', qa_html(qa_opt('site_title'))),
				'error' => qa_html(@$errors['message']),
			),
		),
		
		'buttons' => array(
			'send' => array(
				'tags' => 'onClick="qa_show_waiting_after(this, false);"',
				'label' => qa_lang_html('main/send_button'),
			),
		),
		
		'hidden' => array(
			'domessage' => '1',
		),
	);
	
	$qa_content['focusid']='message';

	if ($messagesent) {
		$qa_content['form_message']['ok']=qa_lang_html('misc/message_sent');
		unset($qa_content['form_message']['fields']['message']['note']);
		unset($qa_content['form_message']['buttons']);
	}
	

//	If relevant, show recent message history

	if (qa_opt('show_message_history')) {
		$recent=array_merge($torecent, $fromrecent);
		
		qa_sort_by($recent, 'created');
		
		$showmessages=array_slice(array_reverse($recent, true), 0, QA_DB_RETRIEVE_MESSAGES);
		
		if (count($showmessages)) {
			$qa_content['form_recent']=array(
				'title' => qa_lang_html_sub('misc/message_recent_history', qa_html($toaccount['handle'])),
				'style' => 'tall',
				'fields' => array(),
			);
			
			foreach ($showmessages as $message) {
				$qa_content['form_recent']['fields'][]=array(
					'label' => qa_lang_html_sub(
						($message['touserid']==$toaccount['userid']) ? 'misc/message_sent_x_ago' : 'misc/message_received_x_ago',
						qa_html(qa_time_to_string(qa_opt('db_time')-$message['created']))),
					'type' => 'static',
					'value' => qa_viewer_html($message['content'], $message['format']),
				);
			}
		}
	}


	$qa_content['raw']['account']=$toaccount; // for plugin layers to access
	

	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/