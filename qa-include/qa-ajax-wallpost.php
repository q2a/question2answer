<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-wallpost.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Server-side response to Ajax wall post requests


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
	
	require_once QA_INCLUDE_DIR.'qa-app-messages.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	
	
	$message=qa_post_text('message');
	$tohandle=qa_post_text('handle');
	$morelink=qa_post_text('morelink');

	$touseraccount=qa_db_select_with_pending(qa_db_user_account_selectspec($tohandle, false));
	$loginuserid=qa_get_logged_in_userid();
	
	$errorhtml=qa_wall_error_html($loginuserid, $touseraccount['userid'], $touseraccount['flags']);
	
	if ($errorhtml || (!strlen($message)) || !qa_check_form_security_code('wall-'.$tohandle, qa_post_text('code')) )
		echo "QA_AJAX_RESPONSE\n0"; // if there's an error, process in non-Ajax way
	
	else {
		$messageid=qa_wall_add_post($loginuserid, qa_get_logged_in_handle(), qa_cookie_get(),
			$touseraccount['userid'], $touseraccount['handle'], $message, '');
		$touseraccount['wallposts']++; // won't have been updated
	
		$usermessages=qa_db_select_with_pending(qa_db_recent_messages_selectspec(null, null, $touseraccount['userid'], true, qa_opt('page_size_wall')));
		$usermessages=qa_wall_posts_add_rules($usermessages, 0);
		
		$themeclass=qa_load_theme_class(qa_get_site_theme(), 'wall', null, null);

		echo "QA_AJAX_RESPONSE\n1\n";
		
		echo 'm'.$messageid."\n"; // element in list to be revealed
		
		foreach ($usermessages as $message)
			$themeclass->message_item(qa_wall_post_view($message));

		if ($morelink && ($touseraccount['wallposts']>count($usermessages)))
			$themeclass->message_item(qa_wall_view_more_link($tohandle, count($usermessages)));
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/