<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-event-updates.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Event module for maintaining events tables


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


	class qa_event_updates {

		function process_event($event, $userid, $handle, $cookieid, $params)
		{
			if (@$params['silent']) // don't create updates about silent edits, and possibly other silent events in future
				return;
				
			require_once QA_INCLUDE_DIR.'qa-db-events.php';
			require_once QA_INCLUDE_DIR.'qa-app-events.php';

			switch ($event) {
				case 'q_post':
					if (isset($params['parent'])) // question is following an answer
						qa_create_event_for_q_user($params['parent']['parentid'], $params['postid'], QA_UPDATE_FOLLOWS, $userid, $params['parent']['userid']);
					
					qa_create_event_for_q_user($params['postid'], $params['postid'], null, $userid);
					qa_create_event_for_tags($params['tags'], $params['postid'], null, $userid);
					qa_create_event_for_category($params['categoryid'], $params['postid'], null, $userid);
					break;
					

				case 'a_post':
					qa_create_event_for_q_user($params['parentid'], $params['postid'], null, $userid, $params['parent']['userid']);
					break;
					

				case 'c_post':
					$keyuserids=array();
					
					foreach ($params['thread'] as $comment) // previous comments in thread (but not author of parent again)
						if (isset($comment['userid']))
							$keyuserids[$comment['userid']]=true;
							
					foreach ($keyuserids as $keyuserid => $dummy)
						if ($keyuserid != $userid)
							qa_db_event_create_not_entity($keyuserid, $params['questionid'], $params['postid'], QA_UPDATE_FOLLOWS, $userid);

					switch ($params['parent']['basetype'])
					{
						case 'Q':
							$updatetype=QA_UPDATE_C_FOR_Q;
							break;
							
						case 'A':
							$updatetype=QA_UPDATE_C_FOR_A;
							break;
							
						default:
							$updatetype=null;
							break;
					}
					
					qa_create_event_for_q_user($params['questionid'], $params['postid'], $updatetype, $userid,
						@$keyuserids[$params['parent']['userid']] ? null : $params['parent']['userid']);
							// give precedence to 'your comment followed' rather than 'your Q/A commented' if both are true	
					break;

				
				case 'q_edit':
					if ($params['titlechanged'] || $params['contentchanged'])
						$updatetype=QA_UPDATE_CONTENT;
					elseif ($params['tagschanged'])
						$updatetype=QA_UPDATE_TAGS;
					else
						$updatetype=null;
						
					if (isset($updatetype)) {
						qa_create_event_for_q_user($params['postid'], $params['postid'], $updatetype, $userid, $params['oldquestion']['userid']);

						if ($params['tagschanged'])
							qa_create_event_for_tags($params['tags'], $params['postid'], QA_UPDATE_TAGS, $userid);
					}
					break;

					
				case 'a_select':
					qa_create_event_for_q_user($params['parentid'], $params['postid'], QA_UPDATE_SELECTED, $userid, $params['answer']['userid']);
					break;

					
				case 'q_reopen':
				case 'q_close':
					qa_create_event_for_q_user($params['postid'], $params['postid'], QA_UPDATE_CLOSED, $userid, $params['oldquestion']['userid']);
					break;
					

				case 'q_hide':
					if (isset($params['oldquestion']['userid']))
						qa_db_event_create_not_entity($params['oldquestion']['userid'], $params['postid'], $params['postid'], QA_UPDATE_VISIBLE, $userid);
					break;
					

				case 'q_reshow':
					qa_create_event_for_q_user($params['postid'], $params['postid'], QA_UPDATE_VISIBLE, $userid, $params['oldquestion']['userid']);
					break;
					
				
				case 'q_move':
					qa_create_event_for_q_user($params['postid'], $params['postid'], QA_UPDATE_CATEGORY, $userid, $params['oldquestion']['userid']);
					qa_create_event_for_category($params['categoryid'], $params['postid'], QA_UPDATE_CATEGORY, $userid);
					break;
					

				case 'a_edit':
					if ($params['contentchanged'])
						qa_create_event_for_q_user($params['parentid'], $params['postid'], QA_UPDATE_CONTENT, $userid, $params['oldanswer']['userid']);
					break;
					

				case 'a_hide':
					if (isset($params['oldanswer']['userid']))
						qa_db_event_create_not_entity($params['oldanswer']['userid'], $params['parentid'], $params['postid'], QA_UPDATE_VISIBLE, $userid);
					break;
					

				case 'a_reshow':
					qa_create_event_for_q_user($params['parentid'], $params['postid'], QA_UPDATE_VISIBLE, $userid, $params['oldanswer']['userid']);
					break;
					

				case 'c_edit':
					if ($params['contentchanged'])
						qa_create_event_for_q_user($params['questionid'], $params['postid'], QA_UPDATE_CONTENT, $userid, $params['oldcomment']['userid']);
					break;
					

				case 'a_to_c':
					if ($params['contentchanged'])
						qa_create_event_for_q_user($params['questionid'], $params['postid'], QA_UPDATE_CONTENT, $userid, $params['oldanswer']['userid']);
					else
						qa_create_event_for_q_user($params['questionid'], $params['postid'], QA_UPDATE_TYPE, $userid, $params['oldanswer']['userid']);
					break;
				

				case 'c_hide':
					if (isset($params['oldcomment']['userid']))
						qa_db_event_create_not_entity($params['oldcomment']['userid'], $params['questionid'], $params['postid'], QA_UPDATE_VISIBLE, $userid);
					break;
					

				case 'c_reshow':
					qa_create_event_for_q_user($params['questionid'], $params['postid'], QA_UPDATE_VISIBLE, $userid, $params['oldcomment']['userid']);
					break;
			}
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/