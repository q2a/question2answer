<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database-level access to votes tables


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
 * Set the vote for $userid on $postid to $vote in the database
 * @param int $postid
 * @param mixed $userid
 * @param int $vote
 */
function qa_db_uservote_set($postid, $userid, $vote)
{
	$vote = max(min(($vote), 1), -1);

	qa_db_query_sub(
		'INSERT INTO ^uservotes (postid, userid, vote, flag, votecreated) VALUES (#, #, #, 0, NOW()) ON DUPLICATE KEY UPDATE vote=#, voteupdated=NOW()',
		$postid, $userid, $vote, $vote
	);
}


/**
 * Get the vote for $userid on $postid from the database (or NULL if none)
 * @param int $postid
 * @param mixed $userid
 * @return string|null
 */
function qa_db_uservote_get($postid, $userid)
{
	return qa_db_read_one_value(qa_db_query_sub(
		'SELECT vote FROM ^uservotes WHERE postid=# AND userid=#',
		$postid, $userid
	), true);
}


/**
 * Set the flag for $userid on $postid to $flag (true or false) in the database
 * @param int $postid
 * @param mixed $userid
 * @param bool $flag
 */
function qa_db_userflag_set($postid, $userid, $flag)
{
	$flag = (int)$flag;

	qa_db_query_sub(
		'INSERT INTO ^uservotes (postid, userid, vote, flag) VALUES (#, #, 0, #) ON DUPLICATE KEY UPDATE flag=#',
		$postid, $userid, $flag, $flag
	);
}


/**
 * Clear all flags for $postid in the database
 * @param int $postid
 */
function qa_db_userflags_clear_all($postid)
{
	qa_db_query_sub(
		'UPDATE ^uservotes SET flag=0 WHERE postid=#',
		$postid
	);
}


/**
 * Recalculate the cached count of upvotes, downvotes and netvotes for $postid in the database
 * @param int $postid
 */
function qa_db_post_recount_votes($postid)
{
	if (qa_should_update_counts()) {
		qa_db_query_sub(
			'UPDATE ^posts AS x, (SELECT COALESCE(SUM(GREATEST(0,vote)),0) AS upvotes, -COALESCE(SUM(LEAST(0,vote)),0) AS downvotes FROM ^uservotes WHERE postid=#) AS a SET x.upvotes=a.upvotes, x.downvotes=a.downvotes, x.netvotes=a.upvotes-a.downvotes WHERE x.postid=#',
			$postid, $postid
		);
	}
}


/**
 * Recalculate the cached count of flags for $postid in the database
 * @param int $postid
 */
function qa_db_post_recount_flags($postid)
{
	if (qa_should_update_counts()) {
		qa_db_query_sub(
			'UPDATE ^posts AS x, (SELECT COALESCE(SUM(IF(flag, 1, 0)),0) AS flagcount FROM ^uservotes WHERE postid=#) AS a SET x.flagcount=a.flagcount WHERE x.postid=#',
			$postid, $postid
		);
	}
}


/**
 * Returns all non-zero votes on post $postid from the database as an array of [userid] => [vote]
 * @param int $postid
 * @return array
 */
function qa_db_uservote_post_get($postid)
{
	return qa_db_read_all_assoc(qa_db_query_sub(
		'SELECT userid, vote FROM ^uservotes WHERE postid=# AND vote!=0',
		$postid
	), 'userid', 'vote');
}


/**
 * Returns all the postids from the database for posts that $userid has voted on or flagged
 * @param mixed $userid
 * @return array
 */
function qa_db_uservoteflag_user_get($userid)
{
	return qa_db_read_all_values(qa_db_query_sub(
		'SELECT postid FROM ^uservotes WHERE userid=# AND (vote!=0 OR flag!=0)',
		$userid
	));
}


/**
 * Return information about all the non-zero votes and/or flags on the posts in postids, including user handles for internal user management
 * @param array $postids
 * @return array
 */
function qa_db_uservoteflag_posts_get($postids)
{
	if (QA_FINAL_EXTERNAL_USERS) {
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT postid, userid, vote, flag, votecreated, voteupdated FROM ^uservotes WHERE postid IN (#) AND (vote!=0 OR flag!=0)',
			$postids
		));
	} else {
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT postid, handle, vote, flag, votecreated, voteupdated FROM ^uservotes LEFT JOIN ^users ON ^uservotes.userid=^users.userid WHERE postid IN (#) AND (vote!=0 OR flag!=0)',
			$postids
		));
	}
}


/**
 * Remove all votes assigned to a post that had been cast by the owner of the post.
 * @param int $postid The post ID from which the owner's votes will be removed.
 */
function qa_db_uservote_remove_own($postid)
{
	qa_db_query_sub(
		'DELETE uv FROM ^uservotes uv JOIN ^posts p ON uv.postid=p.postid AND uv.userid=p.userid WHERE uv.postid=#', $postid
	);
}
