<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-post-update.php
	Version: See define()s at top of qa-include/qa-base.php
	Description:  Database functions for changing a question, answer or comment


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


	require_once QA_INCLUDE_DIR.'qa-app-updates.php';
	
	
	function qa_db_post_set_selchildid($questionid, $selchildid, $lastuserid=null, $lastip=null)
/*
	Update the selected answer in the database for $questionid to $selchildid, and optionally record that $lastuserid did it from $lastip
*/
	{
		qa_db_query_sub(
			"UPDATE ^posts AS x, (SELECT selchildid FROM ^posts WHERE postid=#) AS a ".
			"SET x.updated=NULL, x.updatetype=NULL, x.lastuserid=NULL, x.lastip=NULL WHERE ". // if previous answer's last edit was to be selected, remove that
			"x.postid=a.selchildid AND x.updatetype=$",
			$questionid, QA_UPDATE_SELECTED
		);
		
		qa_db_query_sub(
			'UPDATE ^posts SET selchildid=# WHERE postid=#',
			$selchildid, $questionid
		);
		
		if (isset($selchildid) && isset($lastuserid) && isset($lastip))
			qa_db_query_sub(
				"UPDATE ^posts SET updated=NOW(), updatetype=$, lastuserid=$, lastip=INET_ATON($) WHERE postid=#",
				QA_UPDATE_SELECTED, $lastuserid, $lastip, $selchildid
			);
	}
	
	
	function qa_db_post_set_closed($questionid, $closedbyid, $lastuserid=null, $lastip=null)
/*
	Set $questionid to be closed by post $closedbyid (null if not closed) in the database, and optionally record that
	$lastuserid did it from $lastip
*/
	{
		if (isset($lastuserid) || isset($lastip)) {
			qa_db_query_sub(
				"UPDATE ^posts SET closedbyid=#, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET_ATON($) WHERE postid=#",
				$closedbyid, QA_UPDATE_CLOSED, $lastuserid, $lastip, $questionid
			);
		} else
			qa_db_query_sub(
				'UPDATE ^posts SET closedbyid=# WHERE postid=#',
				$closedbyid, $questionid
			);
	}

	
	function qa_db_post_set_type($postid, $type, $lastuserid=null, $lastip=null, $updatetype=QA_UPDATE_TYPE)
/*
	Set the type in the database of $postid to $type, and optionally record that $lastuserid did it from $lastip
*/
	{
		if (isset($lastuserid) || isset($lastip)) {
			qa_db_query_sub(
				'UPDATE ^posts SET type=$, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET_ATON($) WHERE postid=#',
				$type, $updatetype, $lastuserid, $lastip, $postid
			);
		} else
			qa_db_query_sub(
				'UPDATE ^posts SET type=$ WHERE postid=#',
				$type, $postid
			);
	}

	
	function qa_db_post_set_parent($postid, $parentid, $lastuserid=null, $lastip=null)
/*
	Set the parent in the database of $postid to $parentid, and optionally record that $lastuserid did it from $lastip (if at least one is specified)
*/
	{
		if (isset($lastuserid) || isset($lastip))
			qa_db_query_sub(
				"UPDATE ^posts SET parentid=#, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET_ATON($) WHERE postid=#",
				$parentid, QA_UPDATE_PARENT, $lastuserid, $lastip, $postid
			);
		else
			qa_db_query_sub(
				'UPDATE ^posts SET parentid=# WHERE postid=#',
				$parentid, $postid
			);
	}

	
	function qa_db_post_set_content($postid, $title, $content, $format, $tagstring, $notify, $lastuserid=null, $lastip=null, $updatetype=QA_UPDATE_CONTENT, $name=null)
/*
	Set the text fields in the database of $postid to $title, $content, $tagstring, $notify and $name, and record that
	$lastuserid did it from $lastip (if at least one is specified) with $updatetype. or backwards compatibility if $name
	is null then the name will not be changed.
*/
	{
		if (isset($lastuserid) || isset($lastip)) // use COALESCE() for name since $name=null means it should not be modified (for backwards compatibility)
			qa_db_query_sub(
				'UPDATE ^posts SET title=$, content=$, format=$, tags=$, name=COALESCE($, name), notify=$, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET_ATON($) WHERE postid=#',
				$title, $content, $format, $tagstring, $name, $notify, $updatetype, $lastuserid, $lastip, $postid
			);
		else
			qa_db_query_sub(
				'UPDATE ^posts SET title=$, content=$, format=$, tags=$, name=COALESCE($, name), notify=$ WHERE postid=#',
				$title, $content, $format, $tagstring, $name, $notify, $postid
			);
	}

	
	function qa_db_post_set_userid($postid, $userid)
/*
	Set the author in the database of $postid to $userid, and set the lastuserid to $userid as well if appropriate
*/
	{
		qa_db_query_sub(
			'UPDATE ^posts SET userid=$, lastuserid=IF(updated IS NULL, lastuserid, COALESCE(lastuserid,$)) WHERE postid=#',
			$userid, $userid, $postid
		);
	}
	
	
	function qa_db_post_set_category($postid, $categoryid, $lastuserid=null, $lastip=null)
