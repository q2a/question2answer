<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-unsubscribe.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for unsubscribe page (unsubscribe link is sent in mass mailings)


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

	require_once QA_INCLUDE_DIR.'qa-db-users.php';


//	Check we're not using single-sign on integration
	
	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User login is handled by external code');
		

//	Check the code and unsubscribe the user if appropriate

	$unsubscribed=false;
	$loginuserid=qa_get_logged_in_userid();
	
	$incode=trim(qa_get('c')); // trim to prevent passing in blank values to match uninitiated DB rows
	$inhandle=qa_get('u');
	
	if (!empty($inhandle)) { // match based on code and handle provided on URL
		$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($inhandle, false));

		if (strtolower(trim(@$userinfo['emailcode']))==strtolower($incode)) {
			qa_db_user_set_flag($userinfo['userid'], QA_USER_FLAGS_NO_MAILINGS, true);
			$unsubscribed=true;
		}
	}
	
	if ( (!$unsubscribed) && isset($loginuserid)) { // as a backup, also unsubscribe logged in user
		qa_db_user_set_flag($loginuserid, QA_USER_FLAGS_NO_MAILINGS, true);
		$unsubscribed=true;
	}


//	Prepare content for theme
	
	$qa_content=qa_content_prepare();
	
	$qa_content['title']=qa_lang_html('users/unsubscribe_title');

	if ($unsubscribed)
		$qa_content['error']=strtr(qa_lang_html('users/unsubscribe_complete'), array(
			'^0' => qa_html(qa_opt('site_title')),
			'^1' => '<a href="'.qa_path_html('account').'">',
			'^2' => '</a>',
		));
	else
		$qa_content['error']=qa_insert_login_links(qa_lang_html('users/unsubscribe_wrong_log_in'), 'unsubscribe');

		
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/