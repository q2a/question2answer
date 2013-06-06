<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-answer.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Server-side response to Ajax create answer requests


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
	require_once QA_INCLUDE_DIR.'qa-app-limits.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';


//	Load relevant information about this question
	
	$questionid=qa_post_text('a_questionid');
	$userid=qa_get_logged_in_userid();
	
	list($question, $childposts)=qa_db_select_with_pending(
		qa_db_full_post_selectspec($userid, $questionid),
		qa_db_full_child_posts_selectspec($userid, $questionid)
	);


//	Check if the question exists, is not closed, and whether the user has permission to do this

	if ((@$question['basetype']=='Q') && (!isset($question['closedbyid'])) && !qa_user_post_permit_error('permit_post_a', $question, QA_LIMIT_ANSWERS)) {
		require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
		require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
		require_once QA_INCLUDE_DIR.'qa-page-question-view.php';
		require_once QA_INCLUDE_DIR.'qa-page-question-submit.php';


	//	Try to create the new answer
	
		$usecaptcha=qa_user_use_captcha(qa_user_level_for_post($question));
		$answers=qa_page_q_load_as($question, $childposts);
		$answerid=qa_page_q_add_a_submit($question, $answers, $usecaptcha, $in, $errors);
		
	//	If successful, page content will be updated via Ajax

		if (isset($answerid)) {
			$answer=qa_db_select_with_pending(qa_db_full_post_selectspec($userid, $answerid));
			
			$question=$question+qa_page_q_post_rules($question, null, null, $childposts); // array union
			$answer=$answer+qa_page_q_post_rules($answer, $question, $answers, null);
			
			$usershtml=qa_userids_handles_html(array($answer), true);
			
			$a_view=qa_page_q_answer_view($question, $answer, false, $usershtml, false);
			
			$themeclass=qa_load_theme_class(qa_get_site_theme(), 'ajax-answer', null, null);
			
			echo "QA_AJAX_RESPONSE\n1\n";

			
		//	Send back whether the 'answer' button should still be visible
		
			echo (int)qa_opt('allow_multi_answers')."\n";

			
		//	Send back the count of answers
			
			$countanswers=$question['acount']+1;

			if ($countanswers==1)
				echo qa_lang_html('question/1_answer_title')."\n";
			else
				echo qa_lang_html_sub('question/x_answers_title', $countanswers)."\n";


		//	Send back the HTML

			$themeclass->a_list_item($a_view);

			return;
		}
	}
	

	echo "QA_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if there were any problems

	
/*
	Omit PHP closing tag to help avoid accidental output
*/