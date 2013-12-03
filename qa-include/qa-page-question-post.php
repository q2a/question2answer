<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question-post.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: More control for question page if it's submitted by HTTP POST


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

	require_once QA_INCLUDE_DIR.'qa-app-limits.php';
	require_once QA_INCLUDE_DIR.'qa-page-question-submit.php';
	
	
	$code=qa_post_text('code');
	

//	Process general cancel button

	if (qa_clicked('docancel'))
		qa_page_q_refresh($pagestart);
	

//	Process incoming answer (or button)

	if ($question['answerbutton']) {
		if (qa_clicked('q_doanswer'))
			qa_page_q_refresh($pagestart, 'answer');
		
		// The 'approve', 'login', 'confirm', 'limit', 'userblock', 'ipblock' permission errors are reported to the user here
		// The other option ('level') prevents the answer button being shown, in qa_page_q_post_rules(...)

		if (qa_clicked('a_doadd') || ($pagestate=='answer'))
			switch (qa_user_post_permit_error('permit_post_a', $question, QA_LIMIT_ANSWERS)) {
				case 'login':
					$pageerror=qa_insert_login_links(qa_lang_html('question/answer_must_login'), qa_request());
					break;
					
				case 'confirm':
					$pageerror=qa_insert_login_links(qa_lang_html('question/answer_must_confirm'), qa_request());
					break;
					
				case 'approve':
					$pageerror=qa_lang_html('question/answer_must_be_approved');
					break;
				
				case 'limit':
					$pageerror=qa_lang_html('question/answer_limit');
					break;
				
				default:
					$pageerror=qa_lang_html('users/no_permission');
					break;
					
				case false:
					if (qa_clicked('a_doadd')) {
						$answerid=qa_page_q_add_a_submit($question, $answers, $usecaptcha, $anewin, $anewerrors);
						
						if (isset($answerid))
							qa_page_q_refresh(0, null, 'A', $answerid);
						else
							$formtype='a_add'; // show form again
							
					} else
						$formtype='a_add'; // show form as if first time
					break;
			}
	}


//	Process close buttons for question
		
	if ($question['closeable']) {
		if (qa_clicked('q_doclose'))
			qa_page_q_refresh($pagestart, 'close');
			
		elseif (qa_clicked('doclose') && qa_page_q_permit_edit($question, 'permit_close_q', $pageerror)) {
			if (qa_page_q_close_q_submit($question, $closepost, $closein, $closeerrors))
				qa_page_q_refresh($pagestart);
			else
				$formtype='q_close'; // keep editing if an error

		} elseif (($pagestate=='close') && qa_page_q_permit_edit($question, 'permit_close_q', $pageerror))
			$formtype='q_close';
	}
	
	
//	Process any single click operations or delete button for question

	if (qa_page_q_single_click_q($question, $answers, $commentsfollows, $closepost, $pageerror))
		qa_page_q_refresh($pagestart);
	
	if (qa_clicked('q_dodelete') && $question['deleteable'] && qa_page_q_click_check_form_code($question, $pageerror)) {
		qa_question_delete($question, $userid, qa_get_logged_in_handle(), $cookieid, $closepost);
		qa_redirect(''); // redirect since question has gone
	}


//	Process edit or save button for question

	if ($question['editbutton'] || $question['retagcatbutton']) {
		if (qa_clicked('q_doedit'))
			qa_page_q_refresh($pagestart, 'edit-'.$questionid);
			
		elseif (qa_clicked('q_dosave') && qa_page_q_permit_edit($question, 'permit_edit_q', $pageerror, 'permit_retag_cat')) {
			if (qa_page_q_edit_q_submit($question, $answers, $commentsfollows, $closepost, $qin, $qerrors))
				qa_redirect(qa_q_request($questionid, $qin['title'])); // don't use refresh since URL may have changed
			
			else {
				$formtype='q_edit'; // keep editing if an error
				$pageerror=@$qerrors['page']; // for security code failure
			}

		} else if (($pagestate==('edit-'.$questionid)) && qa_page_q_permit_edit($question, 'permit_edit_q', $pageerror, 'permit_retag_cat'))
			$formtype='q_edit';
		
		if ($formtype=='q_edit') { // get tags for auto-completion
			if (qa_opt('do_complete_tags'))
				$completetags=array_keys(qa_db_select_with_pending(qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS)));
			else
				$completetags=array();
		}
	}
	

