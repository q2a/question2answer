<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-event-notify.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Event module for sending notification emails


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


	class qa_event_notify {

		function process_event($event, $userid, $handle, $cookieid, $params)
		{
			require_once QA_INCLUDE_DIR.'qa-app-emails.php';
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			require_once QA_INCLUDE_DIR.'qa-util-string.php';

			
			switch ($event) {
				case 'q_post':
					$followanswer=@$params['followanswer'];
					$sendhandle=isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] : qa_lang('main/anonymous'));
					
					if (isset($followanswer['notify']) && !qa_post_is_by_user($followanswer, $userid, $cookieid)) {
						$blockwordspreg=qa_get_block_words_preg();
						$sendtext=qa_viewer_text($followanswer['content'], $followanswer['format'], array('blockwordspreg' => $blockwordspreg));
						
						qa_send_notification($followanswer['userid'], $followanswer['notify'], @$followanswer['handle'], qa_lang('emails/a_followed_subject'), qa_lang('emails/a_followed_body'), array(
							'^q_handle' => $sendhandle,
							'^q_title' => qa_block_words_replace($params['title'], $blockwordspreg),
							'^a_content' => $sendtext,
							'^url' => qa_q_path($params['postid'], $params['title'], true),
						));
					}
					
					if (qa_opt('notify_admin_q_post'))
						qa_send_notification(null, qa_opt('feedback_email'), null, qa_lang('emails/q_posted_subject'), qa_lang('emails/q_posted_body'), array(
							'^q_handle' => $sendhandle,
							'^q_title' => $params['title'], // don't censor title or content here since we want the admin to see bad words
							'^q_content' => $params['text'],
							'^url' => qa_q_path($params['postid'], $params['title'], true),
						));

					break;

					
				case 'a_post':
					$question=$params['parent'];
					
					if (isset($question['notify']) && !qa_post_is_by_user($question, $userid, $cookieid))
						qa_send_notification($question['userid'], $question['notify'], @$question['handle'], qa_lang('emails/q_answered_subject'), qa_lang('emails/q_answered_body'), array(
							'^a_handle' => isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] : qa_lang('main/anonymous')),
							'^q_title' => $question['title'],
							'^a_content' => qa_block_words_replace($params['text'], qa_get_block_words_preg()),
							'^url' => qa_q_path($question['postid'], $question['title'], true, 'A', $params['postid']),
						));
					break;

					
				case 'c_post':
					$parent=$params['parent'];
					$question=$params['question'];
					
					$senttoemail=array(); // to ensure each user or email gets only one notification about an added comment
					$senttouserid=array();
					
					switch ($parent['basetype']) {
						case 'Q':
							$subject=qa_lang('emails/q_commented_subject');
							$body=qa_lang('emails/q_commented_body');
							$context=$parent['title'];
							break;
							
						case 'A':
							$subject=qa_lang('emails/a_commented_subject');
							$body=qa_lang('emails/a_commented_body');
							$context=qa_viewer_text($parent['content'], $parent['format']);
							break;
					}
					
					$blockwordspreg=qa_get_block_words_preg();
					$sendhandle=isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] : qa_lang('main/anonymous'));
					$sendcontext=qa_block_words_replace($context, $blockwordspreg);
					$sendtext=qa_block_words_replace($params['text'], $blockwordspreg);
					$sendurl=qa_q_path($question['postid'], $question['title'], true, 'C', $params['postid']);
						
					if (isset($parent['notify']) && !qa_post_is_by_user($parent, $userid, $cookieid)) {
						$senduserid=$parent['userid'];
						$sendemail=@$parent['notify'];
						
						if (qa_email_validate($sendemail))
							$senttoemail[$sendemail]=true;
						elseif (isset($senduserid))
							$senttouserid[$senduserid]=true;
			
						qa_send_notification($senduserid, $sendemail, @$parent['handle'], $subject, $body, array(
							'^c_handle' => $sendhandle,
							'^c_context' => $sendcontext,
							'^c_content' => $sendtext,
							'^url' => $sendurl,
						));
					}
					
					foreach ($params['thread'] as $comment)
						if (isset($comment['notify']) && !qa_post_is_by_user($comment, $userid, $cookieid)) {
							$senduserid=$comment['userid'];
							$sendemail=@$comment['notify'];
							
							if (qa_email_validate($sendemail)) {
								if (@$senttoemail[$sendemail])
									continue;
									
								$senttoemail[$sendemail]=true;
								
							} elseif (isset($senduserid)) {
								if (@$senttouserid[$senduserid])
									continue;
									
								$senttouserid[$senduserid]=true;
							}
		
							qa_send_notification($senduserid, $sendemail, @$comment['handle'], qa_lang('emails/c_commented_subject'), qa_lang('emails/c_commented_body'), array(
								'^c_handle' => $sendhandle,
								'^c_context' => $sendcontext,
								'^c_content' => $sendtext,
								'^url' => $sendurl,
							));
						}
					break;

					
				case 'q_queue':
				case 'q_requeue':
					if (qa_opt('moderate_notify_admin'))
						qa_send_notification(null, qa_opt('feedback_email'), null,
							($event=='q_requeue') ? qa_lang('emails/remoderate_subject') : qa_lang('emails/moderate_subject'),
							($event=='q_requeue') ? qa_lang('emails/remoderate_body') : qa_lang('emails/moderate_body'),
							array(
								'^p_handle' => isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] :
									(strlen(@$oldquestion['name']) ? $oldquestion['name'] : qa_lang('main/anonymous'))),
								'^p_context' => trim(@$params['title']."\n\n".$params['text']), // don't censor for admin
								'^url' => qa_q_path($params['postid'], $params['title'], true),
								'^a_url' => qa_path_absolute('admin/moderate'),
							)
						);
					break;
					

				case 'a_queue':
				case 'a_requeue':
					if (qa_opt('moderate_notify_admin'))
						qa_send_notification(null, qa_opt('feedback_email'), null,
							($event=='a_requeue') ? qa_lang('emails/remoderate_subject') : qa_lang('emails/moderate_subject'),
							($event=='a_requeue') ? qa_lang('emails/remoderate_body') : qa_lang('emails/moderate_body'),
							array(
								'^p_handle' => isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] :
									(strlen(@$oldanswer['name']) ? $oldanswer['name'] : qa_lang('main/anonymous'))),
								'^p_context' => $params['text'], // don't censor for admin
								'^url' => qa_q_path($params['parentid'], $params['parent']['title'], true, 'A', $params['postid']),
								'^a_url' => qa_path_absolute('admin/moderate'),
							)
						);
					break;
					

				case 'c_queue':
				case 'c_requeue':
					if (qa_opt('moderate_notify_admin'))
						qa_send_notification(null, qa_opt('feedback_email'), null,
							($event=='c_requeue') ? qa_lang('emails/remoderate_subject') : qa_lang('emails/moderate_subject'),
							($event=='c_requeue') ? qa_lang('emails/remoderate_body') : qa_lang('emails/moderate_body'),
							array(
								'^p_handle' => isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] :
									(strlen(@$oldcomment['name']) ? $oldcomment['name'] : // could also be after answer converted to comment
									(strlen(@$oldanswer['name']) ? $oldanswer['name'] : qa_lang('main/anonymous')))),
								'^p_context' => $params['text'], // don't censor for admin
								'^url' => qa_q_path($params['questionid'], $params['question']['title'], true, 'C', $params['postid']),
								'^a_url' => qa_path_absolute('admin/moderate'),
							)
						);
					break;

					
				case 'q_flag':
				case 'a_flag':
				case 'c_flag':
					$flagcount=$params['flagcount'];
					$oldpost=$params['oldpost'];
					$notifycount=$flagcount-qa_opt('flagging_notify_first');
					
					if ( ($notifycount>=0) && (($notifycount % qa_opt('flagging_notify_every'))==0) )
						qa_send_notification(null, qa_opt('feedback_email'), null, qa_lang('emails/flagged_subject'), qa_lang('emails/flagged_body'), array(
							'^p_handle' => isset($oldpost['handle']) ? $oldpost['handle'] :
								(strlen($oldpost['name']) ? $oldpost['name'] : qa_lang('main/anonymous')),
							'^flags' => ($flagcount==1) ? qa_lang_html_sub('main/1_flag', '1', '1') : qa_lang_html_sub('main/x_flags', $flagcount),
							'^p_context' => trim(@$oldpost['title']."\n\n".qa_viewer_text($oldpost['content'], $oldpost['format'])), // don't censor for admin
							'^url' => qa_q_path($params['questionid'], $params['question']['title'], true, $oldpost['basetype'], $oldpost['postid']),
							'^a_url' => qa_path_absolute('admin/flagged'),
						));
					break;
		
		
				case 'a_select':
					$answer=$params['answer'];
								
					if (isset($answer['notify']) && !qa_post_is_by_user($answer, $userid, $cookieid)) {
						$blockwordspreg=qa_get_block_words_preg();
						$sendcontent=qa_viewer_text($answer['content'], $answer['format'], array('blockwordspreg' => $blockwordspreg));
		
						qa_send_notification($answer['userid'], $answer['notify'], @$answer['handle'], qa_lang('emails/a_selected_subject'), qa_lang('emails/a_selected_body'), array(
							'^s_handle' => isset($handle) ? $handle : qa_lang('main/anonymous'),
							'^q_title' => qa_block_words_replace($params['parent']['title'], $blockwordspreg),
							'^a_content' => $sendcontent,
							'^url' => qa_q_path($params['parentid'], $params['parent']['title'], true, 'A', $params['postid']),
						));
					}
					break;
				
				case 'u_register':
					if (qa_opt('register_notify_admin'))
						qa_send_notification(null, qa_opt('feedback_email'), null, qa_lang('emails/u_registered_subject'),
							qa_opt('moderate_users') ? qa_lang('emails/u_to_approve_body') : qa_lang('emails/u_registered_body'), array(
							'^u_handle' => $handle,
							'^url' => qa_path_absolute('user/'.$handle),
							'^a_url' => qa_path_absolute('admin/approve'),
						));
					break;
					
				case 'u_level':
					if ( ($params['level']>=QA_USER_LEVEL_APPROVED) && ($params['oldlevel']<QA_USER_LEVEL_APPROVED) )
						qa_send_notification($params['userid'], null, $params['handle'], qa_lang('emails/u_approved_subject'), qa_lang('emails/u_approved_body'), array(
							'^url' => qa_path_absolute('user/'.$params['handle']),
						));
					break;
				
				case 'u_wall_post':
					if ($userid!=$params['userid']) {
						$blockwordspreg=qa_get_block_words_preg();
						
						qa_send_notification($params['userid'], null, $params['handle'], qa_lang('emails/wall_post_subject'), qa_lang('emails/wall_post_body'), array(
							'^f_handle' => isset($handle) ? $handle : qa_lang('main/anonymous'),
							'^post' => qa_block_words_replace($params['text'], $blockwordspreg),
							'^url' => qa_path_absolute('user/'.$params['handle'], null, 'wall'),
						));
					}
					break;
			}
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/