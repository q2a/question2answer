<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-ajax-click-answer.php
	Description: Server-side response to Ajax single clicks on answer


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

	require_once QA_INCLUDE_DIR.'app/cookies.php';
	require_once QA_INCLUDE_DIR.'app/format.php';
	require_once QA_INCLUDE_DIR.'app/users.php';
	require_once QA_INCLUDE_DIR.'db/selects.php';
	require_once QA_INCLUDE_DIR.'pages/question-view.php';
	require_once QA_INCLUDE_DIR.'pages/question-submit.php';


//	Load relevant information about this answer

	$answerid=qa_post_text('answerid');
	$questionid=qa_post_text('questionid');

	$userid=qa_get_logged_in_userid();

	list($answer, $question, $qchildposts, $achildposts)=qa_db_select_with_pending(
		qa_db_full_post_selectspec($userid, $answerid),
		qa_db_full_post_selectspec($userid, $questionid),
		qa_db_full_child_posts_selectspec($userid, $questionid),
		qa_db_full_child_posts_selectspec($userid, $answerid)
	);


//	Check if there was an operation that succeeded

	if (
		(@$answer['basetype']=='A') &&
		(@$question['basetype']=='Q')
	) {
		$answers=qa_page_q_load_as($question, $qchildposts);

		$question=$question+qa_page_q_post_rules($question, null, null, $qchildposts); // array union
		$answer=$answer+qa_page_q_post_rules($answer, $question, $qchildposts, $achildposts);

		if (qa_page_q_single_click_a($answer, $question, $answers, $achildposts, false, $error)) {
			list($answer, $question)=qa_db_select_with_pending(
				qa_db_full_post_selectspec($userid, $answerid),
				qa_db_full_post_selectspec($userid, $questionid)
			);


		//	If so, page content to be updated via Ajax

			echo "QA_AJAX_RESPONSE\n1\n";


		//	Send back new count of answers

			$countanswers=$question['acount'];

			if ($countanswers==1)
				echo qa_lang_html('question/1_answer_title');
			else
				echo qa_lang_html_sub('question/x_answers_title', $countanswers);


		//	If the answer was not deleted....

			if (isset($answer)) {
				$question=$question+qa_page_q_post_rules($question, null, null, $qchildposts); // array union
				$answer=$answer+qa_page_q_post_rules($answer, $question, $qchildposts, $achildposts);

				$commentsfollows=qa_page_q_load_c_follows($question, $qchildposts, $achildposts);

				foreach ($commentsfollows as $key => $commentfollow)
					$commentsfollows[$key]=$commentfollow+qa_page_q_post_rules($commentfollow, $answer, $commentsfollows, null);

				$usershtml=qa_userids_handles_html(array_merge(array($answer), $commentsfollows), true);

				$a_view=qa_page_q_answer_view($question, $answer, ($answer['postid']==$question['selchildid']) && ($answer['type']=='A'),
					$usershtml, false);

				$a_view['c_list']=qa_page_q_comment_follow_list($question, $answer, $commentsfollows, false, $usershtml, false, null);

				$themeclass=qa_load_theme_class(qa_get_site_theme(), 'ajax-answer', null, null);
				$themeclass->initialize();


			//	... send back the HTML for it

				echo "\n";

				$themeclass->a_list_item($a_view);
			}

			return;
		}
	}


	echo "QA_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if something failed


/*
	Omit PHP closing tag to help avoid accidental output
*/