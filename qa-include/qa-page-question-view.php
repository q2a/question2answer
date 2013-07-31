<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question-view.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Common functions for question page viewing, either regular or via Ajax


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


	function qa_page_q_load_as($question, $childposts)
/*
	Given a $question and its $childposts from the database, return a list of that question's answers
*/
	{
		$answers=array();
		
		foreach ($childposts as $postid => $post)
			switch ($post['type']) {
				case 'A':
				case 'A_HIDDEN':
				case 'A_QUEUED':
					$answers[$postid]=$post;
					break;
			}
		
		return $answers;
	}
	
	
	function qa_page_q_load_c_follows($question, $childposts, $achildposts)
/*
	Given a $question, its $childposts and its answers $achildposts from the database,
	return a list of comments or follow-on questions for that question or its answers
*/
	{
		$commentsfollows=array();
		
		foreach ($childposts as $postid => $post)
			switch ($post['type']) {
				case 'Q': // never show follow-on Qs which have been hidden, even to admins
				case 'C':
				case 'C_HIDDEN':
				case 'C_QUEUED':
					$commentsfollows[$postid]=$post;
					break;
			}

		foreach ($achildposts as $postid => $post)
			switch ($post['type']) {
				case 'Q': // never show follow-on Qs which have been hidden, even to admins
				case 'C':
				case 'C_HIDDEN':
				case 'C_QUEUED':
					$commentsfollows[$postid]=$post;
					break;
			}
		
		return $commentsfollows;
	}


	function qa_page_q_post_rules($post, $parentpost=null, $siblingposts=null, $childposts=null)
