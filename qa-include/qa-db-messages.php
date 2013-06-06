<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-messages.php
	Version: See define()s at top of qa-include/qa-base.php
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
	Record a private message sent from $fromuserid to $touserid with $content in $format
*/
	{
		qa_db_query_sub(
			'INSERT INTO ^messages (type, fromuserid, touserid, content, format, created) VALUES ($, #, #, $, $, NOW())',
			$public ? 'PUBLIC' : 'PRIVATE', $fromuserid, $touserid, $content, $format
		);
		
		return qa_db_last_insert_id();
	}
	

	function qa_db_message_delete($messageid)
	{
		qa_db_query_sub(
			'DELETE FROM ^messages WHERE messageid=#',
			$messageid
		);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/