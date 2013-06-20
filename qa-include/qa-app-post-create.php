<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-post-create.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Creating questions, answers and comments (application level)


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

	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';
	require_once QA_INCLUDE_DIR.'qa-db-hotness.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	
	
	function qa_combine_notify_email($userid, $notify, $email)
/*
	Return value to store in database combining $notify and $email values entered by user $userid (or null for anonymous)
*/
	{
		return $notify ? (empty($email) ? (isset($userid) ? '@' : null) : $email) : null;
	}
	
	
	function qa_question_create($followanswer, $userid, $handle, $cookieid, $title, $content, $format, $text, $tagstring, $notify, $email,
		$categoryid=null, $extravalue=null, $queued=false, $name=null)
/*
	Add a question (application level) - create record, update appropriate counts, index it, send notifications.
	If question is follow-on from an answer, $followanswer should contain answer database record, otherwise null.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';

		$postid=qa_db_post_create($queued ? 'Q_QUEUED' : 'Q', @$followanswer['postid'], $userid, isset($userid) ? null : $cookieid,
			qa_remote_ip_address(), $title, $content, $format, $tagstring, qa_combine_notify_email($userid, $notify, $email),
			$categoryid, isset($userid) ? null : $name);
		
		if (isset($extravalue))	{
			require_once QA_INCLUDE_DIR.'qa-db-metas.php';
			qa_db_postmeta_set($postid, 'qa_q_extra', $extravalue);
		}
		
		qa_db_posts_calc_category_path($postid);
		qa_db_hotness_update($postid);
		
		if ($queued) {
			qa_db_queuedcount_update();

		} else {
			qa_post_index($postid, 'Q', $postid, @$followanswer['postid'], $title, $content, $format, $text, $tagstring, $categoryid);
			qa_update_counts_for_q($postid);
			qa_db_points_update_ifuser($userid, 'qposts');
		}
		
		qa_report_event($queued ? 'q_queue' : 'q_post', $userid, $handle, $cookieid, array(
			'postid' => $postid,
			'parentid' => @$followanswer['postid'],
			'parent' => $followanswer,
			'title' => $title,
			'content' => $content,
			'format' => $format,
			'text' => $text,
			'tags' => $tagstring,
			'categoryid' => $categoryid,
			'extra' => $extravalue,
			'name' => $name,
			'notify' => $notify,
			'email' => $email,
		));
		
		return $postid;
	}
	
	
	function qa_update_counts_for_q($postid)
/*
	Perform various common cached count updating operations to reflect changes in the question whose id is $postid
*/
	{
		if (isset($postid)) // post might no longer exist
			qa_db_category_path_qcount_update(qa_db_post_get_category_path($postid));
		
		qa_db_qcount_update();
		qa_db_unaqcount_update();
		qa_db_unselqcount_update();
		qa_db_unupaqcount_update();
	}
	
	
	function qa_array_filter_by_keys($inarray, $keys)
/*
	Return an array containing the elements of $inarray whose key is in $keys
*/
	{
		$outarray=array();

		foreach ($keys as $key)
			if (isset($inarray[$key]))
				$outarray[$key]=$inarray[$key];
				
		return $outarray;
	}

	
	function qa_suspend_post_indexing($suspend=true)
/*
	Suspend the indexing (and unindexing) of posts via qa_post_index(...) and qa_post_unindex(...)
	if $suspend is true, otherwise reinstate it. A counter is kept to allow multiple calls.
*/
	{
		global $qa_post_indexing_suspended;
		
		$qa_post_indexing_suspended+=($suspend ? 1 : -1);
	}
	
	
	function qa_post_index($postid, $type, $questionid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid)
/*
	Add post $postid (which comes under $questionid) of $type (Q/A/C) to the database index, with $title, $text,
	$tagstring and $categoryid. Calls through to all installed search modules.
*/
	{
		global $qa_post_indexing_suspended;
		
		if ($qa_post_indexing_suspended>0)
			return;
			
	//	Send through to any search modules for indexing
	
		$searches=qa_load_modules_with('search', 'index_post');
		foreach ($searches as $search)
			$search->index_post($postid, $type, $questionid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid);
	}

		
	function qa_answer_create($userid, $handle, $cookieid, $content, $format, $text, $notify, $email, $question, $queued=false, $name=null)