/*
	Returns elements that can be added to $post which describe which operations the current user may perform on that
	post. This function is a key part of Q2A's logic and is ripe for overriding by plugins. Pass $post's $parentpost if
	there is one, or null otherwise. Pass an array which contains $post's siblings (i.e. other posts with the same type
	and parent) in $siblingposts and $post's children in $childposts. Both of these latter arrays can contain additional
	posts retrieved from the database, and these will be ignored.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$userid=qa_get_logged_in_userid();
		$cookieid=qa_cookie_get();
		$userlevel=qa_user_level_for_post($post);
		
		$rules['isbyuser']=qa_post_is_by_user($post, $userid, $cookieid);
		$rules['queued']=(substr($post['type'], 1)=='_QUEUED');
		$rules['closed']=($post['basetype']=='Q') && (isset($post['closedbyid']) || (isset($post['selchildid']) && qa_opt('do_close_on_select')));

	//	Cache some responses to the user permission checks
	
		$permiterror_post_q=qa_user_permit_error('permit_post_q', null, $userlevel); // don't check limits here, so we can show error message
		$permiterror_post_a=qa_user_permit_error('permit_post_a', null, $userlevel);
		$permiterror_post_c=qa_user_permit_error('permit_post_c', null, $userlevel);

		$permiterror_edit=qa_user_permit_error(($post['basetype']=='Q') ? 'permit_edit_q' :
			(($post['basetype']=='A') ? 'permit_edit_a' : 'permit_edit_c'), null, $userlevel);
		$permiterror_retagcat=qa_user_permit_error('permit_retag_cat', null, $userlevel);
		$permiterror_flag=qa_user_permit_error('permit_flag', null, $userlevel);
		$permiterror_hide_show=qa_user_permit_error($rules['isbyuser'] ? null : 'permit_hide_show', null, $userlevel);
		$permiterror_close_open=qa_user_permit_error($rules['isbyuser'] ? null : 'permit_close_q', null, $userlevel);
		$permiterror_moderate=qa_user_permit_error('permit_moderate', null, $userlevel);
	
	//	General permissions
	
		$rules['authorlast']=((!isset($post['lastuserid'])) || ($post['lastuserid']===$post['userid']));
		$rules['viewable']=$post['hidden'] ? (!$permiterror_hide_show) : ($rules['queued'] ? ($rules['isbyuser'] || !$permiterror_moderate) : true);
		
	//	Answer, comment and edit might show the button even if the user still needs to do something (e.g. log in)
		
		$rules['answerbutton']=($post['type']=='Q') && ($permiterror_post_a!='level') && (!$rules['closed']) &&
			(qa_opt('allow_self_answer') || !$rules['isbyuser']);

		$rules['commentbutton']=(($post['type']=='Q') || ($post['type']=='A')) &&
			($permiterror_post_c!='level') && qa_opt(($post['type']=='Q') ? 'comment_on_qs' : 'comment_on_as');
		$rules['commentable']=$rules['commentbutton'] && !$permiterror_post_c;

		$rules['editbutton']=(!$post['hidden']) && (!$rules['closed']) && 
			($rules['isbyuser'] || (($permiterror_edit!='level') && ($permiterror_edit!='approve') && (!$rules['queued'])));
		$rules['editable']=$rules['editbutton'] && ($rules['isbyuser'] || !$permiterror_edit);
		
		$rules['retagcatbutton']=($post['basetype']=='Q') && (qa_using_tags() || qa_using_categories()) && 
			(!$post['hidden']) && ($rules['isbyuser'] || (($permiterror_retagcat!='level') && ($permiterror_retagcat!='approve')) );
		$rules['retagcatable']=$rules['retagcatbutton'] && ($rules['isbyuser'] || !$permiterror_retagcat);
		
		if ($rules['editbutton'] && $rules['retagcatbutton']) { // only show one button since they lead to the same form
			if ($rules['retagcatable'] && !$rules['editable'])
				$rules['editbutton']=false; // if we can do this without getting an error, show that as the title
			else
				$rules['retagcatbutton']=false;
		}
		
		$rules['aselectable']=($post['type']=='Q') && !qa_user_permit_error($rules['isbyuser'] ? null : 'permit_select_a', null, $userlevel);

		$rules['flagbutton']=qa_opt('flagging_of_posts') && (!$rules['isbyuser']) && (!$post['hidden']) && (!$rules['queued']) &&
			(!@$post['userflag']) && ($permiterror_flag!='level') && ($permiterror_flag!='approve');
		$rules['flagtohide']=$rules['flagbutton'] && (!$permiterror_flag) && (($post['flagcount']+1)>=qa_opt('flagging_hide_after'));
		$rules['unflaggable']=@$post['userflag'] && (!$post['hidden']);
		$rules['clearflaggable']=($post['flagcount']>=(@$post['userflag'] ? 2 : 1)) && !qa_user_permit_error('permit_hide_show', null, $userlevel);
		
	//	Other actions only show the button if it's immediately possible
		
		$notclosedbyother=!($rules['closed'] && isset($post['closedbyid']) && !$rules['authorlast']);
		$nothiddenbyother=!($post['hidden'] && !$rules['authorlast']);
		
		$rules['closeable']=qa_opt('allow_close_questions') && ($post['type']=='Q') && (!$rules['closed']) && !$permiterror_close_open;
		$rules['reopenable']=$rules['closed'] && isset($post['closedbyid']) && (!$permiterror_close_open) && (!$post['hidden']) &&
			($notclosedbyother || !qa_user_permit_error('permit_close_q', null, $userlevel));
			// cannot reopen a question if it's been hidden, or if it was closed by someone else and you don't have global closing permissions
		$rules['moderatable']=$rules['queued'] && !$permiterror_moderate;
		$rules['hideable']=(!$post['hidden']) && ($rules['isbyuser'] || !$rules['queued']) &&
			(!$permiterror_hide_show) && ($notclosedbyother || !qa_user_permit_error('permit_hide_show', null, $userlevel));
			// cannot hide a question if it was closed by someone else and you don't have global hiding permissions
		$rules['reshowimmed']=$post['hidden'] && !qa_user_permit_error('permit_hide_show', null, $userlevel);
			// means post can be reshown immediately without checking whether it needs moderation
		$rules['reshowable']=$post['hidden'] && (!$permiterror_hide_show) &&
			($rules['reshowimmed'] || ($nothiddenbyother && !$post['flagcount']));
			// cannot reshow a question if it was hidden by someone else, or if it has flags - unless you have global hide/show permissions
		$rules['deleteable']=$post['hidden'] && !qa_user_permit_error('permit_delete_hidden', null, $userlevel);
		$rules['claimable']=(!isset($post['userid'])) && isset($userid) && strlen(@$post['cookieid']) && (strcmp(@$post['cookieid'], $cookieid)==0) &&
			!(($post['basetype']=='Q') ? $permiterror_post_q : (($post['basetype']=='A') ? $permiterror_post_a : $permiterror_post_c));
		$rules['followable']=($post['type']=='A') ? qa_opt('follow_on_as') : false;
		
	//	Check for claims that could break rules about self answering and multiple answers
	
		if ($rules['claimable'] && ($post['basetype']=='A')) {		
			if ( (!qa_opt('allow_self_answer')) && isset($parentpost) && qa_post_is_by_user($parentpost, $userid, $cookieid) )
				$rules['claimable']=false;
			
			if (isset($siblingposts) && !qa_opt('allow_multi_answers'))
				foreach ($siblingposts as $siblingpost)
					if ( ($siblingpost['parentid']==$post['parentid']) && ($siblingpost['basetype']=='A') && qa_post_is_by_user($siblingpost, $userid, $cookieid))
						$rules['claimable']=false;
		}
		
	//	Now make any changes based on the child posts
	
		if (isset($childposts))
			foreach ($childposts as $childpost)
				if ($childpost['parentid']==$post['postid']) {
					$rules['deleteable']=false;
					
					if (($childpost['basetype']=='A') && qa_post_is_by_user($childpost, $userid, $cookieid)) {
						if (!qa_opt('allow_multi_answers'))
							$rules['answerbutton']=false;
						
						if (!qa_opt('allow_self_answer'))
							$rules['claimable']=false;
					}
				}
			
	//	Return the resulting rules
	
		return $rules;
	}
	
	
	function qa_page_q_question_view($question, $parentquestion, $closepost, $usershtml, $formrequested)
/*
	Return the $qa_content['q_view'] element for $question as viewed by the current user. If this question is a
	follow-on, pass the question for this question's parent answer in $parentquestion, otherwise null. If the question
	is closed, pass the post used to close this question in $closepost, otherwise null. $usershtml should be an array
	which maps userids to HTML user representations, including the question's author and (if present) last editor. If a
	form has been explicitly requested for the page, set $formrequested to true - this will hide the buttons.
*/
	{
		$questionid=$question['postid'];
		$userid=qa_get_logged_in_userid();
		$cookieid=qa_cookie_get();
		
		$htmloptions=qa_post_html_options($question, null, true);
		$htmloptions['answersview']=false; // answer count is displayed separately so don't show it here
		$htmloptions['avatarsize']=qa_opt('avatar_q_page_q_size');
		$htmloptions['q_request']=qa_q_request($question['postid'], $question['title']);
		$q_view=qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, $htmloptions);


		$q_view['main_form_tags']='method="post" action="'.qa_self_html().'"';
		$q_view['voting_form_hidden']=array('code' => qa_get_form_security_code('vote'));
		$q_view['buttons_form_hidden']=array('code' => qa_get_form_security_code('buttons-'.$questionid), 'qa_click' => '');
		

	//	Buttons for operating on the question
		
		if (!$formrequested) { // don't show if another form is currently being shown on page
			$clicksuffix=' onclick="qa_show_waiting_after(this, false);"'; // add to operations that write to database
			$buttons=array();
			
			if ($question['editbutton'])
				$buttons['edit']=array(
					'tags' => 'name="q_doedit"',
					'label' => qa_lang_html('question/edit_button'),
					'popup' => qa_lang_html('question/edit_q_popup'),
				);
			
			$hascategories=qa_using_categories();
			
			if ($question['retagcatbutton'])
				$buttons['retagcat']=array(
					'tags' => 'name="q_doedit"',
					'label' => qa_lang_html($hascategories ? 'question/recat_button' : 'question/retag_button'),
					'popup' => qa_lang_html($hascategories
						? (qa_using_tags() ? 'question/retag_cat_popup' : 'question/recat_popup')
						: 'question/retag_popup'
					),
				);
			
			if ($question['flagbutton'])
				$buttons['flag']=array(
					'tags' => 'name="q_doflag"'.$clicksuffix,
					'label' => qa_lang_html($question['flagtohide'] ? 'question/flag_hide_button' : 'question/flag_button'),
					'popup' => qa_lang_html('question/flag_q_popup'),
				);

			if ($question['unflaggable'])
				$buttons['unflag']=array(
					'tags' => 'name="q_dounflag"'.$clicksuffix,
					'label' => qa_lang_html('question/unflag_button'),
					'popup' => qa_lang_html('question/unflag_popup'),
				);
				
			if ($question['clearflaggable'])
				$buttons['clearflags']=array(
					'tags' => 'name="q_doclearflags"'.$clicksuffix,
					'label' => qa_lang_html('question/clear_flags_button'),
					'popup' => qa_lang_html('question/clear_flags_popup'),
				);

			if ($question['closeable'])
				$buttons['close']=array(
					'tags' => 'name="q_doclose"',
					'label' => qa_lang_html('question/close_button'),
					'popup' => qa_lang_html('question/close_q_popup'),
				);
			
			if ($question['reopenable'])
				$buttons['reopen']=array(
					'tags' => 'name="q_doreopen"'.$clicksuffix,
					'label' => qa_lang_html('question/reopen_button'),
				);
			
			if ($question['moderatable']) {
				$buttons['approve']=array(
					'tags' => 'name="q_doapprove"'.$clicksuffix,
					'label' => qa_lang_html('question/approve_button'),
				);

				$buttons['reject']=array(
					'tags' => 'name="q_doreject"'.$clicksuffix,
					'label' => qa_lang_html('question/reject_button'),
				);
			}
			
			if ($question['hideable'])
				$buttons['hide']=array(
					'tags' => 'name="q_dohide"'.$clicksuffix,
					'label' => qa_lang_html('question/hide_button'),
					'popup' => qa_lang_html('question/hide_q_popup'),
				);
				
			if ($question['reshowable'])
				$buttons['reshow']=array(
					'tags' => 'name="q_doreshow"'.$clicksuffix,
					'label' => qa_lang_html('question/reshow_button'),
				);
				
			if ($question['deleteable'])
				$buttons['delete']=array(
					'tags' => 'name="q_dodelete"'.$clicksuffix,
					'label' => qa_lang_html('question/delete_button'),
					'popup' => qa_lang_html('question/delete_q_popup'),
				);
				
			if ($question['claimable'])
				$buttons['claim']=array(
					'tags' => 'name="q_doclaim"'.$clicksuffix,
					'label' => qa_lang_html('question/claim_button'),
				);
			
			if ($question['answerbutton']) // don't show if shown by default
				$buttons['answer']=array(
					'tags' => 'name="q_doanswer" id="q_doanswer" onclick="return qa_toggle_element(\'anew\')"',
					'label' => qa_lang_html('question/answer_button'),
					'popup' => qa_lang_html('question/answer_q_popup'),
				);
			
			if ($question['commentbutton'])
				$buttons['comment']=array(
					'tags' => 'name="q_docomment" onclick="return qa_toggle_element(\'c'.$questionid.'\')"',
					'label' => qa_lang_html('question/comment_button'),
					'popup' => qa_lang_html('question/comment_q_popup'),
				);
				
			$q_view['form']=array(
				'style' => 'light',
				'buttons' => $buttons,
			);
		}
		

	//	Information about the question of the answer that this question follows on from (or a question directly)
			
		if (isset($parentquestion))
			$q_view['follows']=array(
				'label' => qa_lang_html(($question['parentid']==$parentquestion['postid']) ? 'question/follows_q' : 'question/follows_a'),
				'title' => qa_html(qa_block_words_replace($parentquestion['title'], qa_get_block_words_preg())),
				'url' => qa_q_path_html($parentquestion['postid'], $parentquestion['title'], false,
					($question['parentid']==$parentquestion['postid']) ? 'Q' : 'A', $question['parentid']),
			);
		
	
	//	Information about the question that this question is a duplicate of (if appropriate)
	
		if (isset($closepost)) {
			
			if ($closepost['basetype']=='Q') {
				$q_view['closed']=array(
					'label' => qa_lang_html('question/closed_as_duplicate'),
					'content' => qa_html(qa_block_words_replace($closepost['title'], qa_get_block_words_preg())),
					'url' => qa_q_path_html($closepost['postid'], $closepost['title']),
				);

			} elseif ($closepost['type']=='NOTE') {
				$viewer=qa_load_viewer($closepost['content'], $closepost['format']);
				
				$q_view['closed']=array(
					'label' => qa_lang_html('question/closed_with_note'),
					'content' => $viewer->get_html($closepost['content'], $closepost['format'], array(
						'blockwordspreg' => qa_get_block_words_preg(),
					)),
				);
			}
		}
		

	//	Extra value display
	
		if (strlen(@$question['extra']) && qa_opt('extra_field_active') && qa_opt('extra_field_display'))
			$q_view['extra']=array(
				'label' => qa_html(qa_opt('extra_field_label')),
				'content' => qa_html(qa_block_words_replace($question['extra'], qa_get_block_words_preg())),
			);

		
		return $q_view;
	}
	
	
	function qa_page_q_answer_view($question, $answer, $isselected, $usershtml, $formrequested)
