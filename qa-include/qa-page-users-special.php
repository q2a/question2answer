<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-users-special.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for admin page showing users with non-standard privileges


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

	
//	Check we're not using single-sign on integration
	
	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');
		

//	Get list of special users

	$users=qa_db_select_with_pending(qa_db_users_from_level_selectspec(QA_USER_LEVEL_EXPERT));


//	Check we have permission to view this page (moderator or above)

	if (qa_get_logged_in_level() < QA_USER_LEVEL_MODERATOR) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}


//	Get userids and handles of retrieved users

	$usershtml=qa_userids_handles_html($users);


//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('users/special_users');
	
	$qa_content['ranking']=array(
		'items' => array(),
		'rows' => ceil(qa_opt('page_size_users')/qa_opt('columns_users')),
		'type' => 'users'
	);
	
	foreach ($users as $user) {
		$qa_content['ranking']['items'][]=array(
			'label' => $usershtml[$user['userid']],
			'score' => qa_html(qa_user_level_string($user['level'])),
		);
	}

	$qa_content['navigation']['sub']=qa_users_sub_navigation();

	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/