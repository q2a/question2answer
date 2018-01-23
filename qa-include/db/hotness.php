<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Functions for dealing with question hotness in the database


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
 * Increment the views counter for the post (if different IP from last view).
 * @param  int $postid The ID of the post
 * @return bool Whether views were actually incremented.
 */
function qa_db_increment_views($postid)
{
	$query = 'UPDATE ^posts SET views=views+1, lastviewip=UNHEX($) WHERE postid=# AND (lastviewip IS NULL OR lastviewip!=UNHEX($))';
	$ipHex = bin2hex(@inet_pton(qa_remote_ip_address()));

	qa_db_query_sub($query, $ipHex, $postid, $ipHex);

	return qa_db_affected_rows() > 0;
}


/**
 * Recalculate the hotness in the database for one or more posts.
 *
 * @param int $firstpostid First post to recalculate (or only post if $lastpostid is null).
 * @param int $lastpostid Last post in the range to recalculate.
 * @param bool $viewincrement Deprecated - view counter is now incremented separately. Previously, would increment the post's
 *   views and include that in the hotness calculation.
 * @return void
 */
function qa_db_hotness_update($firstpostid, $lastpostid = null, $viewincrement = false)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	if (!qa_should_update_counts()) {
		return;
	}

	if (!isset($lastpostid))
		$lastpostid = $firstpostid;

	$query = "UPDATE ^posts AS x, (SELECT parents.postid, MAX(parents.created) AS qcreated, COALESCE(MAX(children.created), MAX(parents.created)) as acreated, COUNT(children.postid) AS acount, MAX(parents.netvotes) AS netvotes, MAX(parents.views) AS views FROM ^posts AS parents LEFT JOIN ^posts AS children ON parents.postid=children.parentid AND children.type='A' WHERE parents.postid BETWEEN # AND # GROUP BY postid) AS a SET x.hotness=(" .
		'((TO_DAYS(a.qcreated)-734138)*86400.0+TIME_TO_SEC(a.qcreated))*# + ' . // zero-point is Jan 1, 2010
		'((TO_DAYS(a.acreated)-734138)*86400.0+TIME_TO_SEC(a.acreated))*# + ' .
		'(a.acount+0.0)*# + ' .
		'(a.netvotes+0.0)*# + ' .
		'(a.views+0.0)*#' .
		') WHERE x.postid=a.postid';

	// Additional multiples based on empirical analysis of activity on Q2A meta site to give approx equal influence for all factors

	$arguments = array(
		$firstpostid,
		$lastpostid,
		qa_opt('hot_weight_q_age'),
		qa_opt('hot_weight_a_age'),
		qa_opt('hot_weight_answers') * 160000,
		qa_opt('hot_weight_votes') * 160000,
		qa_opt('hot_weight_views') * 4000,
	);

	qa_db_query_raw(qa_db_apply_sub($query, $arguments));
}