//	Process adding a comment to question (shows form or processes it)
	
	if ($question['commentbutton']) {
		if (qa_clicked('q_docomment'))
			qa_page_q_refresh($pagestart, 'comment-'.$questionid);
			
		if (qa_clicked('c'.$questionid.'_doadd') || ($pagestate==('comment-'.$questionid)))
			qa_page_q_do_comment($question, $question, $commentsfollows, $pagestart, $usecaptcha, $cnewin, $cnewerrors, $formtype, $formpostid, $pageerror);
	}
	
	
//	Process clicked buttons for answers

	foreach ($answers as $answerid => $answer) {
		$prefix='a'.$answerid.'_';
		
		if (qa_page_q_single_click_a($answer, $question, $answers, $commentsfollows, true, $pageerror))
			qa_page_q_refresh($pagestart, null, 'A', $answerid);
		
		if ($answer['editbutton']) {
			if (qa_clicked($prefix.'doedit'))
				qa_page_q_refresh($pagestart, 'edit-'.$answerid);
				
			elseif (qa_clicked($prefix.'dosave') && qa_page_q_permit_edit($answer, 'permit_edit_a', $pageerror)) {
				$editedtype=qa_page_q_edit_a_submit($answer, $question, $answers, $commentsfollows, $aeditin[$answerid], $aediterrors[$answerid]);
				
				if (isset($editedtype))
					qa_page_q_refresh($pagestart, null, $editedtype, $answerid);

				else {
					$formtype='a_edit';
					$formpostid=$answerid; // keep editing if an error
				}
				
			} elseif (($pagestate==('edit-'.$answerid)) && qa_page_q_permit_edit($answer, 'permit_edit_a', $pageerror)) {
				$formtype='a_edit';
				$formpostid=$answerid;
			}
		}
		
		if ($answer['commentbutton']) {
			if (qa_clicked($prefix.'docomment'))
				qa_page_q_refresh($pagestart, 'comment-'.$answerid, 'A', $answerid);
				
			if (qa_clicked('c'.$answerid.'_doadd') || ($pagestate==('comment-'.$answerid)))
				qa_page_q_do_comment($question, $answer, $commentsfollows, $pagestart, $usecaptcha, $cnewin, $cnewerrors, $formtype, $formpostid, $pageerror);
		}

		if (qa_clicked($prefix.'dofollow')) {
			$params=array('follow' => $answerid);
			if (isset($question['categoryid']))
				$params['cat']=$question['categoryid'];
			
			qa_redirect('ask', $params);
		}
	}


//	Process hide, show, delete, flag, unflag, edit or save button for comments

	foreach ($commentsfollows as $commentid => $comment)
		if ($comment['basetype']=='C') {
			$commentparent=@$answers[$comment['parentid']];
			if (!isset($commentparent))
				$commentparent=$question;
				
			$commentparenttype=$commentparent['basetype'];

			$prefix='c'.$commentid.'_';
			
			if (qa_page_q_single_click_c($comment, $question, $commentparent, $pageerror))
				qa_page_q_refresh($pagestart, 'showcomments-'.$comment['parentid'], $commentparenttype, $comment['parentid']);
			
			if ($comment['editbutton']) {
				if (qa_clicked($prefix.'doedit')) {
					if (qa_page_q_permit_edit($comment, 'permit_edit_c', $pageerror)) // extra check here ensures error message is visible
						qa_page_q_refresh($pagestart, 'edit-'.$commentid, $commentparenttype, $comment['parentid']);
				
				} elseif (qa_clicked($prefix.'dosave') && qa_page_q_permit_edit($comment, 'permit_edit_c', $pageerror)) {

					if (qa_page_q_edit_c_submit($comment, $question, $commentparent, $ceditin[$commentid], $cediterrors[$commentid]))
						qa_page_q_refresh($pagestart, null, $commentparenttype, $comment['parentid']);
					
					else {
						$formtype='c_edit';
						$formpostid=$commentid; // keep editing if an error
					}

				} elseif (($pagestate==('edit-'.$commentid)) && qa_page_q_permit_edit($comment, 'permit_edit_c', $pageerror)) {
					$formtype='c_edit';
					$formpostid=$commentid;
				}
			}
		}


//	Functions used above - also see functions in qa-page-question-submit.php (which are shared with Ajax)

	function qa_page_q_refresh($start=0, $state=null, $showtype=null, $showid=null)
/*
	Redirects back to the question page, with the specified parameters
*/
	{
		$params=array();

		if ($start>0)
			$params['start']=$start;
		if (isset($state))
			$params['state']=$state;
			
		if (isset($showtype) && isset($showid)) {
			$anchor=qa_anchor($showtype, $showid);
			$params['show']=$showid;
		} else
			$anchor=null;
			
		qa_redirect(qa_request(), $params, null, null, $anchor);
	}

	
	function qa_page_q_permit_edit($post, $permitoption, &$error, $permitoption2=null)
