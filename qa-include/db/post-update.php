<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

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
	header('Location: ../../');
	exit;
}


require_once QA_INCLUDE_DIR . 'app/updates.php';


/**
 * Update the selected answer in the database for $questionid to $selchildid, and optionally record that $lastuserid did it from $lastip
 * @param int $questionid
 * @param int|null $selchildid
 * @param mixed|null $lastuserid
 * @param string|null $lastip
 */
function qa_db_post_set_selchildid($questionid, $selchildid, $lastuserid = null, $lastip = null)
{
	qa_db_query_sub(
		"UPDATE ^posts AS x, (SELECT selchildid FROM ^posts WHERE postid=#) AS a " .
		"SET x.updated=NULL, x.updatetype=NULL, x.lastuserid=NULL, x.lastip=NULL WHERE " . // if previous answer's last edit was to be selected, remove that
		"x.postid=a.selchildid AND x.updatetype=$",
		$questionid, QA_UPDATE_SELECTED
	);

	qa_db_query_sub(
		'UPDATE ^posts SET selchildid=# WHERE postid=#',
		$selchildid, $questionid
	);

	if (isset($selchildid) && isset($lastuserid) && isset($lastip)) {
		qa_db_query_sub(
			"UPDATE ^posts SET updated=NOW(), updatetype=$, lastuserid=$, lastip=UNHEX($) WHERE postid=#",
			QA_UPDATE_SELECTED, $lastuserid, bin2hex(@inet_pton($lastip)), $selchildid
		);
	}
}


/**
 * Set $questionid to be closed by post $closedbyid (null if not closed) in the database, and optionally record that
 * $lastuserid did it from $lastip
 * @param int $questionid
 * @param int $closedbyid
 * @param mixed|null $lastuserid
 * @param string|null $lastip
 */
function qa_db_post_set_closed($questionid, $closedbyid, $lastuserid = null, $lastip = null)
{
	if (isset($lastuserid) || isset($lastip)) {
		qa_db_query_sub(
			"UPDATE ^posts SET closedbyid=#, updated=NOW(), updatetype=$, lastuserid=$, lastip=UNHEX($) WHERE postid=#",
			$closedbyid, QA_UPDATE_CLOSED, $lastuserid, bin2hex(@inet_pton($lastip)), $questionid
		);
	} else {
		qa_db_query_sub(
			'UPDATE ^posts SET closedbyid=# WHERE postid=#',
			$closedbyid, $questionid
		);
	}
}


/**
 * Set the type in the database of $postid to $type, and optionally record that $lastuserid did it from $lastip
 * @param int $postid
 * @param string $type
 * @param mixed|null $lastuserid
 * @param string|null $lastip
 * @param string $updatetype
 */
function qa_db_post_set_type($postid, $type, $lastuserid = null, $lastip = null, $updatetype = QA_UPDATE_TYPE)
{
	if (isset($lastuserid) || isset($lastip)) {
		qa_db_query_sub(
			'UPDATE ^posts SET type=$, updated=NOW(), updatetype=$, lastuserid=$, lastip=UNHEX($) WHERE postid=#',
			$type, $updatetype, $lastuserid, bin2hex(@inet_pton($lastip)), $postid
		);
	} else {
		qa_db_query_sub(
			'UPDATE ^posts SET type=$ WHERE postid=#',
			$type, $postid
		);
	}
}


/**
 * Set the parent in the database of $postid to $parentid, and optionally record that $lastuserid did it from $lastip
 * (if at least one is specified)
 * @param int $postid
 * @param int $parentid
 * @param mixed|null $lastuserid
 * @param string|null $lastip
 */
function qa_db_post_set_parent($postid, $parentid, $lastuserid = null, $lastip = null)
{
	if (isset($lastuserid) || isset($lastip)) {
		qa_db_query_sub(
			"UPDATE ^posts SET parentid=#, updated=NOW(), updatetype=$, lastuserid=$, lastip=UNHEX($) WHERE postid=#",
			$parentid, QA_UPDATE_PARENT, $lastuserid, bin2hex(@inet_pton($lastip)), $postid
		);
	} else {
		qa_db_query_sub(
			'UPDATE ^posts SET parentid=# WHERE postid=#',
			$parentid, $postid
		);
	}
}


