<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-event-limits.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Event module for updating per-user limits


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


	class qa_event_limits {

		function process_event($event, $userid, $handle, $cookieid, $params)
		{
			// Don't increment limits or report user actions for events that were delayed. For example, a 'q_post'
			// event sent when a post is approved by the admin, for which a 'q_queue' event was already sent.
			
			if (isset($params['delayed']))
				return;
			
			require_once QA_INCLUDE_DIR.'qa-app-limits.php';
			
			switch ($event) {
				case 'q_queue':
				case 'q_post':
				case 'q_claim':
					qa_limits_increment($userid, QA_LIMIT_QUESTIONS);
					break;
				
				case 'a_queue':
				case 'a_post':
				case 'a_claim':
					qa_limits_increment($userid, QA_LIMIT_ANSWERS);
					break;
				
				case 'c_queue':	
				case 'c_post':
				case 'c_claim':
				case 'a_to_c':
					qa_limits_increment($userid, QA_LIMIT_COMMENTS);
					break;
				
				case 'q_vote_up':
				case 'q_vote_down':
				case 'q_vote_nil':
				case 'a_vote_up':
				case 'a_vote_down':
				case 'a_vote_nil':
					qa_limits_increment($userid, QA_LIMIT_VOTES);
					break;
					
				case 'q_flag':
				case 'a_flag':
				case 'c_flag':
					qa_limits_increment($userid, QA_LIMIT_FLAGS);
					break;
					
				case 'u_message':
					qa_limits_increment($userid, QA_LIMIT_MESSAGES);
					break;

				case 'u_wall_post':
					qa_limits_increment($userid, QA_LIMIT_WALL_POSTS);
					break;
			}
			
			$writeactions=array(
				'_approve', '_claim', '_clearflags', '_delete', '_edit', '_favorite', '_flag', '_hide',
				'_post', '_queue', '_reject', '_reshow', '_unfavorite', '_unflag', '_vote_down', '_vote_nil', '_vote_up',
				'a_select', 'a_to_c', 'a_unselect',
				'q_close', 'q_move', 'q_reopen',
				'u_block', 'u_edit', 'u_level', 'u_message', 'u_password', 'u_save', 'u_unblock',
			);
			
			if (
				is_numeric(array_search(strstr($event, '_'), $writeactions)) ||
				is_numeric(array_search($event, $writeactions))
			) {
				if (isset($userid)) {
					require_once QA_INCLUDE_DIR.'qa-app-users.php';
					
					qa_user_report_action($userid, $event);
	
				} elseif (isset($cookieid)) {
					require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
		
					qa_cookie_report_action($cookieid, $event);
				}
			}
				
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/