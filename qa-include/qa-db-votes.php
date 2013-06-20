<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-votes.php
	Version: See define()s at top of qa-include/qa-base.php
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
		header('Location: ../');
		exit;
	}


	function qa_db_uservote_set($postid, $userid, $vote)
/*
	Set the vote for $userid on $postid to $vote in the database
*/
	{
		$vote=max(min(($vote), 1), -1);
		
		qa_db_query_sub(
			'INSERT INTO ^uservotes (postid, userid, vote, flag) VALUES (#, #, #, 0) ON DUPLICATE KEY UPDATE vote=#',
			$postid, $userid, $vote, $vote
		);
	}

	
	function qa_db_uservote_get($postid, $userid)
/*
	Get the vote for $userid on $postid from the database (or NULL if none)
*/
	{
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT vote FROM ^uservotes WHERE postid=# AND userid=#',
			$postid, $userid
		), true);
	}
	
	
	function qa_db_userflag_set($postid, $userid, $flag)
/*
	Set the flag for $userid on $postid to $flag (true or false) in the database
*/
	{
		$flag=$flag ? 1 : 0;

		qa_db_query_sub(
			'INSERT INTO ^uservotes (postid, userid, vote, flag) VALUES (#, #, 0, #) ON DUPLICATE KEY UPDATE flag=#',
			$postid, $userid, $flag, $flag
		);
	}
	
	
	function qa_db_userflags_clear_all($postid)
/*
	Clear all flags for $postid in the database
*/
	{
		qa_db_query_sub(
			'UPDATE ^uservotes SET flag=0 WHERE postid=#',
			$postid
		);
	}
	
	
	function qa_db_post_recount_votes($postid)
/*
	Recalculate the cached count of upvotes, downvotes and netvotes for $postid in the database
*/
	{
		if (qa_should_update_counts())
			qa_db_query_sub(
				'UPDATE ^posts AS x, (SELECT COALESCE(SUM(GREATEST(0,vote)),0) AS upvotes, -COALESCE(SUM(LEAST(0,vote)),0) AS downvotes FROM ^uservotes WHERE postid=#) AS a SET x.upvotes=a.upvotes, x.downvotes=a.downvotes, x.netvotes=a.upvotes-a.downvotes WHERE x.postid=#',
				$postid, $postid
			);
	}
	
	
	function qa_db_post_recount_flags($postid)
/*
	Recalculate the cached count of flags for $postid in the database
*/
	{
		if (qa_should_update_counts())
			qa_db_query_sub(
				'UPDATE ^posts AS x, (SELECT COALESCE(SUM(IF(flag, 1, 0)),0) AS flagcount FROM ^uservotes WHERE postid=#) AS a SET x.flagcount=a.flagcount WHERE x.postid=#',
				$postid, $postid
			);
	}
	
	
	function qa_db_uservote_post_get($postid)
/*
	Returns all non-zero votes on post $postid from the database as an array of [userid] => [vote]
*/
	{
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT userid, vote FROM ^uservotes WHERE postid=# AND vote!=0',
			$postid
		), 'userid', 'vote');
	}
	
	
	function qa_db_uservoteflag_user_get($userid)
/*
	Returns all the postids from the database for posts that $userid has voted on or flagged
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT postid FROM ^uservotes WHERE userid=# AND (vote!=0) OR (flag!=0)',
			$userid
		));
	}
	
	
	function qa_db_uservoteflag_posts_get($postids)
/*
	Return information about all the non-zero votes and/or flags on the posts in postids, including user handles for internal user management
*/
	{
		if (QA_FINAL_EXTERNAL_USERS)
			return qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT postid, userid, vote, flag FROM ^uservotes WHERE postid IN (#) AND ((vote!=0) OR (flag!=0))',
				$postids
			));

		else
			return qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT postid, handle, vote, flag FROM ^uservotes LEFT JOIN ^users ON ^uservotes.userid=^users.userid WHERE postid IN (#) AND ((vote!=0) OR (flag!=0))',
				$postids
			));
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/