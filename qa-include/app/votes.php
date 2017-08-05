<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Handling incoming votes (application level)


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
	header('Location: ../../');
	exit;
}


/**
 * Check if $userid can vote on $post, on the page $topage.
 * Return an HTML error to display if there was a problem, or false if it's OK.
 * @param $post
 * @param $vote
 * @param $userid
 * @param $topage
 * @return bool|mixed|string
 */
function qa_vote_error_html($post, $vote, $userid, $topage)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	// The 'login', 'confirm', 'limit', 'userblock' and 'ipblock' permission errors are reported to the user here.
	// Others ('approve', 'level') prevent the buttons being clickable in the first place, in qa_get_vote_view(...)

	require_once QA_INCLUDE_DIR . 'app/users.php';
	require_once QA_INCLUDE_DIR . 'app/limits.php';

	if ($post['hidden']) {
		return qa_lang_html('main/vote_disabled_hidden');
	}
	if ($post['queued']) {
		return qa_lang_html('main/vote_disabled_queued');
	}

	switch($post['basetype'])
	{
		case 'Q':
			$allowVoting = qa_opt('voting_on_qs');
			break;
		case 'A':
			$allowVoting = qa_opt('voting_on_as');
			break;
		case 'C':
			$allowVoting = qa_opt('voting_on_cs');
			break;
		default:
			$allowVoting = false;
			break;
	}

	if (!$allowVoting || (isset($post['userid']) && isset($userid) && $post['userid'] == $userid)) {
		// voting option should not have been presented (but could happen due to options change)
		return qa_lang_html('main/vote_not_allowed');
	}

	$permiterror = qa_user_post_permit_error(($post['basetype'] == 'Q') ? 'permit_vote_q' : 'permit_vote_a', $post, QA_LIMIT_VOTES);

	$errordownonly = !$permiterror && $vote < 0;
	if ($errordownonly) {
		$permiterror = qa_user_post_permit_error('permit_vote_down', $post);
	}

	switch ($permiterror) {
		case false:
			return false;
			break;

		case 'login':
			return qa_insert_login_links(qa_lang_html('main/vote_must_login'), $topage);
			break;

		case 'confirm':
			return qa_insert_login_links(qa_lang_html($errordownonly ? 'main/vote_down_must_confirm' : 'main/vote_must_confirm'), $topage);
			break;

		case 'limit':
			return qa_lang_html('main/vote_limit');
			break;

		default:
			return qa_lang_html('users/no_permission');
			break;
	}
}


/**
 * Actually set (application level) the $vote (-1/0/1) by $userid (with $handle and $cookieid) on $postid.
 * Handles user points, recounting and event reports as appropriate.
 * @param $post
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $vote
 * @return void
 */
function qa_vote_set($post, $userid, $handle, $cookieid, $vote)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/points.php';
	require_once QA_INCLUDE_DIR . 'db/hotness.php';
	require_once QA_INCLUDE_DIR . 'db/votes.php';
	require_once QA_INCLUDE_DIR . 'db/post-create.php';
	require_once QA_INCLUDE_DIR . 'app/limits.php';

	$vote = (int)min(1, max(-1, $vote));
	$oldvote = (int)qa_db_uservote_get($post['postid'], $userid);

	qa_db_uservote_set($post['postid'], $userid, $vote);
	qa_db_post_recount_votes($post['postid']);

	if (!in_array($post['basetype'], array('Q', 'A', 'C'))) {
		return;
	}

	$prefix = strtolower($post['basetype']);

	if ($prefix === 'a') {
		qa_db_post_acount_update($post['parentid']);
		qa_db_unupaqcount_update();
	}

	$columns = array();

	if ($vote > 0 || $oldvote > 0) {
		$columns[] = $prefix . 'upvotes';
	}

	if ($vote < 0 || $oldvote < 0) {
		$columns[] = $prefix . 'downvotes';
	}

	qa_db_points_update_ifuser($userid, $columns);

	qa_db_points_update_ifuser($post['userid'], array($prefix . 'voteds', 'upvoteds', 'downvoteds'));

	if ($prefix === 'q') {
		qa_db_hotness_update($post['postid']);
	}

	if ($vote < 0) {
		$event = $prefix . '_vote_down';
	} elseif ($vote > 0) {
		$event = $prefix . '_vote_up';
	} else {
		$event = $prefix . '_vote_nil';
	}

	qa_report_event($event, $userid, $handle, $cookieid, array(
		'postid' => $post['postid'],
		'userid' => $post['userid'],
		'vote' => $vote,
		'oldvote' => $oldvote,
	));
}


/**
 * Check if $userid can flag $post, on the page $topage.
 * Return an HTML error to display if there was a problem, or false if it's OK.
 * @param $post
 * @param $userid
 * @param $topage
 * @return bool|mixed|string
 */