/*
	Returns an element to add to $qa_content['a_list']['as'] for $answer as viewed by $userid and $cookieid. Pass the
	answer's $question and whether it $isselected. $usershtml should be an array which maps userids to HTML user
	representations, including the answer's author and (if present) last editor. If a form has been explicitly requested
	for the page, set $formrequested to true - this will hide the buttons.
*/
	{
		$answerid=$answer['postid'];
		$userid=qa_get_logged_in_userid();
		$cookieid=qa_cookie_get();
		
		$htmloptions=qa_post_html_options($answer, null, true);
		$htmloptions['isselected']=$isselected;
		$htmloptions['avatarsize']=qa_opt('avatar_q_page_a_size');
		$htmloptions['q_request']=qa_q_request($question['postid'], $question['title']);
		$a_view=qa_post_html_fields($answer, $userid, $cookieid, $usershtml, null, $htmloptions);

		if ($answer['queued'])
			$a_view['error']=$answer['isbyuser'] ? qa_lang_html('question/a_your_waiting_approval') : qa_lang_html('question/a_waiting_your_approval');
		
		$a_view['main_form_tags']='method="post" action="'.qa_self_html().'"';
		$a_view['voting_form_hidden']=array('code' => qa_get_form_security_code('vote'));
		$a_view['buttons_form_hidden']=array('code' => qa_get_form_security_code('buttons-'.$answerid), 'qa_click' => '');


	//	Selection/unselect buttons and others for operating on the answer

		if (!$formrequested) { // don't show if another form is currently being shown on page
			$prefix='a'.qa_html($answerid).'_';
			$clicksuffix=' onclick="return qa_answer_click('.qa_js($answerid).', '.qa_js($question['postid']).', this);"';
			
			if ($question['aselectable'] && !$answer['hidden'] && !$answer['queued']) {
				if ($isselected)
					$a_view['unselect_tags']='title="'.qa_lang_html('question/unselect_popup').'" name="'.$prefix.'dounselect"'.$clicksuffix;
				else
					$a_view['select_tags']='title="'.qa_lang_html('question/select_popup').'" name="'.$prefix.'doselect"'.$clicksuffix;
			}
			
			$buttons=array();
			
			if ($answer['editbutton'])
				$buttons['edit']=array(
					'tags' => 'name="'.$prefix.'doedit"',
					'label' => qa_lang_html('question/edit_button'),
					'popup' => qa_lang_html('question/edit_a_popup'),
				);
				
			if ($answer['flagbutton'])
				$buttons['flag']=array(
					'tags' => 'name="'.$prefix.'doflag"'.$clicksuffix,
					'label' => qa_lang_html($answer['flagtohide'] ? 'question/flag_hide_button' : 'question/flag_button'),
					'popup' => qa_lang_html('question/flag_a_popup'),
				);

			if ($answer['unflaggable'])
				$buttons['unflag']=array(
					'tags' => 'name="'.$prefix.'dounflag"'.$clicksuffix,
					'label' => qa_lang_html('question/unflag_button'),
					'popup' => qa_lang_html('question/unflag_popup'),
				);
				
			if ($answer['clearflaggable'])
				$buttons['clearflags']=array(
					'tags' => 'name="'.$prefix.'doclearflags"'.$clicksuffix,
					'label' => qa_lang_html('question/clear_flags_button'),
					'popup' => qa_lang_html('question/clear_flags_popup'),
				);

			if ($answer['moderatable']) {
				$buttons['approve']=array(
					'tags' => 'name="'.$prefix.'doapprove"'.$clicksuffix,
					'label' => qa_lang_html('question/approve_button'),
				);

				$buttons['reject']=array(
					'tags' => 'name="'.$prefix.'doreject"'.$clicksuffix,
					'label' => qa_lang_html('question/reject_button'),
				);
			}

			if ($answer['hideable'])
				$buttons['hide']=array(
					'tags' => 'name="'.$prefix.'dohide"'.$clicksuffix,
					'label' => qa_lang_html('question/hide_button'),
					'popup' => qa_lang_html('question/hide_a_popup'),
				);
				
			if ($answer['reshowable'])
				$buttons['reshow']=array(
					'tags' => 'name="'.$prefix.'doreshow"'.$clicksuffix,
					'label' => qa_lang_html('question/reshow_button'),
				);
				
			if ($answer['deleteable'])
				$buttons['delete']=array(
					'tags' => 'name="'.$prefix.'dodelete"'.$clicksuffix,
					'label' => qa_lang_html('question/delete_button'),
					'popup' => qa_lang_html('question/delete_a_popup'),
				);
				
			if ($answer['claimable'])
				$buttons['claim']=array(
					'tags' => 'name="'.$prefix.'doclaim"'.$clicksuffix,
					'label' => qa_lang_html('question/claim_button'),
				);

			if ($answer['followable'])
				$buttons['follow']=array(
					'tags' => 'name="'.$prefix.'dofollow"',
					'label' => qa_lang_html('question/follow_button'),
					'popup' => qa_lang_html('question/follow_a_popup'),
				);

			if ($answer['commentbutton'])
				$buttons['comment']=array(
					'tags' => 'name="'.$prefix.'docomment" onclick="return qa_toggle_element(\'c'.$answerid.'\')"',
					'label' => qa_lang_html('question/comment_button'),
					'popup' => qa_lang_html('question/comment_a_popup'),
				);

			$a_view['form']=array(
				'style' => 'light',
				'buttons' => $buttons,
			);
		}
		
		return $a_view;
	}
	
	
	function qa_page_q_comment_view($question, $parent, $comment, $usershtml, $formrequested)
