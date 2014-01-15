<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-user-wall.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for user page showing all user wall posts


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
	require_once QA_INCLUDE_DIR.'qa-app-messages.php';
	

//	Check we're not using single-sign on integration, which doesn't allow walls
	
	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');


//	$handle, $userhtml are already set by qa-page-user.php

	$start=qa_get_start();
	
	
//	Find the questions for this user
	
	list($useraccount, $usermessages)=qa_db_select_with_pending(
		qa_db_user_account_selectspec($handle, false),
		qa_db_recent_messages_selectspec(null, null, $handle, false, qa_opt_if_loaded('page_size_wall'), $start)
	);
	
	if (!is_array($useraccount)) // check the user exists
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';


//	Perform pagination

	$pagesize=qa_opt('page_size_wall');
	$count=$useraccount['wallposts'];
	$loginuserid=qa_get_logged_in_userid();
	
	$usermessages=array_slice($usermessages, 0, $pagesize);
	$usermessages=qa_wall_posts_add_rules($usermessages, $start);


//	Process deleting or adding a wall post (similar but not identical code to qq-page-user-profile.php)
	
	$errors=array();
	
	$wallposterrorhtml=qa_wall_error_html($loginuserid, $useraccount['userid'], $useraccount['flags']);
	
	foreach ($usermessages as $message)
		if ($message['deleteable'] && qa_clicked('m'.$message['messageid'].'_dodelete')) {
			if (!qa_check_form_security_code('wall-'.$useraccount['handle'], qa_post_text('code')))
				$errors['page']=qa_lang_html('misc/form_security_again');
				
			else {
				qa_wall_delete_post($loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), $message);
				qa_redirect(qa_request(), $_GET);
			}
		}

	if (qa_clicked('dowallpost')) {
		$inmessage=qa_post_text('message');
		
		if (!strlen($inmessage))
			$errors['message']=qa_lang('profile/post_wall_empty');
			
		elseif (!qa_check_form_security_code('wall-'.$useraccount['handle'], qa_post_text('code')))
			$errors['message']=qa_lang_html('misc/form_security_again');
		
		elseif (!$wallposterrorhtml) {
			qa_wall_add_post($loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), $useraccount['userid'], $useraccount['handle'], $inmessage, '');
			qa_redirect(qa_request());
		}
	}

	
//	Prepare content for theme
	
	$qa_content=qa_content_prepare();
	
	$qa_content['title']=qa_lang_html_sub('profile/wall_for_x', $userhtml);
	$qa_content['error']=@$errors['page'];
	
	$qa_content['script_rel'][]='qa-content/qa-user.js?'.QA_VERSION;

	$qa_content['message_list']=array(
		'tags' => 'id="wallmessages"',
		
		'form' => array(
			'tags' => 'name="wallpost" method="post" action="'.qa_self_html().'"',
			'style' => 'tall',
			'hidden' => array(
				'qa_click' => '', // for simulating clicks in Javascript
				'handle' => qa_html($useraccount['handle']),
				'start' => qa_html($start),
				'code' => qa_get_form_security_code('wall-'.$useraccount['handle']),
			),
		),
		
		'messages' => array(),
	);
	
	if ($start==0) { // only allow posting on first page
		if ($wallposterrorhtml)
			$qa_content['message_list']['error']=$wallposterrorhtml; // an error that means we are not allowed to post
		
		else {
			$qa_content['message_list']['form']['fields']=array(
				'message' => array(
					'tags' => 'name="message" id="message"',
					'value' => qa_html(@$inmessage, false),
					'rows' => 2,
					'error' => qa_html(@$errors['message']),
				),
			);
			
			$qa_content['message_list']['form']['buttons']=array(
				'post' => array(
					'tags' => 'name="dowallpost" onclick="return qa_submit_wall_post(this, false);"',
					'label' => qa_lang_html('profile/post_wall_button'),
				),
			);
		}
	}

	foreach ($usermessages as $message)
		$qa_content['message_list']['messages'][]=qa_wall_post_view($message);
	
	$qa_content['page_links']=qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));


//	Sub menu for navigation in user pages

	$qa_content['navigation']['sub']=qa_user_sub_navigation($handle, 'wall',
		isset($loginuserid) && ($loginuserid==(QA_FINAL_EXTERNAL_USERS ? $userid : $useraccount['userid'])));


	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/