/**
 * Set the text fields in the database of $postid to $title, $content, $tagstring, $notify and $name, and record that
 * $lastuserid did it from $lastip (if at least one is specified) with $updatetype. For backwards compatibility if $name
 * is null then the name will not be changed.
 * @param int $postid
 * @param string $title
 * @param string $content
 * @param string $format
 * @param string $tagstring
 * @param bool $notify
 * @param mixed|null $lastuserid
 * @param string|null $lastip
 * @param string $updatetype
 * @param string|null $name
 */
function qa_db_post_set_content($postid, $title, $content, $format, $tagstring, $notify, $lastuserid = null, $lastip = null, $updatetype = QA_UPDATE_CONTENT, $name = null)
{
	if (isset($lastuserid) || isset($lastip)) {
		// use COALESCE() for name since $name=null means it should not be modified (for backwards compatibility)
		qa_db_query_sub(
			'UPDATE ^posts SET title=$, content=$, format=$, tags=$, name=COALESCE($, name), notify=$, updated=NOW(), updatetype=$, lastuserid=$, lastip=UNHEX($) WHERE postid=#',
			$title, $content, $format, $tagstring, $name, $notify, $updatetype, $lastuserid, bin2hex(@inet_pton($lastip)), $postid
		);
	} else {
		qa_db_query_sub(
			'UPDATE ^posts SET title=$, content=$, format=$, tags=$, name=COALESCE($, name), notify=$ WHERE postid=#',
			$title, $content, $format, $tagstring, $name, $notify, $postid
		);
	}
}


/**
 * Set the author in the database of $postid to $userid, and set the lastuserid to $userid as well if appropriate
 * @param int $postid
 * @param mixed $userid
 */
function qa_db_post_set_userid($postid, $userid)
{
	qa_db_query_sub(
		'UPDATE ^posts SET userid=$, lastuserid=IF(updated IS NULL, lastuserid, COALESCE(lastuserid,$)) WHERE postid=#',
		$userid, $userid, $postid
	);
}


/**
 * Set the (exact) category in the database of $postid to $categoryid, and optionally record that $lastuserid did it from
 * $lastip (if at least one is specified)
 * @param int $postid
 * @param int $categoryid
 * @param mixed|null $lastuserid
 * @param string|null $lastip
 */
function qa_db_post_set_category($postid, $categoryid, $lastuserid = null, $lastip = null)
{
	if (isset($lastuserid) || isset($lastip)) {
		qa_db_query_sub(
			"UPDATE ^posts SET categoryid=#, updated=NOW(), updatetype=$, lastuserid=$, lastip=UNHEX($) WHERE postid=#",
			$categoryid, QA_UPDATE_CATEGORY, $lastuserid, bin2hex(@inet_pton($lastip)), $postid
		);
	} else {
		qa_db_query_sub(
			'UPDATE ^posts SET categoryid=# WHERE postid=#',
			$categoryid, $postid
		);
	}
}


/**
 * Set the category path in the database of each of $postids to $path retrieved via qa_db_post_get_category_path()
 * @param array $postids
 * @param array $path
 */
function qa_db_posts_set_category_path($postids, $path)
{
	if (!empty($postids)) {
		// requires QA_CATEGORY_DEPTH=4
		qa_db_query_sub(
			'UPDATE ^posts SET categoryid=#, catidpath1=#, catidpath2=#, catidpath3=# WHERE postid IN (#)',
			$path['categoryid'], $path['catidpath1'], $path['catidpath2'], $path['catidpath3'], $postids
		);
	}
}


/**
 * Set the created date of $postid to $created, which is a unix timestamp. If created is null, set to now.
 * @param int $postid
 * @param int|null $created
 */
function qa_db_post_set_created($postid, $created)
{
	if (isset($created)) {
		qa_db_query_sub(
			'UPDATE ^posts SET created=FROM_UNIXTIME(#) WHERE postid=#',
			$created, $postid
		);
	} else {
		qa_db_query_sub(
			'UPDATE ^posts SET created=NOW() WHERE postid=#',
			$postid
		);
	}
}


/**
 * Set the last updated date of $postid to $updated, which is a unix timestamp. If updated is null, set to now.
 * @param int $postid
 * @param int|null $updated
 */
