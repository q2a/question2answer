<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question-submit.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Common functions for question page form submission, either regular or via Ajax


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


	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-update.php';


	function qa_page_q_clicked($name)
/*
	Returns whether we should consider the button $name as having been clicked, taking into account Java-script simulated clicks
*/
	{
		return qa_clicked($name) || (qa_post_text('qa_click')==$name); // to catch simulated clicks
	}
	
	
	function qa_page_q_single_click_q($question, $answers, $commentsfollows, $closepost, &$error)
/*
	Checks for a POSTed click on $question by the current user and returns true if it was permitted and processed. Pass
	in the question's $answers, all $commentsfollows from it or its answers, and its closing $closepost (or null if
	none). If there is an error to display, it will be passed out in $error.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';

		$userid=qa_get_logged_in_userid();
		$handle=qa_get_logged_in_handle();
		$cookieid=qa_cookie_get();
		
		if (qa_page_q_clicked('q_doreopen') && $question['reopenable']) {
			qa_question_close_clear($question, $closepost, $userid, $handle, $cookieid);
			return true;
		}
		
		if ( (qa_page_q_clicked('q_dohide') && $question['hideable']) || (qa_page_q_clicked('q_doreject') && $question['moderatable']) ) {
			qa_question_set_hidden($question, true, $userid, $handle, $cookieid, $answers, $commentsfollows, $closepost);
			return true;
		}
		
		if ( (qa_page_q_clicked('q_doreshow') && $question['reshowable']) || (qa_page_q_clicked('q_doapprove') && $question['moderatable']) ) {
			qa_question_set_hidden($question, false, $userid, $handle, $cookieid, $answers, $commentsfollows, $closepost);
			return true;
		}
		
		if (qa_page_q_clicked('q_doclaim') && $question['claimable']) {
			if (qa_limits_remaining($userid, QA_LIMIT_QUESTIONS)) { // already checked 'permit_post_q'
				qa_question_set_userid($question, $userid, $handle, $cookieid);
				return true;
	
			} else
				$error=qa_lang_html('question/ask_limit');
		}
		
		if (qa_page_q_clicked('q_doflag') && $question['flagbutton']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
			
			$error=qa_flag_error_html($question, $userid, qa_request());
			if (!$error) {
				if (qa_flag_set_tohide($question, $userid, $handle, $cookieid, $question))
					qa_question_set_hidden($question, true, null, null, null, $answers, $commentsfollows, $closepost); // hiding not really by this user so pass nulls
				return true;
			}
		}
		
		if (qa_page_q_clicked('q_dounflag') && $question['unflaggable']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
			
			qa_flag_clear($question, $userid, $handle, $cookieid);
			return true;
		}
		
		if (qa_page_q_clicked('q_doclearflags') && $question['clearflaggable']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
		
			qa_flags_clear_all($question, $userid, $handle, $cookieid);
			return true;
		}
		
		return false;
	}
	
	
	function qa_page_q_single_click_a($answer, $question, $answers, $commentsfollows, $allowselectmove, &$error)
/*
	Checks for a POSTed click on $answer by the current user and returns true if it was permitted and processed. Pass in
	the $question, all of its $answers, and all $commentsfollows from it or its answers. Set $allowselectmove to whether
	it is legitimate to change the selected answer for the question from one to another (this can't be done via Ajax).
	If there is an error to display, it will be passed out in $error.
*/
	{
		$userid=qa_get_logged_in_userid();
		$handle=qa_get_logged_in_handle();
		$cookieid=qa_cookie_get();
		
		$prefix='a'.$answer['postid'].'_';
		
		if (qa_page_q_clicked($prefix.'doselect') && $question['aselectable'] && ($allowselectmove || ( (!isset($question['selchildid'])) && !qa_opt('do_close_on_select'))) ) {
			qa_question_set_selchildid($userid, $handle, $cookieid, $question, $answer['postid'], $answers);
			return true;
		}
		
		if (qa_page_q_clicked($prefix.'dounselect') && $question['aselectable'] && ($question['selchildid']==$answer['postid']) && ($allowselectmove || !qa_opt('do_close_on_select'))) {
			qa_question_set_selchildid($userid, $handle, $cookieid, $question, null, $answers);
			return true;
		}

		if ( (qa_page_q_clicked($prefix.'dohide') && $answer['hideable']) || (qa_page_q_clicked($prefix.'doreject') && $answer['moderatable']) ) {
			qa_answer_set_hidden($answer, true, $userid, $handle, $cookieid, $question, $commentsfollows);
			return true;
		}
		
		if ( (qa_page_q_clicked($prefix.'doreshow') && $answer['reshowable']) || (qa_page_q_clicked($prefix.'doapprove') && $answer['moderatable']) ) {
			qa_answer_set_hidden($answer, false, $userid, $handle, $cookieid, $question, $commentsfollows);
			return true;
		}
		
		if (qa_page_q_clicked($prefix.'dodelete') && $answer['deleteable']) {
			qa_answer_delete($answer, $question, $userid, $handle, $cookieid);
			return true;
		}
		
		if (qa_page_q_clicked($prefix.'doclaim') && $answer['claimable']) {
			if (qa_limits_remaining($userid, QA_LIMIT_ANSWERS)) { // already checked 'permit_post_a'
				qa_answer_set_userid($answer, $userid, $handle, $cookieid);
				return true;
			
			} else
				$error=qa_lang_html('question/answer_limit');
		}
		
		if (qa_page_q_clicked($prefix.'doflag') && $answer['flagbutton']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
			
			$error=qa_flag_error_html($answer, $userid, qa_request());
			if (!$error) {
				if (qa_flag_set_tohide($answer, $userid, $handle, $cookieid, $question))
					qa_answer_set_hidden($answer, true, null, null, null, $question, $commentsfollows); // hiding not really by this user so pass nulls
					
				return true;
			}
		}

		if (qa_page_q_clicked($prefix.'dounflag') && $answer['unflaggable']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
			
			qa_flag_clear($answer, $userid, $handle, $cookieid);
			return true;
		}
		
		if (qa_page_q_clicked($prefix.'doclearflags') && $answer['clearflaggable']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
			
			qa_flags_clear_all($answer, $userid, $handle, $cookieid);
			return true;
		}

		return false;
	}
	
	
	function qa_page_q_single_click_c($comment, $question, $parent, &$error)