/*
	Returns an element to add to the appropriate $qa_content[...]['c_list']['cs'] array for $comment as viewed by the
	current user. Pass the comment's $parent post and antecedent $question. $usershtml should be an array which maps
	userids to HTML user representations, including the comments's author and (if present) last editor. If a form has
	been explicitly requested for the page, set $formrequested to true - this will hide the buttons.
*/
	{
		$commentid=$comment['postid'];
		$questionid=($parent['basetype']=='Q') ? $parent['postid'] : $parent['parentid'];
		$answerid=($parent['basetype']=='Q') ? null : $parent['postid'];		
		$userid=qa_get_logged_in_userid();
		$cookieid=qa_cookie_get();
		
		$htmloptions=qa_post_html_options($comment, null, true);
		$htmloptions['avatarsize']=qa_opt('avatar_q_page_c_size');
		$htmloptions['q_request']=qa_q_request($question['postid'], $question['title']);
		$c_view=qa_post_html_fields($comment, $userid, $cookieid, $usershtml, null, $htmloptions);
	
		if ($comment['queued'])
			$c_view['error']=$comment['isbyuser'] ? qa_lang_html('question/c_your_waiting_approval') : qa_lang_html('question/c_waiting_your_approval');


	//	Buttons for operating on this comment
			
		if (!$formrequested) { // don't show if another form is currently being shown on page
			$prefix='c'.qa_html($commentid).'_';
			$clicksuffix=' onclick="return qa_comment_click('.qa_js($commentid).', '.qa_js($questionid).', '.qa_js($parent['postid']).', this);"';
			
			$buttons=array();
			
			if ($comment['editbutton'])
				$buttons['edit']=array(
					'tags' => 'name="'.$prefix.'doedit"',
					'label' => qa_lang_html('question/edit_button'),
					'popup' => qa_lang_html('question/edit_c_popup'),
				);
				
			if ($comment['flagbutton'])
				$buttons['flag']=array(
					'tags' => 'name="'.$prefix.'doflag"'.$clicksuffix,
					'label' => qa_lang_html($comment['flagtohide'] ? 'question/flag_hide_button' : 'question/flag_button'),
					'popup' => qa_lang_html('question/flag_c_popup'),
				);
			
			if ($comment['unflaggable'])
				$buttons['unflag']=array(
					'tags' => 'name="'.$prefix.'dounflag"'.$clicksuffix,
					'label' => qa_lang_html('question/unflag_button'),
					'popup' => qa_lang_html('question/unflag_popup'),
				);
				
			if ($comment['clearflaggable'])
				$buttons['clearflags']=array(
					'tags' => 'name="'.$prefix.'doclearflags"'.$clicksuffix,
					'label' => qa_lang_html('question/clear_flags_button'),
					'popup' => qa_lang_html('question/clear_flags_popup'),
				);

			if ($comment['moderatable']) {
				$buttons['approve']=array(
					'tags' => 'name="'.$prefix.'doapprove"'.$clicksuffix,
					'label' => qa_lang_html('question/approve_button'),
				);

				$buttons['reject']=array(
					'tags' => 'name="'.$prefix.'doreject"'.$clicksuffix,
					'label' => qa_lang_html('question/reject_button'),
				);
			}

			if ($comment['hideable'])
				$buttons['hide']=array(
					'tags' => 'name="'.$prefix.'dohide"'.$clicksuffix,
					'label' => qa_lang_html('question/hide_button'),
					'popup' => qa_lang_html('question/hide_c_popup'),
				);
				
			if ($comment['reshowable'])
				$buttons['reshow']=array(
					'tags' => 'name="'.$prefix.'doreshow"'.$clicksuffix,
					'label' => qa_lang_html('question/reshow_button'),
				);
				
			if ($comment['deleteable'])
				$buttons['delete']=array(
					'tags' => 'name="'.$prefix.'dodelete"'.$clicksuffix,
					'label' => qa_lang_html('question/delete_button'),
					'popup' => qa_lang_html('question/delete_c_popup'),
				);
				
			if ($comment['claimable'])
				$buttons['claim']=array(
					'tags' => 'name="'.$prefix.'doclaim"'.$clicksuffix,
					'label' => qa_lang_html('question/claim_button'),
				);
				
			if ($parent['commentbutton'] && qa_opt('show_c_reply_buttons') && ($comment['type']=='C'))
				$buttons['comment']=array(
					'tags' => 'name="'.(($parent['basetype']=='Q') ? 'q' : ('a'.qa_html($parent['postid']))).
						'_docomment" onclick="return qa_toggle_element(\'c'.qa_html($parent['postid']).'\')"',
					'label' => qa_lang_html('question/reply_button'),
					'popup' => qa_lang_html('question/reply_c_popup'),
				);

			$c_view['form']=array(
				'style' => 'light',
				'buttons' => $buttons,
			);
		}
		
		return $c_view;
	}


	function qa_page_q_comment_follow_list($question, $parent, $commentsfollows, $alwaysfull, $usershtml, $formrequested, $formpostid)