/*
	Returns whether the editing operation (as specified by $permitoption or $permitoption2) on $post is permitted.
	If not, sets the $error variable appropriately
*/
	{
		// The 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
		// The other options ('approve', 'level') prevent the edit button being shown, in qa_page_q_post_rules(...)

		$permiterror=qa_user_post_permit_error($post['isbyuser'] ? null : $permitoption, $post);
			// if it's by the user, this will only check whether they are blocked
		
		if ($permiterror && isset($permitoption2)) {
			$permiterror2=qa_user_post_permit_error($post['isbyuser'] ? null : $permitoption2, $post);
			
			if ( ($permiterror=='level') || ($permiterror=='approve') || (!$permiterror2) ) // if it's a less strict error
				$permiterror=$permiterror2;
		}
		
		switch ($permiterror) {
			case 'login':
				$error=qa_insert_login_links(qa_lang_html('question/edit_must_login'), qa_request());
				break;
				
			case 'confirm':
				$error=qa_insert_login_links(qa_lang_html('question/edit_must_confirm'), qa_request());
				break;
				
			default:
				$error=qa_lang_html('users/no_permission');
				break;
				
			case false:
				break;
		}
		
		return !$permiterror;
	}


	function qa_page_q_edit_q_form(&$qa_content, $question, $in, $errors, $completetags, $categories)
/*
	Returns a $qa_content form for editing the question and sets up other parts of $qa_content accordingly
*/
	{
		$form=array(
			'tags' => 'method="post" action="'.qa_self_html().'"',
			
			'style' => 'tall',
			
			'fields' => array(
				'title' => array(
					'type' => $question['editable'] ? 'text' : 'static',
					'label' => qa_lang_html('question/q_title_label'),
					'tags' => 'name="q_title"',
					'value' => qa_html(($question['editable'] && isset($in['title'])) ? $in['title'] : $question['title']),
					'error' => qa_html(@$errors['title']),
				),
				
				'category' => array(
					'label' => qa_lang_html('question/q_category_label'),
					'error' => qa_html(@$errors['categoryid']),
				),
				
				'content' => array(
					'label' => qa_lang_html('question/q_content_label'),
					'error' => qa_html(@$errors['content']),
				),
				
				'extra' => array(
					'label' => qa_html(qa_opt('extra_field_prompt')),
					'tags' => 'name="q_extra"',
					'value' => qa_html(isset($in['extra']) ? $in['extra'] : $question['extra']),
					'error' => qa_html(@$errors['extra']),
				),
				
				'tags' => array(
					'error' => qa_html(@$errors['tags']),
				),

			),
			
			'buttons' => array(
				'save' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false);"',
					'label' => qa_lang_html('main/save_button'),
				),
				
				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				'q_dosave' => '1',
				'code' => qa_get_form_security_code('edit-'.$question['postid']),
			),
		);
		
		if ($question['editable']) {
			$content=isset($in['content']) ? $in['content'] : $question['content'];
			$format=isset($in['format']) ? $in['format'] : $question['format'];
			
			$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_qs');
			$editor=qa_load_editor($content, $format, $editorname);
			
			$form['fields']['content']=array_merge($form['fields']['content'],
				qa_editor_load_field($editor, $qa_content, $content, $format, 'q_content', 12, true));
				
			if (method_exists($editor, 'update_script'))
				$form['buttons']['save']['tags']='onclick="qa_show_waiting_after(this, false); '.$editor->update_script('q_content').'"';
			
			$form['hidden']['q_editor']=qa_html($editorname);
		
		} else
			unset($form['fields']['content']);
		
		if (qa_using_categories() && count($categories) && $question['retagcatable'])
			qa_set_up_category_field($qa_content, $form['fields']['category'], 'q_category', $categories,
				isset($in['categoryid']) ? $in['categoryid'] : $question['categoryid'], 
				qa_opt('allow_no_category') || !isset($question['categoryid']), qa_opt('allow_no_sub_category'));
		else
			unset($form['fields']['category']);
			
		if (!($question['editable'] && qa_opt('extra_field_active')))
			unset($form['fields']['extra']);
		
		if (qa_using_tags() && $question['retagcatable'])
			qa_set_up_tag_field($qa_content, $form['fields']['tags'], 'q_tags', isset($in['tags']) ? $in['tags'] : qa_tagstring_to_tags($question['tags']),
				array(), $completetags, qa_opt('page_size_ask_tags'));
		else
			unset($form['fields']['tags']);
		
		if ($question['isbyuser']) {
			if (!qa_is_logged_in())
				qa_set_up_name_field($qa_content, $form['fields'], isset($in['name']) ? $in['name'] : @$question['name'], 'q_');

			qa_set_up_notify_fields($qa_content, $form['fields'], 'Q', qa_get_logged_in_email(),
				isset($in['notify']) ? $in['notify'] : !empty($question['notify']),
				isset($in['email']) ? $in['email'] : @$question['notify'], @$errors['email'], 'q_');
		}
		
		if (!qa_user_post_permit_error('permit_edit_silent', $question))
			$form['fields']['silent']=array(
				'type' => 'checkbox',
				'label' => qa_lang_html('question/save_silent_label'),
				'tags' => 'name="q_silent"',
				'value' => qa_html(@$in['silent']),
			);
		
		return $form;
	}
	
	
	function qa_page_q_edit_q_submit($question, $answers, $commentsfollows, $closepost, &$in, &$errors)
