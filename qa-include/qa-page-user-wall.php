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
	$usermessages=array_slice($usermessages, 0, $pagesize);

	
//	Prepare content for theme
	
	$qa_content=qa_content_prepare();
	
	$qa_content['title']=qa_lang_html_sub('profile/wall_for_x', $userhtml);
	$qa_content['message_list']=array('messages' => array());
	
	foreach ($usermessages as $message)
		$qa_content['message_list']['messages'][]=qa_wall_post_view($message);

	$qa_content['page_links']=qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));


//	Sub menu for navigation in user pages

	$qa_content['navigation']['sub']=qa_user_sub_navigation($handle, 'wall');


	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/