/*
	Return an array for $qa_content[...]['c_list'] to display all of the comments and follow-on questions in
	$commentsfollows which belong to post $parent with antecedent $question, as viewed by the current user. If
	$alwaysfull then all comments will be included, otherwise the list may be shortened with a 'show previous x
	comments' link. $usershtml should be an array which maps userids to HTML user representations, including all
	comments' and follow on questions' authors and (if present) last editors. If a form has been explicitly requested
	for the page, set $formrequested to true and pass the postid of the post for the form in $formpostid - this will
	hide the buttons and remove the $formpostid comment from the list.
*/
	{
		$parentid=$parent['postid'];
		$userid=qa_get_logged_in_userid();
		$cookieid=qa_cookie_get();
		
		$commentlist=array(
			'tags' => 'id="c'.qa_html($parentid).'_list"',
			'cs' => array(),
		);

		$showcomments=array();
		
		foreach ($commentsfollows as $commentfollowid => $commentfollow)
			if (($commentfollow['parentid']==$parentid) && $commentfollow['viewable'] && ($commentfollowid!=$formpostid) )
				$showcomments[$commentfollowid]=$commentfollow;
				
		$countshowcomments=count($showcomments);
		
		if ( (!$alwaysfull) && ($countshowcomments > qa_opt('show_fewer_cs_from')) )
			$skipfirst=$countshowcomments-qa_opt('show_fewer_cs_count');
		else
			$skipfirst=0;
			
		if ($skipfirst==$countshowcomments) { // showing none
			if ($skipfirst==1)
				$expandtitle=qa_lang_html('question/show_1_comment');
			else
				$expandtitle=qa_lang_html_sub('question/show_x_comments', $skipfirst);
		
		} else {
			if ($skipfirst==1)
				$expandtitle=qa_lang_html('question/show_1_previous_comment');
			else
				$expandtitle=qa_lang_html_sub('question/show_x_previous_comments', $skipfirst);
		}
		
		if ($skipfirst>0)
			$commentlist['cs'][$parentid]=array(
				'url' => qa_html('?state=showcomments-'.$parentid.'&show='.$parentid.'#'.urlencode(qa_anchor($parent['basetype'], $parentid))),
					
				'expand_tags' => 'onclick="return qa_show_comments('.qa_js($question['postid']).', '.qa_js($parentid).', this);"',
				
				'title' => $expandtitle,
			);
		
		foreach ($showcomments as $commentfollowid => $commentfollow)
			if ($skipfirst>0)
				$skipfirst--;
			
			elseif ($commentfollow['basetype']=='C') {
				$commentlist['cs'][$commentfollowid]=qa_page_q_comment_view($question, $parent, $commentfollow, $usershtml, $formrequested);

			} elseif ($commentfollow['basetype']=='Q') {
				$htmloptions=qa_post_html_options($commentfollow);
				$htmloptions['avatarsize']=qa_opt('avatar_q_page_c_size');
				
				$commentlist['cs'][$commentfollowid]=qa_post_html_fields($commentfollow, $userid, $cookieid, $usershtml, null, $htmloptions);
			}
			
		if (!count($commentlist['cs']))
			$commentlist['hidden']=true;
			
		return $commentlist;
	}
	

	function qa_page_q_add_a_form(&$qa_content, $formid, $captchareason, $question, $in, $errors, $loadnow, $formrequested)
