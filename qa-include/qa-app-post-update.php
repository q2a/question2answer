<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-post-update.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Changing questions, answer and comments (application level)


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
	require_once QA_INCLUDE_DIR.'qa-app-updates.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-update.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';
	require_once QA_INCLUDE_DIR.'qa-db-hotness.php';

	
	function qa_question_set_content($oldquestion, $title, $content, $format, $text, $tagstring, $notify, $userid, $handle, $cookieid, $extravalue=null)
/*
	Change the fields of a question (application level) to $title, $content, $format, $tagstring, $notify and
	$extravalue and reindex based on $text. Pass the question's database record before changes in $oldquestion and
	details of the user doing this in $userid, $handle and $cookieid. Reports event as appropriate.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		qa_post_unindex($oldquestion['postid']);
		
		$titlechanged=strcmp($oldquestion['title'], $title) ? true : false;
		$contentchanged=strcmp($oldquestion['content'], $content) || strcmp($oldquestion['format'], $format);
		$tagschanged=strcmp($oldquestion['tags'], $tagstring) ? true : false;
		$setupdated=$titlechanged || $contentchanged || $tagschanged;
		
		qa_db_post_set_content($oldquestion['postid'], $title, $content, $format, $tagstring, $notify,
			$setupdated ? $userid : null, $setupdated ? qa_remote_ip_address() : null,
			($titlechanged || $contentchanged) ? QA_UPDATE_CONTENT : QA_UPDATE_TAGS);
			
		if (isset($extravalue)) {
			require_once QA_INCLUDE_DIR.'qa-db-metas.php';
			qa_db_postmeta_set($oldquestion['postid'], 'qa_q_extra', $extravalue);
		}
		
		if ($oldquestion['type']=='Q') // not hidden or queued
			qa_post_index($oldquestion['postid'], 'Q', $oldquestion['postid'], $oldquestion['parentid'], $title, $content, $format, $text, $tagstring, $oldquestion['categoryid']);

		qa_report_event('q_edit', $userid, $handle, $cookieid, array(
			'postid' => $oldquestion['postid'],
			'title' => $title,
			'content' => $content,
			'format' => $format,
			'text' => $text,
			'tags' => $tagstring,
			'extra' => $extravalue,
			'oldquestion' => $oldquestion,
			'oldtitle' => $oldquestion['title'],
			'oldcontent' => $oldquestion['content'],
			'oldformat' => $oldquestion['format'],
			'oldtags' => $oldquestion['tags'],
			'titlechanged' => $titlechanged,
			'contentchanged' => $contentchanged,
			'tagschanged' => $tagschanged,
		));
	}
	
	
	function qa_question_set_selchildid($userid, $handle, $cookieid, $oldquestion, $selchildid, $answers)
/*
	Set the selected answer (application level) of $oldquestion to $selchildid. Pass details of the user doing this
	in $userid, $handle and $cookieid, and the database records for all answers to the question in $answers.
	Handles user points values and notifications.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		$oldselchildid=$oldquestion['selchildid'];
		
		qa_db_post_set_selchildid($oldquestion['postid'], isset($selchildid) ? $selchildid : null, $userid, qa_remote_ip_address());
		qa_db_points_update_ifuser($oldquestion['userid'], 'aselects');
		qa_db_unselqcount_update();
		
		if (isset($oldselchildid) && isset($answers[$oldselchildid])) {
			qa_db_points_update_ifuser($answers[$oldselchildid]['userid'], 'aselecteds');
		
			qa_report_event('a_unselect', $userid, $handle, $cookieid, array(
				'parentid' => $oldquestion['postid'],
				'parent' => $oldquestion,
				'postid' => $oldselchildid,
				'answer' => $answers[$oldselchildid],
			));
		}

		if (isset($selchildid)) {
			qa_db_points_update_ifuser($answers[$selchildid]['userid'], 'aselecteds');

			qa_report_event('a_select', $userid, $handle, $cookieid, array(
				'parentid' => $oldquestion['postid'],
				'parent' => $oldquestion,
				'postid' => $selchildid,
				'answer' => $answers[$selchildid],
			));
		}
	}

	
	function qa_question_close_clear($oldquestion, $oldclosepost, $userid, $handle, $cookieid)
/*
	Reopen $oldquestion if it was closed. Pass details of the user doing this in $userid, $handle and $cookieid, and the
	$oldclosepost (to match $oldquestion['closedbyid']) if any.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		if (isset($oldquestion['closedbyid'])) {
			qa_db_post_set_closed($oldquestion['postid'], null, $userid, qa_remote_ip_address());

			if (isset($oldclosepost) && ($oldclosepost['parentid']==$oldquestion['postid'])) {
				qa_post_unindex($oldclosepost['postid']);
				qa_db_post_delete($oldclosepost['postid']);
			}

			qa_report_event('q_reopen', $userid, $handle, $cookieid, array(
				'postid' => $oldquestion['postid'],
				'oldquestion' => $oldquestion,
			));
		}
	}
	
	
	function qa_question_close_duplicate($oldquestion, $oldclosepost, $originalpostid, $userid, $handle, $cookieid)
/*
	Close $oldquestion as a duplicate of the question with id $originalpostid. Pass details of the user doing this in
	$userid, $handle and $cookieid, and the $oldclosepost (to match $oldquestion['closedbyid']) if any. See
	qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		qa_question_close_clear($oldquestion, $oldclosepost, $userid, $handle, $cookieid);

		qa_db_post_set_closed($oldquestion['postid'], $originalpostid, $userid, qa_remote_ip_address());
		
		qa_report_event('q_close', $userid, $handle, $cookieid, array(
			'postid' => $oldquestion['postid'],
			'oldquestion' => $oldquestion,
			'reason' => 'duplicate',
			'originalid' => $originalpostid,
		));
	}
	

	function qa_question_close_other($oldquestion, $oldclosepost, $note, $userid, $handle, $cookieid)
/*
	Close $oldquestion with the reason given in $note. Pass details of the user doing this in $userid, $handle and
	$cookieid, and the $oldclosepost (to match $oldquestion['closedbyid']) if any.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		qa_question_close_clear($oldquestion, $oldclosepost, $userid, $handle, $cookieid);
		
		$postid=qa_db_post_create('NOTE', $oldquestion['postid'], $userid, isset($userid) ? null : $cookieid,
			qa_remote_ip_address(), null, $note, '', null, null, $oldquestion['categoryid']);
		
		qa_db_posts_calc_category_path($postid);
		
		if ($oldquestion['type']=='Q')
			qa_post_index($postid, 'NOTE', $oldquestion['postid'], $oldquestion['postid'], null, $note, '', $note, null, $oldquestion['categoryid']);
		
		qa_db_post_set_closed($oldquestion['postid'], $postid, $userid, qa_remote_ip_address());
		
		qa_report_event('q_close', $userid, $handle, $cookieid, array(
			'postid' => $oldquestion['postid'],
			'oldquestion' => $oldquestion,
			'reason' => 'other',
			'note' => $note,
		));
	}

	
	function qa_question_set_hidden($oldquestion, $hidden, $userid, $handle, $cookieid, $answers, $commentsfollows, $closepost=null)
/*
	Set the hidden status (application level) of $oldquestion to $hidden. Pass details of the user doing this in
	$userid, $handle and $cookieid, the database records for all answers to the question in $answers, the database
	records for all comments on the question or the question's answers in $commentsfollows ($commentsfollows can also
	contain records for follow-on questions which are ignored), and $closepost to match $oldquestion['closedbyid'] (if any).
	Handles indexing, user points, cached counts and event reports.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
			
		$wasqueued=($oldquestion['type']=='Q_QUEUED');
		
		qa_post_unindex($oldquestion['postid']);
		
		foreach ($answers as $answer)
			qa_post_unindex($answer['postid']);
		
		foreach ($commentsfollows as $comment)
			if ($comment['basetype']=='C')
				qa_post_unindex($comment['postid']);
				
		if (@$closepost['parentid']==$oldquestion['postid'])
			qa_post_unindex($closepost['postid']);
				
		$setupdated=$hidden || !$wasqueued; // don't record approval of a post as an update action
			
		qa_db_post_set_type($oldquestion['postid'], $hidden ? 'Q_HIDDEN' : 'Q',
			$setupdated ? $userid : null, $setupdated ? qa_remote_ip_address() : null, QA_UPDATE_VISIBLE);
		
		qa_db_category_path_qcount_update(qa_db_post_get_category_path($oldquestion['postid']));
		qa_db_points_update_ifuser($oldquestion['userid'], array('qposts', 'aselects'));
		qa_db_qcount_update();
		qa_db_unaqcount_update();
		qa_db_unselqcount_update();
		qa_db_unupaqcount_update();
		
		if (!$hidden) {
			qa_post_index($oldquestion['postid'], 'Q', $oldquestion['postid'], $oldquestion['parentid'], $oldquestion['title'], $oldquestion['content'],
				$oldquestion['format'], qa_viewer_text($oldquestion['content'], $oldquestion['format']), $oldquestion['tags'], $oldquestion['categoryid']);

			foreach ($answers as $answer)
				if ($answer['type']=='A') // even if question visible, don't index hidden or queued answers
					qa_post_index($answer['postid'], $answer['type'], $oldquestion['postid'], $answer['parentid'], null,
						$answer['content'], $answer['format'], qa_viewer_text($answer['content'], $answer['format']), null, $answer['categoryid']);
					
			foreach ($commentsfollows as $comment)
				if ($comment['type']=='C') {
					$answer=@$answers[$comment['parentid']];
					
					if ( (!isset($answer)) || ($answer['type']=='A') ) // don't index comment if it or its parent is hidden
						qa_post_index($comment['postid'], $comment['type'], $oldquestion['postid'], $comment['parentid'], null,
							$comment['content'], $comment['format'], qa_viewer_text($comment['content'], $comment['format']), null, $comment['categoryid']);
				}
							
			if ($closepost['parentid']==$oldquestion['postid'])
				qa_post_index($closepost['postid'], $closepost['type'], $oldquestion['postid'], $closepost['parentid'], null,
					$closepost['content'], $closepost['format'], qa_viewer_text($closepost['content'], $closepost['format']), null, $closepost['categoryid']);
		}

		qa_report_event($wasqueued ? ($hidden ? 'q_reject' : 'q_approve') : ($hidden ? 'q_hide' : 'q_reshow'), $userid, $handle, $cookieid, array(
			'postid' => $oldquestion['postid'],
			'oldquestion' => $oldquestion,
		));
		
		if ($wasqueued && !$hidden) {
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			qa_report_event('q_post', $oldquestion['userid'], $oldquestion['handle'], $oldquestion['cookieid'], array(
				'postid' => $oldquestion['postid'],
				'parentid' => $oldquestion['parentid'],
				'parent' => isset($oldquestion['parentid']) ? qa_db_single_select(qa_db_full_post_selectspec(null, $oldquestion['parentid'])) : null,
				'title' => $oldquestion['title'],
				'content' => $oldquestion['content'],
				'format' => $oldquestion['format'],
				'text' => qa_viewer_text($oldquestion['content'], $oldquestion['format']),
				'tags' => $oldquestion['tags'],
				'categoryid' => $oldquestion['categoryid'],
				'notify' => isset($oldquestion['notify']),
				'email' => qa_email_validate($oldquestion['notify']) ? $oldquestion['notify'] : null,
				'delayed' => $oldquestion['created'],
			));
		}
	}

	
	function qa_question_set_category($oldquestion, $categoryid, $userid, $handle, $cookieid, $answers, $commentsfollows, $closepost=null)
/*
	Sets the category (application level) of $oldquestion to $categoryid. Pass details of the user doing this in
	$userid, $handle and $cookieid, the database records for all answers to the question in $answers, the database
	records for all comments on the question or the question's answers in $commentsfollows ($commentsfollows can also
	contain records for follow-on questions which are ignored), and $closepost to match $oldquestion['closedbyid'] (if any).
	Handles cached counts and event reports and will reset category IDs and paths for all answers and comments.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		$oldpath=qa_db_post_get_category_path($oldquestion['postid']);
		
		qa_db_post_set_category($oldquestion['postid'], $categoryid, $userid, qa_remote_ip_address());
		qa_db_posts_calc_category_path($oldquestion['postid']);
		
		$newpath=qa_db_post_get_category_path($oldquestion['postid']);
		
		qa_db_category_path_qcount_update($oldpath);
		qa_db_category_path_qcount_update($newpath);

		$otherpostids=array();
		foreach ($answers as $answer)
			$otherpostids[]=$answer['postid'];
			
		foreach ($commentsfollows as $comment)
			if ($comment['basetype']=='C')
				$otherpostids[]=$comment['postid'];
				
		if (@$closepost['parentid']==$oldquestion['postid'])
			$otherpostids[]=$closepost['postid'];
				
		qa_db_posts_set_category_path($otherpostids, $newpath);

		$searchmodules=qa_load_modules_with('search', 'move_post');
		foreach ($searchmodules as $searchmodule) {
			$searchmodule->move_post($oldquestion['postid'], $categoryid);
			foreach ($otherpostids as $otherpostid)
				$searchmodule->move_post($otherpostid, $categoryid);
		}

		qa_report_event('q_move', $userid, $handle, $cookieid, array(
			'postid' => $oldquestion['postid'],
			'oldquestion' => $oldquestion,
			'categoryid' => $categoryid,
			'oldcategoryid' => $oldquestion['categoryid'],
		));
	}
	
	
	function qa_question_delete($oldquestion, $userid, $handle, $cookieid, $oldclosepost=null)
/*
	Permanently delete a question (application level) from the database. The question must not have any answers or
	comments on it. Pass details of the user doing this in $userid, $handle and $cookieid, and $closepost to match
	$oldquestion['closedbyid'] (if any). Handles unindexing, votes, points, cached counts and event reports.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-votes.php';
		
		if ($oldquestion['type']!='Q_HIDDEN')
			qa_fatal_error('Tried to delete a non-hidden question');
		
		if (isset($oldclosepost) && ($oldclosepost['parentid']==$oldquestion['postid'])) {
			qa_db_post_set_closed($oldquestion['postid'], null); // for foreign key constraint
			qa_post_unindex($oldclosepost['postid']);
			qa_db_post_delete($oldclosepost['postid']);
		}

		$useridvotes=qa_db_uservote_post_get($oldquestion['postid']);
		$oldpath=qa_db_post_get_category_path($oldquestion['postid']);
		
		qa_post_unindex($oldquestion['postid']);
		qa_db_post_delete($oldquestion['postid']); // also deletes any related voteds due to cascading
		
		qa_db_category_path_qcount_update($oldpath);
		qa_db_points_update_ifuser($oldquestion['userid'], array('qposts', 'aselects', 'qvoteds', 'upvoteds', 'downvoteds'));
		
		foreach ($useridvotes as $voteruserid => $vote)
			qa_db_points_update_ifuser($voteruserid, ($vote>0) ? 'qupvotes' : 'qdownvotes');
				// could do this in one query like in qa_db_users_recalc_points() but this will do for now - unlikely to be many votes
		
		qa_db_qcount_update();
		qa_db_unaqcount_update();
		qa_db_unselqcount_update();
		qa_db_unupaqcount_update();

		qa_report_event('q_delete', $userid, $handle, $cookieid, array(
			'postid' => $oldquestion['postid'],
			'oldquestion' => $oldquestion,
		));
	}


	function qa_question_set_userid($oldquestion, $userid, $handle, $cookieid)
/*
	Set the author (application level) of $oldquestion to $userid and also pass $handle and $cookieid
	of user. Updates points and reports events as appropriate.
*/
	{
		qa_db_post_set_userid($oldquestion['postid'], $userid);

		qa_db_points_update_ifuser($oldquestion['userid'], array('qposts', 'aselects', 'qvoteds', 'upvoteds', 'downvoteds'));
		qa_db_points_update_ifuser($userid, array('qposts', 'aselects', 'qvoteds', 'upvoteds', 'downvoteds'));
		
		qa_report_event('q_claim', $userid, $handle, $cookieid, array(
			'postid' => $oldquestion['postid'],
			'oldquestion' => $oldquestion,
		));
	}

	
	function qa_post_unindex($postid)