/*
	Set the (exact) category in the database of $postid to $categoryid, and optionally record that $lastuserid did it from $lastip (if at least one is specified)
*/
	{
		if (isset($lastuserid) || isset($lastip))
			qa_db_query_sub(
				"UPDATE ^posts SET categoryid=#, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET_ATON($) WHERE postid=#",
				$categoryid, QA_UPDATE_CATEGORY, $lastuserid, $lastip, $postid
			);
		else
			qa_db_query_sub(
				'UPDATE ^posts SET categoryid=# WHERE postid=#',
				$categoryid, $postid
			);
	}
	
	
	function qa_db_posts_set_category_path($postids, $path)
/*
	Set the category path in the database of each of $postids to $path retrieved via qa_db_post_get_category_path()
*/
	{
		if (count($postids))
			qa_db_query_sub(
				'UPDATE ^posts SET categoryid=#, catidpath1=#, catidpath2=#, catidpath3=# WHERE postid IN (#)',
				$path['categoryid'], $path['catidpath1'], $path['catidpath2'], $path['catidpath3'], $postids
			); // requires QA_CATEGORY_DEPTH=4
	}
	
	
	function qa_db_post_set_created($postid, $created)
/*
	Set the created date of $postid to $created, which is a unix timestamp. If created is null, set to now.
*/
	{
		if (isset($created))
			qa_db_query_sub(
				'UPDATE ^posts SET created=FROM_UNIXTIME(#) WHERE postid=#',
				$created, $postid
			);
		else
			qa_db_query_sub(
				'UPDATE ^posts SET created=NOW() WHERE postid=#',
				$postid
			);
	}
	
	
	function qa_db_post_set_updated($postid, $updated)
/*
	Set the last updated date of $postid to $updated, which is a unix timestamp. If updated is nul, set to now.
*/
	{
		if (isset($updated))
			qa_db_query_sub(
				'UPDATE ^posts SET updated=FROM_UNIXTIME(#) WHERE postid=#',
				$updated, $postid
			);
		else
			qa_db_query_sub(
				'UPDATE ^posts SET updated=NOW() WHERE postid=#',
				$postid
			);
	}
	
	
	function qa_db_post_delete($postid)
/*
	Deletes post $postid from the database (will also delete any votes on the post due to foreign key cascading)
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^posts WHERE postid=#',
			$postid
		);
	}

	
	function qa_db_titlewords_get_post_wordids($postid)
/*
	Return an array of wordids that were indexed in the database for the title of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT wordid FROM ^titlewords WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_titlewords_delete_post($postid)
/*
	Remove all entries in the database index of title words for $postid
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^titlewords WHERE postid=#',
			$postid
		);
	}


	function qa_db_contentwords_get_post_wordids($postid)
/*
	Return an array of wordids that were indexed in the database for the content of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT wordid FROM ^contentwords WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_contentwords_delete_post($postid)
/*
	Remove all entries in the database index of content words for $postid
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^contentwords WHERE postid=#',
			$postid
		);
	}
	
	
	function qa_db_tagwords_get_post_wordids($postid)
/*
	Return an array of wordids that were indexed in the database for the individual words in tags of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT wordid FROM ^tagwords WHERE postid=#',
			$postid
		));
	}
	
	
	function qa_db_tagwords_delete_post($postid)
/*
	Remove all entries in the database index of individual words in tags of $postid
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^tagwords WHERE postid=#',
			$postid
		);
	}


	function qa_db_posttags_get_post_wordids($postid)
/*
	Return an array of wordids that were indexed in the database for the whole tags of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT wordid FROM ^posttags WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_posttags_delete_post($postid)
/*
	Remove all entries in the database index of whole tags for $postid
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^posttags WHERE postid=#',
			$postid
		);
	}
	
	
	function qa_db_posts_filter_q_postids($postids)
/*
	Return the array $postids containing only those elements which are the postid of a qustion in the database
*/
	{
		if (count($postids))
			return qa_db_read_all_values(qa_db_query_sub(
				"SELECT postid FROM ^posts WHERE type='Q' AND postid IN (#)",
				$postids
			));
		else
			return array();
	}
	
	
	function qa_db_posts_get_userids($postids)
/*
	Return an array of all the userids of authors of posts in the array $postids
*/
	{
		if (count($postids))
			return qa_db_read_all_values(qa_db_query_sub(
				"SELECT DISTINCT userid FROM ^posts WHERE postid IN (#) AND userid IS NOT NULL",
				$postids
			));
		else
			return array();
	}

	
	function qa_db_flaggedcount_update()
/*
	Update the cached count of the number of flagged posts in the database
*/
	{
		if (qa_should_update_counts())
			qa_db_query_sub("REPLACE ^options (title, content) SELECT 'cache_flaggedcount', COUNT(*) FROM ^posts WHERE flagcount>0 AND type IN ('Q', 'A', 'C')");
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/