/*
	Processes a POSTed form for editing the question and returns true if successful
*/
	{
		$in=array();
		
		if ($question['editable']) {
			$in['title']=qa_post_text('q_title');
			qa_get_post_content('q_editor', 'q_content', $in['editor'], $in['content'], $in['format'], $in['text']);
			$in['extra']=qa_opt('extra_field_active') ? qa_post_text('q_extra') : null;
		}
		
		if ($question['retagcatable']) {
			if (qa_using_tags())
				$in['tags']=qa_get_tags_field_value('q_tags');
			
			if (qa_using_categories())
				$in['categoryid']=qa_get_category_field_value('q_category');
		}
		
		if (array_key_exists('categoryid', $in)) { // need to check if we can move it to that category, and if we need moderation
			$categories=qa_db_select_with_pending(qa_db_category_nav_selectspec($in['categoryid'], true));
			$categoryids=array_keys(qa_category_path($categories, $in['categoryid']));
			$userlevel=qa_user_level_for_categories($categoryids);
		
		} else
			$userlevel=null;
			
		if ($question['isbyuser']) {
			$in['name']=qa_post_text('q_name');
			$in['notify']=qa_post_text('q_notify') ? true : false;
			$in['email']=qa_post_text('q_email');
		}
		
		if (!qa_user_post_permit_error('permit_edit_silent', $question))
			$in['silent']=qa_post_text('q_silent');

		// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters
		
		$errors=array();
		
		if (!qa_check_form_security_code('edit-'.$question['postid'], qa_post_text('code')))
			$errors['page']=qa_lang_html('misc/form_security_again');
			
		else {
			$in['queued']=qa_opt('moderate_edited_again') && qa_user_moderation_reason($userlevel);
			
			$filtermodules=qa_load_modules_with('filter', 'filter_question');
			foreach ($filtermodules as $filtermodule) {
				$oldin=$in;
				$filtermodule->filter_question($in, $errors, $question);
				
				if ($question['editable'])
					qa_update_post_text($in, $oldin);
			}
			
			if (array_key_exists('categoryid', $in) && strcmp($in['categoryid'], $question['categoryid']))
				if (qa_user_permit_error('permit_post_q', null, $userlevel))
					$errors['categoryid']=qa_lang_html('question/category_ask_not_allowed');
			
			if (empty($errors)) {
				$userid=qa_get_logged_in_userid();
				$handle=qa_get_logged_in_handle();
				$cookieid=qa_cookie_get();
				
				// now we fill in the missing values in the $in array, so that we have everything we need for qa_question_set_content()
				// we do things in this way to avoid any risk of a validation failure on elements the user can't see (e.g. due to admin setting changes)
				
				if (!$question['editable']) {
					$in['title']=$question['title'];
					$in['content']=$question['content'];
					$in['format']=$question['format'];
					$in['text']=qa_viewer_text($in['content'], $in['format']);
					$in['extra']=$question['extra'];
				}
				
				if (!isset($in['tags']))
					$in['tags']=qa_tagstring_to_tags($question['tags']);
					
				if (!array_key_exists('categoryid', $in))
					$in['categoryid']=$question['categoryid'];
					
				if (!isset($in['silent']))
					$in['silent']=false;
				
				$setnotify=$question['isbyuser'] ? qa_combine_notify_email($question['userid'], $in['notify'], $in['email']) : $question['notify'];
				
				qa_question_set_content($question, $in['title'], $in['content'], $in['format'], $in['text'], qa_tags_to_tagstring($in['tags']),
					$setnotify, $userid, $handle, $cookieid, $in['extra'], @$in['name'], $in['queued'], $in['silent']);
	
				if (qa_using_categories() && strcmp($in['categoryid'], $question['categoryid']))
					qa_question_set_category($question, $in['categoryid'], $userid, $handle, $cookieid,
						$answers, $commentsfollows, $closepost, $in['silent']);
				
				return true;
			}
		}
		
		return false;
	}
	

	function qa_page_q_close_q_form(&$qa_content, $question, $id, $in, $errors)
