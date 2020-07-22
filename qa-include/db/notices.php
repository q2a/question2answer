<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database-level access to usernotices table


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
 * Create a notice for $userid with $content in $format and optional $tags (not displayed) and return its noticeid
 * @param mixed $userid
 * @param string $content
 * @param string $format
 * @param string|null $tags
 * @return mixed
 */
function qa_db_usernotice_create($userid, $content, $format = '', $tags = null)
{
	$db = qa_service('database');
	$db->query(
		'INSERT INTO ^usernotices (userid, content, format, tags, created) VALUES (?, ?, ?, ?, NOW())',
		[$userid, $content, $format, $tags]
	);

	return $db->lastInsertId();
}


/**
 * Delete the notice $notice which belongs to $userid
 * @param mixed $userid
 * @param int $noticeid
 */
function qa_db_usernotice_delete($userid, $noticeid)
{
	qa_service('database')->query(
		'DELETE FROM ^usernotices WHERE userid=$ AND noticeid=#',
		$userid, $noticeid
	);
}


/**
 * Return an array summarizing the notices to be displayed for $userid, including the tags (not displayed)
 * @param mixed $userid
 * @return array
 */
function qa_db_usernotices_list($userid)
{
	return qa_service('database')->query(
		'SELECT noticeid, tags, UNIX_TIMESTAMP(created) AS created FROM ^usernotices WHERE userid=? ORDER BY created',
		[$userid]
	)->fetchAllAssoc();
}