function qa_flag_error_html($post, $userid, $topage)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	// The 'login', 'confirm', 'limit', 'userblock' and 'ipblock' permission errors are reported to the user here.
	// Others ('approve', 'level') prevent the flag button being shown, in qa_page_q_post_rules(...)

	require_once QA_INCLUDE_DIR . 'db/selects.php';
	require_once QA_INCLUDE_DIR . 'app/options.php';
	require_once QA_INCLUDE_DIR . 'app/users.php';
	require_once QA_INCLUDE_DIR . 'app/limits.php';

	if (is_array($post) && qa_opt('flagging_of_posts') &&
		(!isset($post['userid']) || !isset($userid) || $post['userid'] != $userid)
	) {
		switch (qa_user_post_permit_error('permit_flag', $post, QA_LIMIT_FLAGS)) {
			case 'login':
				return qa_insert_login_links(qa_lang_html('question/flag_must_login'), $topage);
				break;

			case 'confirm':
				return qa_insert_login_links(qa_lang_html('question/flag_must_confirm'), $topage);
				break;

			case 'limit':
				return qa_lang_html('question/flag_limit');
				break;

			default:
				return qa_lang_html('users/no_permission');
				break;

			case false:
				return false;
		}
	} else {
		return qa_lang_html('question/flag_not_allowed'); // flagging option should not have been presented
	}
}


/**
 * Set (application level) a flag by $userid (with $handle and $cookieid) on $oldpost which belongs to $question.
 * Handles recounting, admin notifications and event reports as appropriate.
 * Returns true if the post should now be hidden because it has accumulated enough flags.
 * @param $oldpost
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $question
 * @return bool
 */
function qa_flag_set_tohide($oldpost, $userid, $handle, $cookieid, $question)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/votes.php';
	require_once QA_INCLUDE_DIR . 'app/limits.php';
	require_once QA_INCLUDE_DIR . 'db/post-update.php';

	qa_db_userflag_set($oldpost['postid'], $userid, true);
	qa_db_post_recount_flags($oldpost['postid']);
	qa_db_flaggedcount_update();

	switch ($oldpost['basetype']) {
		case 'Q':
			$event = 'q_flag';
			break;

		case 'A':
			$event = 'a_flag';
			break;

		case 'C':
			$event = 'c_flag';
			break;
	}

	$post = qa_db_select_with_pending(qa_db_full_post_selectspec(null, $oldpost['postid']));

	qa_report_event($event, $userid, $handle, $cookieid, array(
		'postid' => $oldpost['postid'],
		'oldpost' => $oldpost,
		'flagcount' => $post['flagcount'],
		'questionid' => $question['postid'],
		'question' => $question,
	));

	return $post['flagcount'] >= qa_opt('flagging_hide_after') && !$post['hidden'];
}


/**
 * Clear (application level) a flag on $oldpost by $userid (with $handle and $cookieid).
 * Handles recounting and event reports as appropriate.
 * @param $oldpost
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @return mixed
 */
function qa_flag_clear($oldpost, $userid, $handle, $cookieid)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/votes.php';
	require_once QA_INCLUDE_DIR . 'app/limits.php';
	require_once QA_INCLUDE_DIR . 'db/post-update.php';

	qa_db_userflag_set($oldpost['postid'], $userid, false);
	qa_db_post_recount_flags($oldpost['postid']);
	qa_db_flaggedcount_update();

	switch ($oldpost['basetype']) {
		case 'Q':
			$event = 'q_unflag';
			break;

		case 'A':
			$event = 'a_unflag';
			break;

		case 'C':
			$event = 'c_unflag';
			break;
	}

	qa_report_event($event, $userid, $handle, $cookieid, array(
		'postid' => $oldpost['postid'],
		'oldpost' => $oldpost,
	));
}


/**
 * Clear (application level) all flags on $oldpost by $userid (with $handle and $cookieid).
 * Handles recounting and event reports as appropriate.
 * @param $oldpost
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @return mixed
 */
function qa_flags_clear_all($oldpost, $userid, $handle, $cookieid)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/votes.php';
	require_once QA_INCLUDE_DIR . 'app/limits.php';
	require_once QA_INCLUDE_DIR . 'db/post-update.php';

	qa_db_userflags_clear_all($oldpost['postid']);
	qa_db_post_recount_flags($oldpost['postid']);
	qa_db_flaggedcount_update();

	switch ($oldpost['basetype']) {
		case 'Q':
			$event = 'q_clearflags';
			break;

		case 'A':
			$event = 'a_clearflags';
			break;

		case 'C':
			$event = 'c_clearflags';
			break;
	}

	qa_report_event($event, $userid, $handle, $cookieid, array(
		'postid' => $oldpost['postid'],
		'oldpost' => $oldpost,
	));
}