/*
	Remove post $postid from our index and update appropriate word counts. Calls through to all search modules.
*/
	{
		global $qa_post_indexing_suspended;

		if ($qa_post_indexing_suspended>0)
			return;
			
	//	Send through to any search modules for unindexing
	
		$searchmodules=qa_load_modules_with('search', 'unindex_post');
		foreach ($searchmodules as $searchmodule)
			$searchmodule->unindex_post($postid);
	}

	
	function qa_answer_set_content($oldanswer, $content, $format, $text, $notify, $userid, $handle, $cookieid, $question)
/*
	Change the fields of an answer (application level) to $content, $format and $notify, and reindex based on $text.
	Pass the answer's database record before changes in $oldanswer, the question's in $question, and details of the
	user doing this in $userid, $handle and $cookieid. Handle indexing and event reports as appropriate.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		qa_post_unindex($oldanswer['postid']);
		
		$contentchanged=strcmp($oldanswer['content'], $content) || strcmp($oldanswer['format'], $format);
		
		qa_db_post_set_content($oldanswer['postid'], $oldanswer['title'], $content, $format, $oldanswer['tags'], $notify,
			$contentchanged ? $userid : null, $contentchanged ? qa_remote_ip_address() : null);
		
		if ( ($oldanswer['type']=='A') && ($question['type']=='Q') ) // don't index if question or answer are hidden/queued
			qa_post_index($oldanswer['postid'], 'A', $question['postid'], $oldanswer['parentid'], null, $content, $format, $text, null, $oldanswer['categoryid']);

		qa_report_event('a_edit', $userid, $handle, $cookieid, array(
			'postid' => $oldanswer['postid'],
			'parentid' => $oldanswer['parentid'],
			'content' => $content,
			'format' => $format,
			'text' => $text,
			'oldcontent' => $oldanswer['content'],
			'oldformat' => $oldanswer['format'],
			'oldanswer' => $oldanswer,
			'contentchanged' => $contentchanged,
		));
	}

	
	function qa_answer_set_hidden($oldanswer, $hidden, $userid, $handle, $cookieid, $question, $commentsfollows)
/*
	Set the hidden status (application level) of $oldanswer to $hidden. Pass details of the user doing this
	in $userid, $handle and $cookieid, the database record for the question in $question, and the database
	records for all comments on the answer in $commentsfollows ($commentsfollows can also contain other
	records which are ignored). Handles indexing, user points, cached counts and event reports.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
		$wasqueued=($oldanswer['type']=='A_QUEUED');
		
		qa_post_unindex($oldanswer['postid']);
		
		foreach ($commentsfollows as $comment)
			if ( ($comment['basetype']=='C') && ($comment['parentid']==$oldanswer['postid']) )
				qa_post_unindex($comment['postid']);
		
		$setupdated=$hidden || !$wasqueued; // don't record approval of a post as an update action
		
		qa_db_post_set_type($oldanswer['postid'], $hidden ? 'A_HIDDEN' : 'A',
			$setupdated ? $userid : null, $setupdated ? qa_remote_ip_address() : null, QA_UPDATE_VISIBLE);

		qa_db_points_update_ifuser($oldanswer['userid'], array('aposts', 'aselecteds'));
		qa_db_post_acount_update($question['postid']);
		qa_db_hotness_update($question['postid']);
		qa_db_acount_update();
		qa_db_unaqcount_update();
		qa_db_unupaqcount_update();
		
		if (($question['type']=='Q') && !$hidden) { // even if answer visible, don't index if question is hidden or queued
			qa_post_index($oldanswer['postid'], 'A', $question['postid'], $oldanswer['parentid'], null, $oldanswer['content'],
				$oldanswer['format'], qa_viewer_text($oldanswer['content'], $oldanswer['format']), null, $oldanswer['categoryid']);
			
			foreach ($commentsfollows as $comment)
				if ( ($comment['type']=='C') && ($comment['parentid']==$oldanswer['postid']) ) // and don't index hidden/queued comments
					qa_post_index($comment['postid'], $comment['type'], $question['postid'], $comment['parentid'], null, $comment['content'],
						$comment['format'], qa_viewer_text($comment['content'], $comment['format']), null, $comment['categoryid']);
		}

		qa_report_event($wasqueued ? ($hidden ? 'a_reject' : 'a_approve') : ($hidden ? 'a_hide' : 'a_reshow'), $userid, $handle, $cookieid, array(
			'postid' => $oldanswer['postid'],
			'parentid' => $oldanswer['parentid'],
			'oldanswer' => $oldanswer,
		));
		
		if ($wasqueued && !$hidden) {
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			qa_report_event('a_post', $oldanswer['userid'], $oldanswer['handle'], $oldanswer['cookieid'], array(
				'postid' => $oldanswer['postid'],
				'parentid' => $oldanswer['parentid'],
				'parent' => $question,
				'content' => $oldanswer['content'],
				'format' => $oldanswer['format'],
				'text' => qa_viewer_text($oldanswer['content'], $oldanswer['format']),
				'categoryid' => $oldanswer['categoryid'],
				'notify' => isset($oldanswer['notify']),
				'email' => qa_email_validate($oldanswer['notify']) ? $oldanswer['notify'] : null,
				'delayed' => $oldanswer['created'],
			));
		}
	}

	
	function qa_answer_delete($oldanswer, $question, $userid, $handle, $cookieid)
/*
	Permanently delete an answer (application level) from the database. The answer must not have any comments or
	follow-on questions. Pass the database record for the question in $question and details of the user doing this
	in $userid, $handle and $cookieid. Handles unindexing, votes, points, cached counts and event reports.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-votes.php';
		
		if ($oldanswer['type']!='A_HIDDEN')
			qa_fatal_error('Tried to delete a non-hidden answer');
		
		$useridvotes=qa_db_uservote_post_get($oldanswer['postid']);
		
		qa_post_unindex($oldanswer['postid']);
		qa_db_post_delete($oldanswer['postid']); // also deletes any related voteds due to cascading
		
		if ($question['selchildid']==$oldanswer['postid']) {
			qa_db_post_set_selchildid($question['postid'], null);
			qa_db_points_update_ifuser($question['userid'], 'aselects');
			qa_db_unselqcount_update();
		}
		
		qa_db_points_update_ifuser($oldanswer['userid'], array('aposts', 'aselecteds', 'avoteds', 'upvoteds', 'downvoteds'));
		
		foreach ($useridvotes as $userid => $vote)
			qa_db_points_update_ifuser($userid, ($vote>0) ? 'aupvotes' : 'adownvotes');
				// could do this in one query like in qa_db_users_recalc_points() but this will do for now - unlikely to be many votes
		
		qa_db_post_acount_update($question['postid']);
		qa_db_hotness_update($question['postid']);
		qa_db_acount_update();
		qa_db_unaqcount_update();
		qa_db_unupaqcount_update();

		qa_report_event('a_delete', $userid, $handle, $cookieid, array(
			'postid' => $oldanswer['postid'],
			'parentid' => $oldanswer['parentid'],
			'oldanswer' => $oldanswer,
		));
	}
	
	
	function qa_answer_set_userid($oldanswer, $userid, $handle, $cookieid)
/*
	Set the author (application level) of $oldanswer to $userid and also pass $handle and $cookieid
	of user. Updates points and reports events as appropriate.
*/
	{
		qa_db_post_set_userid($oldanswer['postid'], $userid);

		qa_db_points_update_ifuser($oldanswer['userid'], array('aposts', 'aselecteds', 'avoteds', 'upvoteds', 'downvoteds'));
		qa_db_points_update_ifuser($userid, array('aposts', 'aselecteds', 'avoteds', 'upvoteds', 'downvoteds'));

		qa_report_event('a_claim', $userid, $handle, $cookieid, array(
			'postid' => $oldanswer['postid'],
			'parentid' => $oldanswer['parentid'],
			'oldanswer' => $oldanswer,
		));
	}

	
	function qa_comment_set_content($oldcomment, $content, $format, $text, $notify, $userid, $handle, $cookieid, $question, $parent)
