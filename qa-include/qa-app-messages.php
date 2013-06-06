<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-messages.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Handling public/private messages


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


	function qa_wall_error_html($fromuserid, $touseraccount)
	{
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';

		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if ((!QA_FINAL_EXTERNAL_USERS) && qa_opt('allow_user_walls')) {
			if ( ($touseraccount['flags'] & QA_USER_FLAGS_NO_WALL_POSTS) && !(isset($fromuserid) && ($fromuserid==$touseraccount['userid'])) )
				return qa_lang_html('profile/post_wall_blocked');
			
			else
				switch (qa_user_permit_error('permit_post_wall', QA_LIMIT_MESSAGES)) {
					case 'limit':
						return qa_lang_html('profile/post_wall_limit');
						break;
						
					case 'login':
						return qa_insert_login_links(qa_lang_html('profile/post_wall_must_login'), qa_request());
						break;
						
					case 'confirm':
						return qa_insert_login_links(qa_lang_html('profile/post_wall_must_confirm'), qa_request());
						break;
						
					case 'approve':
						return qa_lang_html('profile/post_wall_must_be_approved');
						break;
						
					case false:
						return false;
						break;
				}
		}
		
		return qa_lang_html('users/no_permission');
	}
	
	
	function qa_wall_add_post($userid, $handle, $cookieid, $touserid, $tohandle, $content, $format)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		require_once QA_INCLUDE_DIR.'qa-db-messages.php';
				
		$messageid=qa_db_message_create($userid, $touserid, $content, $format, true);
		
		qa_report_event('u_wall_post', $userid, $handle, $cookieid, array(
			'userid' => $touserid,
			'handle' => $tohandle,
			'messageid' => $messageid,
			'content' => $content,
			'format' => $format,
			'text' => qa_viewer_text($content, $format),
		));

		return $messageid;
	}
	
	
	function qa_wall_delete_post($userid, $handle, $cookieid, $message)
	{
		require_once QA_INCLUDE_DIR.'qa-db-messages.php';
		
		qa_db_message_delete($message['messageid']);
		
		qa_report_event('u_wall_delete', $userid, $handle, $cookieid, array(
			'messageid' => $message['messageid'],
			'oldmessage' => $message,
		));
	}
	
	
	function qa_wall_posts_add_rules($usermessages, $userid)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$deleteable=isset($userid); // can delete all most recent messages...
		
		foreach ($usermessages as $key => $message) {
			if (($message['touserid']!=$userid) && ($message['fromuserid']!=$userid))
				$deleteable=false; // ... until we come across one that doesn't involve me 
			
			$usermessages[$key]['deleteable']=$deleteable;
		}
		
		return $usermessages;
	}
	
	
	function qa_wall_post_view($message, $userid)
	{
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		$options=qa_message_html_defaults();
		
		$htmlfields=qa_message_html_fields($message, $userid, $options);
		
		if ($message['deleteable'])
			$htmlfields['form']=array(
				'style' => 'light',

				'buttons' => array(
					'delete' => array(
						'tags' => 'NAME="m'.qa_html($message['messageid']).'_dodelete" onClick="return qa_wall_post_click('.qa_js($message['messageid']).', this);"',
						'label' => qa_lang_html('question/delete_button'),
						'popup' => qa_lang_html('profile/delete_wall_post_popup'),
					),
				),
			);
			
		return $htmlfields;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/