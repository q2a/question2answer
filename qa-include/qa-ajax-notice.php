<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-notice.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Server-side response to Ajax requests to close a notice


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

	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-db-notices.php';
	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	

	$noticeid=qa_post_text('noticeid');	
	
	if (!qa_check_form_security_code('notice-'.$noticeid, qa_post_text('code')))
		echo "QA_AJAX_RESPONSE\n0\n".qa_lang('misc/form_security_reload');
	
	else {
		if ($noticeid=='visitor')
			setcookie('qa_noticed', 1, time()+86400*3650, '/', QA_COOKIE_DOMAIN);
		
		else {
			$userid=qa_get_logged_in_userid();
			
			if ($noticeid=='welcome')
				qa_db_user_set_flag($userid, QA_USER_FLAGS_WELCOME_NOTICE, false);
			else
				qa_db_usernotice_delete($userid, $noticeid);
		}
	
		
		echo "QA_AJAX_RESPONSE\n1";
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/