/*
	Change the fields of a comment (application level) to $content, $format and $notify, and reindex based on $text.
	Pass the comment's database record before changes in $oldcomment, details of the user doing this in  $userid,
	$handle and $cookieid, the antecedent question in $question and the answer's database record in $answer if this
	is a comment on an answer, otherwise null. Handles unindexing and event reports.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		if (!isset($parent))
			$parent=$question; // for backwards compatibility with old answer parameter
		
		qa_post_unindex($oldcomment['postid']);
		
		$contentchanged=strcmp($oldcomment['content'], $content) || strcmp($oldcomment['format'], $format);
		
		qa_db_post_set_content($oldcomment['postid'], $oldcomment['title'], $content, $format, $oldcomment['tags'], $notify,
			$contentchanged ? $userid : null, $contentchanged ? qa_remote_ip_address() : null);
	
		if ( ($oldcomment['type']=='C') && ($question['type']=='Q') && (($parent['type']=='Q') || ($parent['type']=='A')) ) // all must be visible
			qa_post_index($oldcomment['postid'], 'C', $question['postid'], $oldcomment['parentid'], null, $content, $format, $text, null, $oldcomment['categoryid']);

		qa_report_event('c_edit', $userid, $handle, $cookieid, array(
			'postid' => $oldcomment['postid'],
			'parentid' => $oldcomment['parentid'],
			'parenttype' => $parent['basetype'],
			'questionid' => $question['postid'],
			'content' => $content,
			'format' => $format,
			'text' => $text,
			'oldcontent' => $oldcomment['content'],
			'oldformat' => $oldcomment['format'],
			'oldcomment' => $oldcomment,
			'contentchanged' => $contentchanged,
		));
	}

	
	function qa_answer_to_comment($oldanswer, $parentid, $content, $format, $text, $notify, $userid, $handle, $cookieid, $question, $answers, $commentsfollows)
/*
	Convert an answer to a comment (application level) and set its fields to $content, $format and $notify.
	Pass the answer's database record before changes in $oldanswer, the new comment's $parentid to be, details of the
	user doing this in $userid, $handle and $cookieid, the antecedent question's record in $question, the records for
	all answers to that question in $answers, and the records for all comments on the (old) answer and questions
	following from the (old) answer in $commentsfollows ($commentsfollows can also contain other records which are ignored).
	Handles indexing (based on $text), user points, cached counts and event reports.
*/
	{
		$parent=isset($answers[$parentid]) ? $answers[$parentid] : $question;
			
		qa_post_unindex($oldanswer['postid']);
		
		$contentchanged=strcmp($oldanswer['content'], $content) || strcmp($oldanswer['format'], $format);
		
		qa_db_post_set_type($oldanswer['postid'], substr_replace($oldanswer['type'], 'C', 0, 1), $userid, qa_remote_ip_address(), QA_UPDATE_TYPE);
		qa_db_post_set_parent($oldanswer['postid'], $parentid);
		qa_db_post_set_content($oldanswer['postid'], $oldanswer['title'], $content, $format, $oldanswer['tags'], $notify,
			$contentchanged ? $userid : null, $contentchanged ? qa_remote_ip_address() : null);
		
		foreach ($commentsfollows as $commentfollow)
			if ($commentfollow['parentid']==$oldanswer['postid']) // do same thing for comments and follows
				qa_db_post_set_parent($commentfollow['postid'], $parentid);

		qa_db_points_update_ifuser($oldanswer['userid'], array('aposts', 'aselecteds', 'cposts'));

		qa_db_post_acount_update($question['postid']);
		qa_db_hotness_update($question['postid']);
		qa_db_acount_update();
		qa_db_ccount_update();
		qa_db_unaqcount_update();
		qa_db_unupaqcount_update();
	
		if ( ($oldanswer['type']=='A') && ($question['type']=='Q') && (($parent['type']=='Q') || ($parent['type']=='A')) ) // only if all fully visible
			qa_post_index($oldanswer['postid'], 'C', $question['postid'], $parentid, null, $content, $format, $text, null, $oldanswer['categoryid']);

		qa_report_event('a_to_c', $userid, $handle, $cookieid, array(
			'postid' => $oldanswer['postid'],
			'parentid' => $parentid,
			'parenttype' => $parent['basetype'],
			'questionid' => $question['postid'],
			'content' => $content,
			'format' => $format,
			'text' => $text,
			'oldcontent' => $oldanswer['content'],
			'oldformat' => $oldanswer['format'],
			'oldanswer' => $oldanswer,
			'contentchanged' => $contentchanged,
		));
	}

	
	function qa_comment_set_hidden($oldcomment, $hidden, $userid, $handle, $cookieid, $question, $parent)