/*
	Returns a $qa_content form for closing the question and sets up other parts of $qa_content accordingly
*/
	{
		$form=array(
			'tags' => 'method="post" action="'.qa_self_html().'"',
			
			'id' => $id,
			
			'style' => 'tall',
			
			'title' => qa_lang_html('question/close_form_title'),
			
			'fields' => array(
				'duplicate' => array(
					'type' => 'checkbox',
					'tags' => 'name="q_close_duplicate" id="q_close_duplicate" onchange="document.getElementById(\'q_close_details\').focus();"',
					'label' => qa_lang_html('question/close_duplicate'),
					'value' => @$in['duplicate'],
				),
				
				'details' => array(
					'tags' => 'name="q_close_details" id="q_close_details"',
					'label' =>
						'<span id="close_label_duplicate">'.qa_lang_html('question/close_original_title').' </span>'.
						'<span id="close_label_other">'.qa_lang_html('question/close_reason_title').'</span>',
					'note' => '<span id="close_note_duplicate" style="display:none;">'.qa_lang_html('question/close_original_note').'</span>',
					'value' => @$in['details'],
					'error' => qa_html(@$errors['details']),
				),
			),
			
			'buttons' => array(
				'close' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false);"',
					'label' => qa_lang_html('question/close_form_button'),
				),
				
				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				'doclose' => '1',
				'code' => qa_get_form_security_code('close-'.$question['postid']),
			),
		);
		
		qa_set_display_rules($qa_content, array(
			'close_label_duplicate' => 'q_close_duplicate',
			'close_label_other' => '!q_close_duplicate',
			'close_note_duplicate' => 'q_close_duplicate',
		));
		
		$qa_content['focusid']='q_close_details';

		return $form;
	}
	
	
	function qa_page_q_close_q_submit($question, $closepost, &$in, &$errors)
/*
	Processes a POSTed form for closing the question and returns true if successful
*/
	{
		$in=array(
			'duplicate' => qa_post_text('q_close_duplicate'),
			'details' => qa_post_text('q_close_details'),
		);
		
		$userid=qa_get_logged_in_userid();
		$handle=qa_get_logged_in_handle();
		$cookieid=qa_cookie_get();
		
		if (!qa_check_form_security_code('close-'.$question['postid'], qa_post_text('code')))
			$errors['details']=qa_lang_html('misc/form_security_again');

		elseif ($in['duplicate']) {
			// be liberal in what we accept, but there are two potential unlikely pitfalls here:
			// a) URLs could have a fixed numerical path, e.g. http://qa.mysite.com/1/478/...
			// b) There could be a question title which is just a number, e.g. http://qa.mysite.com/478/12345/...
			// so we check if more than one question could match, and if so, show an error
			
			$parts=preg_split('|[=/&]|', $in['details'], -1, PREG_SPLIT_NO_EMPTY);
			$keypostids=array();
			
			foreach ($parts as $part)
				if (preg_match('/^[0-9]+$/', $part))
					$keypostids[$part]=true;
					
			$questionids=qa_db_posts_filter_q_postids(array_keys($keypostids));
			
			if ( (count($questionids)==1) && ($questionids[0]!=$question['postid']) ) {
				qa_question_close_duplicate($question, $closepost, $questionids[0], $userid, $handle, $cookieid);
				return true;
			
			} else
				$errors['details']=qa_lang('question/close_duplicate_error');
		
		} else {
			if (strlen($in['details'])>0) {
				qa_question_close_other($question, $closepost, $in['details'], $userid, $handle, $cookieid);
				return true;
			
			} else
				$errors['details']=qa_lang('main/field_required');
		}
		
		return false; 
	}
	
	
	function qa_page_q_edit_a_form(&$qa_content, $id, $answer, $question, $answers, $commentsfollows, $in, $errors)