/*
	Return a $qa_content form for adding an answer to $question. Pass an HTML element id to use for the form in $formid
	and the result of qa_user_captcha_reason() in $captchareason. Pass previous inputs from a submitted version of this
	form in the array $in and resulting errors in $errors. If $loadnow is true, the form will be loaded immediately. Set
	$formrequested to true if the user explicitly requested it, as opposed being shown automatically.
*/
	{
		// The 'approve', 'login', 'confirm', 'limit', 'userblock', 'ipblock' permission errors are reported to the user here
		// The other option ('level') prevents the answer button being shown, in qa_page_q_post_rules(...)

		switch (qa_user_post_permit_error('permit_post_a', $question, QA_LIMIT_ANSWERS)) {
			case 'login':
				$form=array(
					'title' => qa_insert_login_links(qa_lang_html('question/answer_must_login'), qa_request())
				);
				break;
				
			case 'confirm':
				$form=array(
					'title' => qa_insert_login_links(qa_lang_html('question/answer_must_confirm'), qa_request())
				);
				break;
				
			case 'approve':
				$form=array(
					'title' => qa_lang_html('question/answer_must_be_approved')
				);
				break;
				
			case 'limit':
				$form=array(
					'title' => qa_lang_html('question/answer_limit')
				);
				break;
			
			default:
				$form=array(
					'title' => qa_lang_html('users/no_permission')
				);
				break;
			
			case false:
				$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_as');
				$editor=qa_load_editor(@$in['content'], @$in['format'], $editorname);
				
				if (method_exists($editor, 'update_script'))
					$updatescript=$editor->update_script('a_content');
				else
					$updatescript='';
				
				$custom=qa_opt('show_custom_answer') ? trim(qa_opt('custom_answer')) : '';
				
				$form=array(
					'tags' => 'method="post" action="'.qa_self_html().'" name="a_form"',
					
					'title' => qa_lang_html('question/your_answer_title'),
					
					'fields' => array(
						'custom' => array(
							'type' => 'custom',
							'note' => $custom,
						),
						
						'content' => array_merge(
							qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'a_content', 12, $formrequested, $loadnow),
							array(
								'error' => qa_html(@$errors['content']),
							)
						),
					),
					
					'buttons' => array(
						'answer' => array(
							'tags' => 'onclick="'.$updatescript.' return qa_submit_answer('.qa_js($question['postid']).', this);"',
							'label' => qa_lang_html('question/add_answer_button'),
						),
					),
					
					'hidden' => array(
						'a_editor' => qa_html($editorname),
						'a_doadd' => '1',
						'code' => qa_get_form_security_code('answer-'.$question['postid']),
					),
				);
				
				if (!strlen($custom))
					unset($form['fields']['custom']);

				if ($formrequested || !$loadnow)
					$form['buttons']['cancel']=array(
						'tags' => 'name="docancel"',
						'label' => qa_lang_html('main/cancel_button'),
					);
					
				if (!qa_is_logged_in())
					qa_set_up_name_field($qa_content, $form['fields'], @$in['name'], 'a_');
					
				qa_set_up_notify_fields($qa_content, $form['fields'], 'A', qa_get_logged_in_email(),
					isset($in['notify']) ? $in['notify'] : qa_opt('notify_users_default'), @$in['email'], @$errors['email'], 'a_');
					
				$onloads=array();
					
				if ($captchareason) {
					$captchaloadscript=qa_set_up_captcha_field($qa_content, $form['fields'], $errors, qa_captcha_reason_note($captchareason));
						
					if (strlen($captchaloadscript))
						$onloads[]='document.getElementById('.qa_js($formid).').qa_show=function() { '.$captchaloadscript.' };';
				}

				if (!$loadnow) {
					if (method_exists($editor, 'load_script'))
						$onloads[]='document.getElementById('.qa_js($formid).').qa_load=function() { '.$editor->load_script('a_content').' };';
						
					$form['buttons']['cancel']['tags'].=' onclick="return qa_toggle_element();"';
				}
				
				if (!$formrequested) {
					if (method_exists($editor, 'focus_script'))
						$onloads[]='document.getElementById('.qa_js($formid).').qa_focus=function() { '.$editor->focus_script('a_content').' };';
				}

				if (count($onloads))
					$qa_content['script_onloads'][]=$onloads;
				break;
		}
		
		$form['id']=$formid;
		$form['collapse']=!$loadnow;
		$form['style']='tall';
		
		return $form;
	}
	
	
	function qa_page_q_add_c_form(&$qa_content, $question, $parent, $formid, $captchareason, $in, $errors, $loadfocusnow)