/*
	Add an answer (application level) - create record, update appropriate counts, index it, send notifications.
	$question should contain database record for the question this is an answer to.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		$postid=qa_db_post_create($queued ? 'A_QUEUED' : 'A', $question['postid'], $userid, isset($userid) ? null : $cookieid,
			qa_remote_ip_address(), null, $content, $format, null, qa_combine_notify_email($userid, $notify, $email),
			$question['categoryid'], isset($userid) ? null : $name);
		
		qa_db_posts_calc_category_path($postid);
		
		if ($queued) {
			qa_db_queuedcount_update();
			
		} else {
			if ($question['type']=='Q') // don't index answer if parent question is hidden or queued
				qa_post_index($postid, 'A', $question['postid'], $question['postid'], null, $content, $format, $text, null, $question['categoryid']);
			
			qa_update_q_counts_for_a($question['postid']);
			qa_db_points_update_ifuser($userid, 'aposts');
		}
		
		qa_report_event($queued ? 'a_queue' : 'a_post', $userid, $handle, $cookieid, array(
			'postid' => $postid,
			'parentid' => $question['postid'],
			'parent' => $question,
			'content' => $content,
			'format' => $format,
			'text' => $text,
			'categoryid' => $question['categoryid'],
			'name' => $name,
			'notify' => $notify,
			'email' => $email,
		));
		
		return $postid;
	}
	
	
	function qa_update_q_counts_for_a($questionid)
/*
	Perform various common cached count updating operations to reflect changes in an answer of question $questionid
*/
	{
		qa_db_post_acount_update($questionid);
		qa_db_hotness_update($questionid);
		qa_db_acount_update();
		qa_db_unaqcount_update();
		qa_db_unupaqcount_update();
	}

	
	function qa_comment_create($userid, $handle, $cookieid, $content, $format, $text, $notify, $email, $question, $parent, $commentsfollows, $queued=false, $name=null)
/*
	Add a comment (application level) - create record, update appropriate counts, index it, send notifications.
	$question should contain database record for the question this is part of (as direct or comment on Q's answer).
	If this is a comment on an answer, $answer should contain database record for the answer, otherwise null.
	$commentsfollows should contain database records for all previous comments on the same question or answer,
	but it can also contain other records that are ignored.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		require_once QA_INCLUDE_DIR.'qa-util-string.php';

		if (!isset($parent))
			$parent=$question; // for backwards compatibility with old answer parameter
		
		$postid=qa_db_post_create($queued ? 'C_QUEUED' : 'C', $parent['postid'], $userid, isset($userid) ? null : $cookieid,
			qa_remote_ip_address(), null, $content, $format, null, qa_combine_notify_email($userid, $notify, $email),
			$question['categoryid'], isset($userid) ? null : $name);
		
		qa_db_posts_calc_category_path($postid);
		
		if ($queued) {
			qa_db_queuedcount_update();
		
		} else {
			if ( ($question['type']=='Q') && (($parent['type']=='Q') || ($parent['type']=='A')) ) // only index if antecedents fully visible
				qa_post_index($postid, 'C', $question['postid'], $parent['postid'], null, $content, $format, $text, null, $question['categoryid']);
			
			qa_db_points_update_ifuser($userid, 'cposts');
			qa_db_ccount_update();
		}
		
		$thread=array();
		
		foreach ($commentsfollows as $comment)
			if (($comment['type']=='C') && ($comment['parentid']==$parent['postid'])) // find just those for this parent, fully visible
				$thread[]=$comment;

		qa_report_event($queued ? 'c_queue' : 'c_post', $userid, $handle, $cookieid, array(
			'postid' => $postid,
			'parentid' => $parent['postid'],
			'parenttype' => $parent['basetype'],
			'parent' => $parent,
			'questionid' => $question['postid'],
			'question' => $question,
			'thread' => $thread,
			'content' => $content,
			'format' => $format,
			'text' => $text,
			'categoryid' => $question['categoryid'],
			'name' => $name,
			'notify' => $notify,
			'email' => $email,
		));
		
		return $postid;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/