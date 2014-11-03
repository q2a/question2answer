<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-db-messages.php
	Description: Database-level access to messages table for private message history


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


	function qa_db_message_create($fromuserid, $touserid, $content, $format, $public=false)
/*
	Record a message sent from $fromuserid to $touserid with $content in $format in the database. $public sets whether
	public (on wall) or private. Return the messageid of the row created.
*/
	{
		qa_db_query_sub(
			'INSERT INTO ^messages (type, fromuserid, touserid, content, format, created) VALUES ($, #, #, $, $, NOW())',
			$public ? 'PUBLIC' : 'PRIVATE', $fromuserid, $touserid, $content, $format
		);

		return qa_db_last_insert_id();
	}


	function qa_db_message_user_hide($messageid, $box)
/*
	Hide the message with $messageid, in $box (inbox|outbox) from the user.
*/
	{
		$field = ($box === 'inbox' ? 'tohidden' : 'fromhidden');

		qa_db_query_sub(
			"UPDATE ^messages SET $field=1 WHERE messageid=#",
			$messageid
		);
	}


	function qa_db_message_delete($messageid, $public=true)
/*
	Delete the message with $messageid from the database.
*/
	{
		// delete PM only if both sender and receiver have hidden it
		$clause = $public ? '' : ' AND fromhidden=1 AND tohidden=1';

		qa_db_query_sub(
			'DELETE FROM ^messages WHERE messageid=#'.$clause,
			$messageid
		);
	}


	function qa_db_user_recount_posts($userid)
/*
	Recalculate the cached count of wall posts for user $userid in the database
*/
	{
		if (qa_should_update_counts())
			qa_db_query_sub(
				"UPDATE ^users AS x, (SELECT COUNT(*) AS wallposts FROM ^messages WHERE touserid=# AND type='PUBLIC') AS a SET x.wallposts=a.wallposts WHERE x.userid=#",
				$userid, $userid
			);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/