/*
	Returns a $qa_content form for editing an answer and sets up other parts of $qa_content accordingly
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
	
		$answerid=$answer['postid'];
		$prefix='a'.$answerid.'_';
		
		$content=isset($in['content']) ? $in['content'] : $answer['content'];
		$format=isset($in['format']) ? $in['format'] : $answer['format'];
		
		$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_as');
		$editor=qa_load_editor($content, $format, $editorname);
		
		$hascomments=false;
		foreach ($commentsfollows as $commentfollow)
			if ($commentfollow['parentid']==$answerid)
				$hascomments=true;
		
		$form=array(
			'tags' => 'method="post" action="'.qa_self_html().'"',
			
			'id' => $id,
			
			'title' => qa_lang_html('question/edit_a_title'),
			
			'style' => 'tall',
			
			'fields' => array(
				'content' => array_merge(
					qa_editor_load_field($editor, $qa_content, $content, $format, $prefix.'content', 12),
					array(
						'error' => qa_html(@$errors['content']),
					)
				),
			),

			'buttons' => array(
				'save' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false); '.
						(method_exists($editor, 'update_script') ? $editor->update_script($prefix.'content') : '').'"',
					'label' => qa_lang_html('main/save_button'),
				),
				
				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				$prefix.'editor' => qa_html($editorname),
				$prefix.'dosave' => '1',
				$prefix.'code' => qa_get_form_security_code('edit-'.$answerid),
			),
		);
		
	//	Show option to convert this answer to a comment, if appropriate
		
		$commentonoptions=array();

		$lastbeforeid=$question['postid']; // used to find last post created before this answer - this is default given
		$lastbeforetime=$question['created'];
		
		if ($question['commentable'])
			$commentonoptions[$question['postid']]=
				qa_lang_html('question/comment_on_q').qa_html(qa_shorten_string_line($question['title'], 80));
		
		foreach ($answers as $otheranswer)
			if (($otheranswer['postid']!=$answerid) && ($otheranswer['created']<$answer['created']) && $otheranswer['commentable'] && !$otheranswer['hidden']) {
				$commentonoptions[$otheranswer['postid']]=
					qa_lang_html('question/comment_on_a').qa_html(qa_shorten_string_line(qa_viewer_text($otheranswer['content'], $otheranswer['format']), 80));
				
				if ($otheranswer['created']>$lastbeforetime) {
					$lastbeforeid=$otheranswer['postid'];
					$lastebeforetime=$otheranswer['created'];
				}
			}
				
		if (count($commentonoptions)) {
			$form['fields']['tocomment']=array(
				'tags' => 'name="'.$prefix.'dotoc" id="'.$prefix.'dotoc"',
				'label' => '<span id="'.$prefix.'toshown">'.qa_lang_html('question/a_convert_to_c_on').'</span>'.
								'<span id="'.$prefix.'tohidden" style="display:none;">'.qa_lang_html('question/a_convert_to_c').'</span>',
				'type' => 'checkbox',
				'tight' => true,
			);
			
			$form['fields']['commenton']=array(
				'tags' => 'name="'.$prefix.'commenton"',
				'id' => $prefix.'commenton',
				'type' => 'select',
				'note' => qa_lang_html($hascomments ? 'question/a_convert_warn_cs' : 'question/a_convert_warn'),
				'options' => $commentonoptions,
				'value' => @$commentonoptions[$lastbeforeid],
			);
			
			qa_set_display_rules($qa_content, array(
				$prefix.'commenton' => $prefix.'dotoc',
				$prefix.'toshown' => $prefix.'dotoc',
				$prefix.'tohidden' => '!'.$prefix.'dotoc',
			));
		}
		
	//	Show name and notification field if appropriate
		
		if ($answer['isbyuser']) {
			if (!qa_is_logged_in())
				qa_set_up_name_field($qa_content, $form['fields'], isset($in['name']) ? $in['name'] : @$answer['name'], $prefix);
			
			qa_set_up_notify_fields($qa_content, $form['fields'], 'A', qa_get_logged_in_email(),
				isset($in['notify']) ? $in['notify'] : !empty($answer['notify']),
				isset($in['email']) ? $in['email'] : @$answer['notify'], @$errors['email'], $prefix);
		}
		
		if (!qa_user_post_permit_error('permit_edit_silent', $answer))
			$form['fields']['silent']=array(
				'type' => 'checkbox',
				'label' => qa_lang_html('question/save_silent_label'),
				'tags' => 'name="'.$prefix.'silent"',
				'value' => qa_html(@$in['silent']),
			);
		
		return $form;
	}
	
	
	function qa_page_q_edit_a_submit($answer, $question, $answers, $commentsfollows, &$in, &$errors)
/*
	Processes a POSTed form for editing an answer and returns the new type of the post if successful
*/
	{
		$answerid=$answer['postid'];
		$prefix='a'.$answerid.'_';
		
		$in=array(
			'dotoc' => qa_post_text($prefix.'dotoc'),
			'commenton' => qa_post_text($prefix.'commenton'),
		);
		
		if ($answer['isbyuser']) {
			$in['name']=qa_post_text($prefix.'name');
			$in['notify']=qa_post_text($prefix.'notify') ? true : false;
			$in['email']=qa_post_text($prefix.'email');
		}
		
		if (!qa_user_post_permit_error('permit_edit_silent', $answer))
			$in['silent']=qa_post_text($prefix.'silent');

		qa_get_post_content($prefix.'editor', $prefix.'content', $in['editor'], $in['content'], $in['format'], $in['text']);
		
		// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters
		
		$errors=array();
		
		if (!qa_check_form_security_code('edit-'.$answerid, qa_post_text($prefix.'code')))
			$errors['content']=qa_lang_html('misc/form_security_again');
			
		else {
			$in['queued']=qa_opt('moderate_edited_again') && qa_user_moderation_reason(qa_user_level_for_post($answer));
			
			$filtermodules=qa_load_modules_with('filter', 'filter_answer');
			foreach ($filtermodules as $filtermodule) {
				$oldin=$in;
				$filtermodule->filter_answer($in, $errors, $question, $answer);
				qa_update_post_text($in, $oldin);
			}
			
			if (empty($errors)) {
				$userid=qa_get_logged_in_userid();
				$handle=qa_get_logged_in_handle();
				$cookieid=qa_cookie_get();
	
				if (!isset($in['silent']))
					$in['silent']=false;
				
				$setnotify=$answer['isbyuser'] ? qa_combine_notify_email($answer['userid'], $in['notify'], $in['email']) : $answer['notify'];			
	
				if ($in['dotoc'] && (
					(($in['commenton']==$question['postid']) && $question['commentable']) ||
					(($in['commenton']!=$answerid) && @$answers[$in['commenton']]['commentable'])
				)) { // convert to a comment
	
					if (qa_user_limits_remaining(QA_LIMIT_COMMENTS)) { // already checked 'permit_post_c'
						qa_answer_to_comment($answer, $in['commenton'], $in['content'], $in['format'], $in['text'], $setnotify,
							$userid, $handle, $cookieid, $question, $answers, $commentsfollows, @$in['name'], $in['queued'], $in['silent']);
	
						return 'C'; // to signify that redirect should be to the comment
	
					} else
						$errors['content']=qa_lang_html('question/comment_limit'); // not really best place for error, but it will do
				
				} else {
					qa_answer_set_content($answer, $in['content'], $in['format'], $in['text'], $setnotify,
						$userid, $handle, $cookieid, $question, @$in['name'], $in['queued'], $in['silent']);
	
					return 'A';
				}
			}
		}
		
		return null;
	}


	function qa_page_q_do_comment($question, $parent, $commentsfollows, $pagestart, $usecaptcha, &$cnewin, &$cnewerrors, &$formtype, &$formpostid, &$error)