/*
	Checks for a POSTed click on $comment by the current user and returns true if it was permitted and processed. Pass
	in the antecedent $question and the comment's $parent post. If there is an error to display, it will be passed out
	in $error.
*/
	{
		$userid=qa_get_logged_in_userid();
		$handle=qa_get_logged_in_handle();
		$cookieid=qa_cookie_get();
		
		$prefix='c'.$comment['postid'].'_';
		
		if ( (qa_page_q_clicked($prefix.'dohide') && $comment['hideable']) || (qa_page_q_clicked($prefix.'doreject') && $comment['moderatable']) ) {
			qa_comment_set_hidden($comment, true, $userid, $handle, $cookieid, $question, $parent);
			return true;
		}
		
		if ( (qa_page_q_clicked($prefix.'doreshow') && $comment['reshowable']) || (qa_page_q_clicked($prefix.'doapprove') && $comment['moderatable']) ) {
			qa_comment_set_hidden($comment, false, $userid, $handle, $cookieid, $question, $parent);
			return true;
		}
		
		if (qa_page_q_clicked($prefix.'dodelete') && $comment['deleteable']) {
			qa_comment_delete($comment, $question, $parent, $userid, $handle, $cookieid);
			return true;
		}
			
		if (qa_page_q_clicked($prefix.'doclaim') && $comment['claimable']) {
			if (qa_limits_remaining($userid, QA_LIMIT_COMMENTS)) {
				qa_comment_set_userid($comment, $userid, $handle, $cookieid);
				return true;
				
			} else
				$error=qa_lang_html('question/comment_limit');
		}
		
		if (qa_page_q_clicked($prefix.'doflag') && $comment['flagbutton']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
			
			$error=qa_flag_error_html($comment, $userid, qa_request());
			if (!$error) {
				if (qa_flag_set_tohide($comment, $userid, $handle, $cookieid, $question))
					qa_comment_set_hidden($comment, true, null, null, null, $question, $parent); // hiding not really by this user so pass nulls
				
				return true;
			}
		}

		if (qa_page_q_clicked($prefix.'dounflag') && $comment['unflaggable']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
			
			qa_flag_clear($comment, $userid, $handle, $cookieid);
			return true;
		}
		
		if (qa_page_q_clicked($prefix.'doclearflags') && $comment['clearflaggable']) {
			require_once QA_INCLUDE_DIR.'qa-app-votes.php';
			
			qa_flags_clear_all($comment, $userid, $handle, $cookieid);
			return true;
		}
		
		return false;
	}
	
	
	function qa_page_q_add_a_submit($question, $answers, $usecaptcha, &$in, &$errors)