/*
	Set the hidden status (application level) of $oldcomment to $hidden. Pass the antecedent question's record in $question,
	details of the user doing this in $userid, $handle and $cookieid, and the answer's database record in $answer if this
	is a comment on an answer, otherwise null. Handles indexing, user points, cached counts and event reports.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		if (!isset($parent))
			$parent=$question; // for backwards compatibility with old answer parameter
		
		$wasqueued=($oldcomment['type']=='C_QUEUED');
		
		qa_post_unindex($oldcomment['postid']);
		
		$setupdated=$hidden || !$wasqueued; // don't record approval of a post as an update action
		
		qa_db_post_set_type($oldcomment['postid'], $hidden ? 'C_HIDDEN' : 'C',
			$setupdated ? $userid : null, $setupdated ? qa_remote_ip_address() : null, QA_UPDATE_VISIBLE);

		qa_db_points_update_ifuser($oldcomment['userid'], array('cposts'));
		qa_db_ccount_update();
		
		if ( ($question['type']=='Q') && (($parent['type']=='Q') || ($parent['type']=='A')) && !$hidden) // only index if none of the things it depends on are hidden or queued
			qa_post_index($oldcomment['postid'], 'C', $question['postid'], $oldcomment['parentid'], null, $oldcomment['content'],
				$oldcomment['format'], qa_viewer_text($oldcomment['content'], $oldcomment['format']), null, $oldcomment['categoryid']);

		qa_report_event($wasqueued ? ($hidden ? 'c_reject' : 'c_approve') : ($hidden ? 'c_hide' : 'c_reshow'), $userid, $handle, $cookieid, array(
			'postid' => $oldcomment['postid'],
			'parentid' => $oldcomment['parentid'],
			'oldcomment' => $oldcomment,
			'parenttype' => $parent['basetype'],
			'questionid' => $question['postid'],
		));
		
		if ($wasqueued && !$hidden) {
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
			$commentsfollows=qa_db_single_select(qa_db_full_child_posts_selectspec(null, $oldcomment['parentid']));			
			$thread=array();
			
			foreach ($commentsfollows as $comment)
				if (($comment['type']=='C') && ($comment['parentid']==$parent['postid']))
					$thread[]=$comment;
				
			qa_report_event('c_post', $oldcomment['userid'], $oldcomment['handle'], $oldcomment['cookieid'], array(
				'postid' => $oldcomment['postid'],
				'parentid' => $oldcomment['parentid'],
				'parenttype' => $parent['basetype'],
				'parent' => $parent,
				'questionid' => $question['postid'],
				'question' => $question,
				'thread' => $thread,
				'content' => $oldcomment['content'],
				'format' => $oldcomment['format'],
				'text' => qa_viewer_text($oldcomment['content'], $oldcomment['format']),
				'categoryid' => $oldcomment['categoryid'],
				'notify' => isset($oldcomment['notify']),
				'email' => qa_email_validate($oldcomment['notify']) ? $oldcomment['notify'] : null,
				'delayed' => $oldcomment['created'],
			));
		}
	}

	
	function qa_comment_delete($oldcomment, $question, $parent, $userid, $handle, $cookieid)
/*
	Permanently delete a comment in $oldcomment (application level) from the database. Pass the database question in $question
	and the answer's database record in $answer if this is a comment on an answer, otherwise null. Pass details of the user
	doing this in $userid, $handle and $cookieid. Handles unindexing, points, cached counts and event reports.
	See qa-app-posts.php for a higher-level function which is easier to use.
*/
	{
		if (!isset($parent))
			$parent=$question; // for backwards compatibility with old answer parameter
		
		if ($oldcomment['type']!='C_HIDDEN')
			qa_fatal_error('Tried to delete a non-hidden comment');
		
		qa_post_unindex($oldcomment['postid']);
		qa_db_post_delete($oldcomment['postid']);
		qa_db_points_update_ifuser($oldcomment['userid'], array('cposts'));
		qa_db_ccount_update();

		qa_report_event('c_delete', $userid, $handle, $cookieid, array(
			'postid' => $oldcomment['postid'],
			'parentid' => $oldcomment['parentid'],
			'oldcomment' => $oldcomment,
			'parenttype' => $parent['basetype'],
			'questionid' => $question['postid'],
		));
	}

	
	function qa_comment_set_userid($oldcomment, $userid, $handle, $cookieid)
/*
	Set the author (application level) of $oldcomment to $userid and also pass $handle and $cookieid
	of user. Updates points and reports events as appropriate.
*/
	{
		qa_db_post_set_userid($oldcomment['postid'], $userid);
		
		qa_db_points_update_ifuser($oldcomment['userid'], array('cposts'));
		qa_db_points_update_ifuser($userid, array('cposts'));

		qa_report_event('c_claim', $userid, $handle, $cookieid, array(
			'postid' => $oldcomment['postid'],
			'parentid' => $oldcomment['parentid'],
			'oldcomment' => $oldcomment,
		));
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/