/*
	Processes a request to add a comment to $parent, with antecedent $question, checking for permissions errors
*/
	{
		// The 'approve', 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
		// The other option ('level') prevents the comment button being shown, in qa_page_q_post_rules(...)

		$answer=($question['postid']==$parent['postid']) ? null : $parent;
		$parentid=$parent['postid'];
		
		switch (qa_user_post_permit_error('permit_post_c', $parent, QA_LIMIT_COMMENTS)) {
			case 'login':
				$error=qa_insert_login_links(qa_lang_html('question/comment_must_login'), qa_request());
				break;
				
			case 'confirm':
				$error=qa_insert_login_links(qa_lang_html('question/comment_must_confirm'), qa_request());
				break;
				
			case 'approve':
				$error=qa_lang_html('question/comment_must_be_approved');
				break;
				
			case 'limit':
				$error=qa_lang_html('question/comment_limit');
				break;
				
			default:
				$error=qa_lang_html('users/no_permission');
				break;
				
			case false:
				if (qa_clicked('c'.$parentid.'_doadd')) {
					$commentid=qa_page_q_add_c_submit($question, $parent, $commentsfollows, $usecaptcha, $cnewin[$parentid], $cnewerrors[$parentid]);

					if (isset($commentid))
						qa_page_q_refresh($pagestart, null, $parent['basetype'], $parentid);
					
					else {
						$formtype='c_add';
						$formpostid=$parentid; // show form again
					}

				} else {
					$formtype='c_add';
					$formpostid=$parentid; // show form first time
				}
				break;
		}
	}

	
	function qa_page_q_edit_c_form(&$qa_content, $id, $comment, $in, $errors)