/*
	Returns a $qa_content form for adding a comment to post $parent which is part of $question. Pass an HTML element id
	to use for the form in $formid and the result of qa_user_captcha_reason() in $captchareason. Pass previous inputs
	from a submitted version of this form in the array $in and resulting errors in $errors. If $loadfocusnow is true,
	the form will be loaded and focused immediately.
*/
	{
		// The 'approve', 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
		// The other option ('level') prevents the comment button being shown, in qa_page_q_post_rules(...)

		switch (qa_user_post_permit_error('permit_post_c', $parent, QA_LIMIT_COMMENTS)) {
			case 'login':
				$form=array(
					'title' => qa_insert_login_links(qa_lang_html('question/comment_must_login'), qa_request())
				);
				break;
			
			case 'confirm':
				$form=array(
					'title' => qa_insert_login_links(qa_lang_html('question/comment_must_confirm'), qa_request())
				);
				break;
			
			case 'approve':
				$form=array(
					'title' => qa_lang_html('question/comment_must_be_approved')
				);
				break;
			
			case 'limit':
				$form=array(
					'title' => qa_lang_html('question/comment_limit')
				);
				break;
			
			default:
				$form=array(
					'title' => qa_lang_html('users/no_permission')
				);
				break;
			
			case false:
				$prefix='c'.$parent['postid'].'_';
				
				$editorname=isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_cs');
				$editor=qa_load_editor(@$in['content'], @$in['format'], $editorname);
		
				if (method_exists($editor, 'update_script'))
					$updatescript=$editor->update_script($prefix.'content');
				else
					$updatescript='';

				$custom=qa_opt('show_custom_comment') ? trim(qa_opt('custom_comment')) : '';
				
				$form=array(
					'tags' => 'method="post" action="'.qa_self_html().'" name="c_form_'.qa_html($parent['postid']).'"',
					
					'title' => qa_lang_html(($question['postid']==$parent['postid']) ? 'question/your_comment_q' : 'question/your_comment_a'),
					
					'fields' => array(
						'custom' => array(
							'type' => 'custom',
							'note' => $custom,
						),
						
						'content' => array_merge(
							qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], $prefix.'content', 4, $loadfocusnow, $loadfocusnow),
							array(
								'error' => qa_html(@$errors['content']),
							)
						),
					),
					
					'buttons' => array(
						'comment' => array(
							'tags' => 'onclick="'.$updatescript.' return qa_submit_comment('.qa_js($question['postid']).', '.qa_js($parent['postid']).', this);"',
							'label' => qa_lang_html('question/add_comment_button'),
						),
						
						'cancel' => array(
							'tags' => 'name="docancel"',
							'label' => qa_lang_html('main/cancel_button'),
						),
					),
					
					'hidden' => array(
						$prefix.'editor' => qa_html($editorname),
						$prefix.'doadd' => '1',
						$prefix.'code' => qa_get_form_security_code('comment-'.$parent['postid']),
					),
				);
		
				if (!strlen($custom))
					unset($form['fields']['custom']);
			
				if (!qa_is_logged_in())
					qa_set_up_name_field($qa_content, $form['fields'], @$in['name'], $prefix);

				qa_set_up_notify_fields($qa_content, $form['fields'], 'C', qa_get_logged_in_email(),
					isset($in['notify']) ? $in['notify'] : qa_opt('notify_users_default'), $in['email'], @$errors['email'], $prefix);
				
				$onloads=array();

				if ($captchareason) {
					$captchaloadscript=qa_set_up_captcha_field($qa_content, $form['fields'], $errors, qa_captcha_reason_note($captchareason));
						
					if (strlen($captchaloadscript))
						$onloads[]='document.getElementById('.qa_js($formid).').qa_show=function() { '.$captchaloadscript.' };';
				}
				
				if (!$loadfocusnow) {
					if (method_exists($editor, 'load_script'))
						$onloads[]='document.getElementById('.qa_js($formid).').qa_load=function() { '.$editor->load_script($prefix.'content').' };';
					if (method_exists($editor, 'focus_script'))
						$onloads[]='document.getElementById('.qa_js($formid).').qa_focus=function() { '.$editor->focus_script($prefix.'content').' };';
						
					$form['buttons']['cancel']['tags'].=' onclick="return qa_toggle_element()"';
				}

				if (count($onloads))
					$qa_content['script_onloads'][]=$onloads;
		}
		
		$form['id']=$formid;
		$form['collapse']=!$loadfocusnow;
		$form['style']='tall';
				
		return $form;
	}

	
/*
	Omit PHP closing tag to help avoid accidental output
*/