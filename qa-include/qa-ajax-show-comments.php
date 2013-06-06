<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-show-comments.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Server-side response to Ajax request to view full comment list


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-page-question-view.php';
	require_once QA_INCLUDE_DIR.'qa-util-sort.php';


//	Load relevant information about this question and check it exists

	$questionid=qa_post_text('c_questionid');
	$parentid=qa_post_text('c_parentid');
	$userid=qa_get_logged_in_userid();
	
	list($question, $parent, $children)=qa_db_select_with_pending(
		qa_db_full_post_selectspec($userid, $questionid),
		qa_db_full_post_selectspec($userid, $parentid),
		qa_db_full_child_posts_selectspec($userid, $parentid)
	);
	
	if (isset($parent)) {
		$parent=$parent+qa_page_q_post_rules($parent, null, null, $children);				
			// in theory we should retrieve the parent's parent and siblings for the above, but they're not going to be relevant

		foreach ($children as $key => $child)
			$children[$key]=$child+qa_page_q_post_rules($child, $parent, $children, null);
			
		$usershtml=qa_userids_handles_html($children, true);
			
		qa_sort_by($children, 'created');
			
		$c_list=qa_page_q_comment_follow_list($question, $parent, $children, true, $usershtml, false, null);
			
		$themeclass=qa_load_theme_class(qa_get_site_theme(), 'ajax-comments', null, null);
		
		echo "QA_AJAX_RESPONSE\n1\n";

		
	//	Send back the HTML

		$themeclass->c_list_items($c_list['cs']);

		return;
	}

	
	echo "QA_AJAX_RESPONSE\n0\n";

	
/*
	Omit PHP closing tag to help avoid accidental output
*/