/*
	Returns a $qa_content form for editing a comment and sets up other parts of $qa_content accordingly
*/
	{
		$commentid=$comment['postid'];
		$prefix='c'.$commentid.'_';
		
		$content=isset($in['content']) ? $in['content'] : $comment['content'];
		$format=isset($in['format']) ? $in['format'] : $comment['format'];
		
		$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_cs');
		$editor=qa_load_editor($content, $format, $editorname);
		
		$form=array(
			'tags' => 'method="post" action="'.qa_self_html().'"',
			
			'id' => $id,
			
			'title' => qa_lang_html('question/edit_c_title'),
			
			'style' => 'tall',
			
			'fields' => array(
				'content' => array_merge(
					qa_editor_load_field($editor, $qa_content, $content, $format, $prefix.'content', 4, true),
					array(
						'error' => qa_html(@$errors['content']),
					)
				),
			),
			
			'buttons' => array(
				'save' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false); '.
						(method_exists($editor, 'update_script') ? $editor->update_script($prefix.'content') : '').'"',
					'label' => qa_lang_html('main/save_button'),
				),
				
				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),
			
			'hidden' => array(
				$prefix.'editor' => qa_html($editorname),
				$prefix.'dosave' => '1',
				$prefix.'code' => qa_get_form_security_code('edit-'.$commentid),
			),
		);
		
		if ($comment['isbyuser']) {
			if (!qa_is_logged_in())
				qa_set_up_name_field($qa_content, $form['fields'], isset($in['name']) ? $in['name'] : @$comment['name'], $prefix);

			qa_set_up_notify_fields($qa_content, $form['fields'], 'C', qa_get_logged_in_email(),
				isset($in['notify']) ? $in['notify'] : !empty($comment['notify']),
				isset($in['email']) ? $in['email'] : @$comment['notify'], @$errors['email'], $prefix);
		}

		if (!qa_user_post_permit_error('permit_edit_silent', $comment))
			$form['fields']['silent']=array(
				'type' => 'checkbox',
				'label' => qa_lang_html('question/save_silent_label'),
				'tags' => 'name="'.$prefix.'silent"',
				'value' => qa_html(@$in['silent']),
			);

		return $form;
	}
	
	
	function qa_page_q_edit_c_submit($comment, $question, $parent, &$in, &$errors)
/*
	Processes a POSTed form for editing a comment and returns true if successful
*/
	{
		$commentid=$comment['postid'];
		$prefix='c'.$commentid.'_';
		
		$in=array();
		
		if ($comment['isbyuser']) {
			$in['name']=qa_post_text($prefix.'name');
			$in['notify']=qa_post_text($prefix.'notify') ? true : false;
			$in['email']=qa_post_text($prefix.'email');
		}

		if (!qa_user_post_permit_error('permit_edit_silent', $comment))
			$in['silent']=qa_post_text($prefix.'silent');

		qa_get_post_content($prefix.'editor', $prefix.'content', $in['editor'], $in['content'], $in['format'], $in['text']);
		
		// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

		$errors=array();
		
		if (!qa_check_form_security_code('edit-'.$commentid, qa_post_text($prefix.'code')))
			$errors['content']=qa_lang_html('misc/form_security_again');
			
		else {
			$in['queued']=qa_opt('moderate_edited_again') && qa_user_moderation_reason(qa_user_level_for_post($comment));
			
			$filtermodules=qa_load_modules_with('filter', 'filter_comment');
			foreach ($filtermodules as $filtermodule) {
				$oldin=$in;
				$filtermodule->filter_comment($in, $errors, $question, $parent, $comment);
				qa_update_post_text($in, $oldin);
			}
	
			if (empty($errors)) {
				$userid=qa_get_logged_in_userid();
				$handle=qa_get_logged_in_handle();
				$cookieid=qa_cookie_get();
	
				if (!isset($in['silent']))
					$in['silent']=false;
					
				$setnotify=$comment['isbyuser'] ? qa_combine_notify_email($comment['userid'], $in['notify'], $in['email']) : $comment['notify'];
				
				qa_comment_set_content($comment, $in['content'], $in['format'], $in['text'], $setnotify,
					$userid, $handle, $cookieid, $question, $parent, @$in['name'], $in['queued'], $in['silent']);
				
				return true;
			}
		}
		
		return false;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/