function qa_db_post_set_updated($postid, $updated)
{
	if (isset($updated)) {
		qa_db_query_sub(
			'UPDATE ^posts SET updated=FROM_UNIXTIME(#) WHERE postid=#',
			$updated, $postid
		);
	} else {
		qa_db_query_sub(
			'UPDATE ^posts SET updated=NOW() WHERE postid=#',
			$postid
		);
	}
}


/**
 * Deletes post $postid from the database (will also delete any votes on the post due to foreign key cascading)
 * @param int $postid
 */
function qa_db_post_delete($postid)
{
	qa_db_query_sub(
		'DELETE FROM ^posts WHERE postid=#',
		$postid
	);
}


/**
 * Return an array of wordids that were indexed in the database for the title of $postid
 * @param int $postid
 * @return array
 */
function qa_db_titlewords_get_post_wordids($postid)
{
	return qa_db_read_all_values(qa_db_query_sub(
		'SELECT wordid FROM ^titlewords WHERE postid=#',
		$postid
	));
}


/**
 * Remove all entries in the database index of title words for $postid
 * @param int $postid
 */
function qa_db_titlewords_delete_post($postid)
{
	qa_db_query_sub(
		'DELETE FROM ^titlewords WHERE postid=#',
		$postid
	);
}


/**
 * Return an array of wordids that were indexed in the database for the content of $postid
 * @param int $postid
 * @return array
 */
function qa_db_contentwords_get_post_wordids($postid)
{
	return qa_db_read_all_values(qa_db_query_sub(
		'SELECT wordid FROM ^contentwords WHERE postid=#',
		$postid
	));
}


/**
 * Remove all entries in the database index of content words for $postid
 * @param int $postid
 */
function qa_db_contentwords_delete_post($postid)
{
	qa_db_query_sub(
		'DELETE FROM ^contentwords WHERE postid=#',
		$postid
	);
}


/**
 * Return an array of wordids that were indexed in the database for the individual words in tags of $postid
 * @param int $postid
 * @return array
 */
function qa_db_tagwords_get_post_wordids($postid)
{
	return qa_db_read_all_values(qa_db_query_sub(
		'SELECT wordid FROM ^tagwords WHERE postid=#',
		$postid
	));
}


/**
 * Remove all entries in the database index of individual words in tags of $postid
 * @param int $postid
 */
function qa_db_tagwords_delete_post($postid)
{
	qa_db_query_sub(
		'DELETE FROM ^tagwords WHERE postid=#',
		$postid
	);
}


/**
 * Return an array of wordids that were indexed in the database for the whole tags of $postid
 * @param int $postid
 * @return array
 */
function qa_db_posttags_get_post_wordids($postid)
{
	return qa_db_read_all_values(qa_db_query_sub(
		'SELECT wordid FROM ^posttags WHERE postid=#',
		$postid
	));
}


/**
 * Remove all entries in the database index of whole tags for $postid
 * @param int $postid
 */
function qa_db_posttags_delete_post($postid)
{
	qa_db_query_sub(
		'DELETE FROM ^posttags WHERE postid=#',
		$postid
	);
}


/**
 * Return the array $postids containing only those elements which are the postid of a question in the database
 * @param array $postids
 * @return array
 */
function qa_db_posts_filter_q_postids($postids)
{
	if (!empty($postids)) {
		return qa_db_read_all_values(qa_db_query_sub(
			"SELECT postid FROM ^posts WHERE type='Q' AND postid IN (#)",
			$postids
		));
	}

	return array();
}


/**
 * Return an array of all the userids of authors of posts in the array $postids
 * @param array $postids
 * @return array
 */
function qa_db_posts_get_userids($postids)
{
	if (!empty($postids)) {
		return qa_db_read_all_values(qa_db_query_sub(
			"SELECT DISTINCT userid FROM ^posts WHERE postid IN (#) AND userid IS NOT NULL",
			$postids
		));
	}

	return array();
}


/**
 * Update the cached count of the number of flagged posts in the database
 */
function qa_db_flaggedcount_update()
{
	if (qa_should_update_counts()) {
		qa_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_flaggedcount', COUNT(*) FROM ^posts " .
			"WHERE flagcount > 0 AND type IN ('Q', 'A', 'C') " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}
