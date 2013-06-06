<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-events.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Database-level access to userevents and sharedevents tables


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


	function qa_db_event_create_for_entity($entitytype, $entityid, $questionid, $lastpostid, $updatetype, $lastuserid, $timestamp=null)
/*
	Add an event to the event streams for entity $entitytype with $entityid. The event of type $updatetype relates to
	$lastpostid whose antecedent question is $questionid, and was caused by $lastuserid. Pass a unix $timestamp for the
	event time or leave as null to use now. This will add the event both to the entity's shared stream, and the
	individual user streams for any users following the entity not via its shared stream (See long comment in
	qa-db-favorites.php). Also handles truncation.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		$updatedsql=isset($timestamp) ? ('FROM_UNIXTIME('.qa_db_argument_to_mysql($timestamp, false).')') : 'NOW()';

	//	Enter it into the appropriate shared event stream for that entity
	
		qa_db_query_sub(
			'INSERT INTO ^sharedevents (entitytype, entityid, questionid, lastpostid, updatetype, lastuserid, updated) '.
			'VALUES ($, #, #, #, $, $, '.$updatedsql.')',
			$entitytype, $entityid, $questionid, $lastpostid, $updatetype, $lastuserid
		);
		
	//	If this is for a question entity, check the shared event stream doesn't have too many entries for that question
		
		$questiontruncated=false;
		
		if ($entitytype==QA_ENTITY_QUESTION) {
			$truncate=qa_db_read_one_value(qa_db_query_sub(
				'SELECT updated FROM ^sharedevents WHERE entitytype=$ AND entityid=# AND questionid=# ORDER BY updated DESC LIMIT #,1',
				$entitytype, $entityid, $questionid, QA_DB_MAX_EVENTS_PER_Q
			), true);
			
			if (isset($truncate)) {
				qa_db_query_sub(
					'DELETE FROM ^sharedevents WHERE entitytype=$ AND entityid=# AND questionid=# AND updated<=$',
					$entitytype, $entityid, $questionid, $truncate
				);
			
				$questiontruncated=true;
			}
		}
	
	//	If we didn't truncate due to a specific question, truncate the shared event stream for its overall length
	
		if (!$questiontruncated) {
			$truncate=qa_db_read_one_value(qa_db_query_sub(
				'SELECT updated FROM ^sharedevents WHERE entitytype=$ AND entityid=$ ORDER BY updated DESC LIMIT #,1',
				$entitytype, $entityid, (int)qa_opt('max_store_user_updates')
			), true);
			
			if (isset($truncate))
				qa_db_query_sub(
					'DELETE FROM ^sharedevents WHERE entitytype=$ AND entityid=$ AND updated<=$',
					$entitytype, $entityid, $truncate
				);
		}
				
	//	See if we can identify a user who has favorited this entity, but is not using its shared event stream
	
		$randomuserid=qa_db_read_one_value(qa_db_query_sub(
			'SELECT userid FROM ^userfavorites WHERE entitytype=$ AND entityid=# AND nouserevents=0 ORDER BY RAND() LIMIT 1',
			$entitytype, $entityid
		), true);

		if (isset($randomuserid)) {
		
		//	If one was found, this means we have one or more individual event streams, so update them all
		
			qa_db_query_sub(
				'INSERT INTO ^userevents (userid, entitytype, entityid, questionid, lastpostid, updatetype, lastuserid, updated) '.
				'SELECT userid, $, #, #, #, $, $, '.$updatedsql.' FROM ^userfavorites WHERE entitytype=$ AND entityid=# AND nouserevents=0',
				$entitytype, $entityid, $questionid, $lastpostid, $updatetype, $lastuserid, $entitytype, $entityid
			);
		
		//	Now truncate the random individual event stream that was found earlier
		//	(in theory we should truncate them all, but truncation is just a 'housekeeping' activity, so it's not necessary)
	
			qa_db_user_events_truncate($randomuserid, $questionid);
		}
	}

	
	function qa_db_event_create_not_entity($userid, $questionid, $lastpostid, $updatetype, $lastuserid, $timestamp=null)
/*
	Add an event to the event stream for $userid which is not related to an entity they are following (but rather a
	notification which is relevant for them, e.g. if someone answers their question). The event of type $updatetype
	relates to $lastpostid whose antecedent question is $questionid, and was caused by $lastuserid. Pass a unix
	$timestamp for the event time or leave as null to use now. Also handles truncation of event streams.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';

		$updatedsql=isset($timestamp) ? ('FROM_UNIXTIME('.qa_db_argument_to_mysql($timestamp, false).')') : 'NOW()';
		
		qa_db_query_sub(
			"INSERT INTO ^userevents (userid, entitytype, entityid, questionid, lastpostid, updatetype, lastuserid, updated) ".
			"VALUES ($, $, 0, #, #, $, $, ".$updatedsql.")",
			$userid, QA_ENTITY_NONE, $questionid, $lastpostid, $updatetype, $lastuserid
		);
		
		qa_db_user_events_truncate($userid, $questionid);
	}

	
	function qa_db_user_events_truncate($userid, $questionid=null)
/*
	Trim the number of events in the event stream for $userid. If an event was just added for a particular question,
	pass the question's id in $questionid (to help focus the truncation).
*/
	{
	
	//	First try truncating based on there being too many events for this question
	
		$questiontruncated=false;
		
		if (isset($questionid)) {
			$truncate=qa_db_read_one_value(qa_db_query_sub(
				'SELECT updated FROM ^userevents WHERE userid=$ AND questionid=# ORDER BY updated DESC LIMIT #,1',
				$userid, $questionid, QA_DB_MAX_EVENTS_PER_Q
			), true);
			
			if (isset($truncate)) {
				qa_db_query_sub(
					'DELETE FROM ^userevents WHERE userid=$ AND questionid=# AND updated<=$',
					$userid, $questionid, $truncate
				);
			
				$questiontruncated=true;
			}
		}
		
	//	If that didn't happen, try truncating the stream in general based on its total length
		
		if (!$questiontruncated) {
			$truncate=qa_db_read_one_value(qa_db_query_sub(
				'SELECT updated FROM ^userevents WHERE userid=$ ORDER BY updated DESC LIMIT #,1',
				$userid, (int)qa_opt('max_store_user_updates')
			), true);
			
			if (isset($truncate))
				qa_db_query_sub(
					'DELETE FROM ^userevents WHERE userid=$ AND updated<=$',
					$userid, $truncate
				);
		}
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/