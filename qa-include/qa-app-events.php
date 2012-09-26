<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-events.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Handles the submission of events to the database (application level)


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

	require_once QA_INCLUDE_DIR.'qa-db-events.php';
	require_once QA_INCLUDE_DIR.'qa-app-updates.php';
	

	function qa_create_event_for_q_user($questionid, $lastpostid, $updatetype, $lastuserid, $otheruserid=null, $timestamp=null)
/*
	Add appropriate events to the database for an action performed on a question. The event of type $updatetype relates
	to $lastpostid whose antecedent question is $questionid, and was caused by $lastuserid. Pass a unix $timestamp for
	the event time or leave as null to use now. This will add an event to $questionid's and $lastuserid's streams. If
	$otheruserid is set, it will also add an notification-style event for that user, unless they are the one who did it.
*/
	{
		qa_db_event_create_for_entity(QA_ENTITY_QUESTION, $questionid, $questionid, $lastpostid, $updatetype, $lastuserid, $timestamp); // anyone who favorited the question

		if (isset($lastuserid) && !QA_FINAL_EXTERNAL_USERS)
			qa_db_event_create_for_entity(QA_ENTITY_USER, $lastuserid, $questionid, $lastpostid, $updatetype, $lastuserid, $timestamp); // anyone who favorited the user who did it

		if (isset($otheruserid) && ($otheruserid!=$lastuserid))
			qa_db_event_create_not_entity($otheruserid, $questionid, $lastpostid, $updatetype, $lastuserid, $timestamp); // possible other user to be informed
	}
	

	function qa_create_event_for_tags($tagstring, $questionid, $updatetype, $lastuserid, $timestamp=null)
/*
	Add appropriate events to the database for an action performed on a set of tags in $tagstring (namely, a question
	being created with those tags or having one of those tags added afterwards). The event of type $updatetype relates
	to the question $questionid, and was caused by $lastuserid. Pass a unix $timestamp for the event time or leave as
	null to use now.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		require_once QA_INCLUDE_DIR.'qa-db-post-create.php';

		$tagwordids=qa_db_word_mapto_ids(array_unique(qa_tagstring_to_tags($tagstring)));
		foreach ($tagwordids as $wordid)
			qa_db_event_create_for_entity(QA_ENTITY_TAG, $wordid, $questionid, $questionid, $updatetype, $lastuserid, $timestamp);
	}
	

	function qa_create_event_for_category($categoryid, $questionid, $updatetype, $lastuserid, $timestamp=null)
/*
	Add appropriate events to the database for an action performed on $categoryid (namely, a question being created in
	that category or being moved to it later on), along with all of its ancestor categories. The event of type
	$updatetype relates to the question $questionid, and was caused by $lastuserid. Pass a unix $timestamp for the event
	time or leave as null to use now.
*/
	{
		if (isset($categoryid)) {
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
			$categories=qa_category_path(qa_db_single_select(qa_db_category_nav_selectspec($categoryid, true)), $categoryid);
			foreach ($categories as $category)
				qa_db_event_create_for_entity(QA_ENTITY_CATEGORY, $category['categoryid'], $questionid, $questionid, $updatetype, $lastuserid, $timestamp);
		}
	}
	
	
/*
	Omit PHP closing tag to help avoid accidental output
*/