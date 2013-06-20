<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-user.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for user profile page


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


//	Determine the identify of the user
	
	$handle=qa_request_part(1);
	if (!strlen($handle)) {
		$handle=qa_get_logged_in_handle();
		qa_redirect(isset($handle) ? ('user/'.$handle) : 'users');
	}
	
		
//	Get the HTML to display for the handle, and if we're using external users, determine the userid 

	if (QA_FINAL_EXTERNAL_USERS) {
		$userid=qa_handle_to_userid($handle);
		if (!isset($userid))
			return include QA_INCLUDE_DIR.'qa-page-not-found.php';
		
		$usershtml=qa_get_users_html(array($userid), false, qa_path_to_root(), true);
		$userhtml=@$usershtml[$userid];

	} else
		$userhtml=qa_html($handle);


//	Display the appropriate page based on the request	

	switch (qa_request_part(2)) {
		case 'wall':
			qa_set_template('user-wall');
			$qa_content=include QA_INCLUDE_DIR.'qa-page-user-wall.php';
			break;
		
		case 'activity':
			qa_set_template('user-activity');
			$qa_content=include QA_INCLUDE_DIR.'qa-page-user-activity.php';
			break;

		case 'questions':
			qa_set_template('user-questions');
			$qa_content=include QA_INCLUDE_DIR.'qa-page-user-questions.php';
			break;

		case 'answers':
			qa_set_template('user-answers');
			$qa_content=include QA_INCLUDE_DIR.'qa-page-user-answers.php';
			break;

		case null:
			$qa_content=include QA_INCLUDE_DIR.'qa-page-user-profile.php';
			break;
			
		default:
			$qa_content=include QA_INCLUDE_DIR.'qa-page-not-found.php';
			break;
	}
	
	return $qa_content;

/*
	Omit PHP closing tag to help avoid accidental output
*/