/*
	Processes a POSTed form to add an answer to $question, returning the postid if successful, otherwise null. Pass in
	other $answers to the question and whether a $usecaptcha is required. The form fields submitted will be passed out
	as an array in $in, as well as any $errors on those fields.
*/
	{
		$in=array(
			'notify' => qa_post_text('a_notify') ? true : false,
			'email' => qa_post_text('a_email'),
			'queued' => qa_user_moderation_reason() ? true : false,
		);
		
		qa_get_post_content('a_editor', 'a_content', $in['editor'], $in['content'], $in['format'], $in['text']);
		
		$errors=array();
		
		$filtermodules=qa_load_modules_with('filter', 'filter_answer');
		foreach ($filtermodules as $filtermodule) {
			$oldin=$in;
			$filtermodule->filter_answer($in, $errors, $question, null);
			qa_update_post_text($in, $oldin);
		}
		
		if ($usecaptcha)
			qa_captcha_validate_post($errors);
			
		if (empty($errors)) {
			$testwords=implode(' ', qa_string_to_words($in['content']));
			
			foreach ($answers as $answer)
				if (!$answer['hidden'])
					if (implode(' ', qa_string_to_words($answer['content'])) == $testwords)
						$errors['content']=qa_lang_html('question/duplicate_content');
		}
		
		if (empty($errors)) {
			$userid=qa_get_logged_in_userid();
			$handle=qa_get_logged_in_handle();
			$cookieid=isset($userid) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary
			
			$answerid=qa_answer_create($userid, $handle, $cookieid, $in['content'], $in['format'], $in['text'], $in['notify'], $in['email'],
				$question, $in['queued']);
			
			return $answerid;
		}
		
		return null;
	}
	

	function qa_page_q_add_c_submit($question, $parent, $commentsfollows, $usecaptcha, &$in, &$errors)
/*
	Processes a POSTed form to add a comment, returning the postid if successful, otherwise null. Pass in the antecedent
	$question and the comment's $parent post. Set $usecaptcha to whether a captcha is required. Pass an array which
	includes the other comments with the same parent in $commentsfollows (it can contain other posts which are ignored).
	The form fields submitted will be passed out as an array in $in, as well as any $errors on those fields.
*/
	{
		$parentid=$parent['postid'];
		
		$prefix='c'.$parentid.'_';
		
		$in=array(
			'notify' => qa_post_text($prefix.'notify') ? true : false,
			'email' => qa_post_text($prefix.'email'),
			'queued' => qa_user_moderation_reason() ? true : false,
		);
		
		qa_get_post_content($prefix.'editor', $prefix.'content', $in['editor'], $in['content'], $in['format'], $in['text']);

		$errors=array();
		
		$filtermodules=qa_load_modules_with('filter', 'filter_comment');
		foreach ($filtermodules as $filtermodule) {
			$oldin=$in;
			$filtermodule->filter_comment($in, $errors, $question, $parent, null);
			qa_update_post_text($in, $oldin);
		}
		
		if ($usecaptcha)
			qa_captcha_validate_post($errors);

		if (empty($errors)) {
			$testwords=implode(' ', qa_string_to_words($in['content']));
			
			foreach ($commentsfollows as $comment)
				if (($comment['basetype']=='C') && ($comment['parentid']==$parentid) && !$comment['hidden'])
					if (implode(' ', qa_string_to_words($comment['content'])) == $testwords)
						$errors['content']=qa_lang_html('question/duplicate_content');
		}
		
		if (empty($errors)) {
			$userid=qa_get_logged_in_userid();
			$handle=qa_get_logged_in_handle();
			$cookieid=isset($userid) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary
						
			$commentid=qa_comment_create($userid, $handle, $cookieid, $in['content'], $in['format'], $in['text'], $in['notify'], $in['email'],
				$question, $parent, $commentsfollows, $in['queued']);
			
			return $commentid;
		}
		
		return null;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/