<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-click-admin.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Server-side response to Ajax single clicks on posts in admin section


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

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';


	$postid=qa_post_text('postid');
	$action=qa_post_text('action');

	if (qa_admin_single_click($postid, $action)) // permission check happens in here
		echo "QA_AJAX_RESPONSE\n1\n";
	else
		echo "QA_AJAX_RESPONSE\n0\n";
				
	
/*
	Omit PHP closing tag to help avoid accidental output
*/