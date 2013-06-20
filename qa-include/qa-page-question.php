<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-question.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for question page (only viewing functionality here)


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

	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-util-sort.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	require_once QA_INCLUDE_DIR.'qa-page-question-view.php';
	require_once QA_INCLUDE_DIR.'qa-app-updates.php';
	
	$questionid=qa_request_part(0);
	$userid=qa_get_logged_in_userid();
	$cookieid=qa_cookie_get();


//	Get information about this question

	list($question, $childposts, $achildposts, $parentquestion, $closepost, $extravalue, $categories, $favorite)=qa_db_select_with_pending(
		qa_db_full_post_selectspec($userid, $questionid),
		qa_db_full_child_posts_selectspec($userid, $questionid),
		qa_db_full_a_child_posts_selectspec($userid, $questionid),
		qa_db_post_parent_q_selectspec($questionid),
		qa_db_post_close_post_selectspec($questionid),
		qa_db_post_meta_selectspec($questionid, 'qa_q_extra'),
		qa_db_category_nav_selectspec($questionid, true, true, true),
		isset($userid) ? qa_db_is_favorite_selectspec($userid, QA_ENTITY_QUESTION, $questionid) : null
	);
	
	if ($question['basetype']!='Q') // don't allow direct viewing of other types of post
		$question=null;

	if (isset($question)) {
		$question['extra']=$extravalue;
		
		$answers=qa_page_q_load_as($question, $childposts);
		$commentsfollows=qa_page_q_load_c_follows($question, $childposts, $achildposts);
		
		$question=$question+qa_page_q_post_rules($question, null, null, $childposts); // array union
		
		if ($question['selchildid'] && (@$answers[$question['selchildid']]['type']!='A'))
			$question['selchildid']=null; // if selected answer is hidden or somehow not there, consider it not selected

		foreach ($answers as $key => $answer) {
			$answers[$key]=$answer+qa_page_q_post_rules($answer, $question, $answers, $achildposts);
			$answers[$key]['isselected']=($answer['postid']==$question['selchildid']);
		}

		foreach ($commentsfollows as $key => $commentfollow) {
			$parent=($commentfollow['parentid']==$questionid) ? $question : @$answers[$commentfollow['parentid']];
			$commentsfollows[$key]=$commentfollow+qa_page_q_post_rules($commentfollow, $parent, $commentsfollows, null);
		}
	}
	
//	Deal with question not found or not viewable, otherwise report the view event

	if (!isset($question))
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';

	if (!$question['viewable']) {
		$qa_content=qa_content_prepare();
		
		if ($question['queued'])
			$qa_content['error']=qa_lang_html('question/q_waiting_approval');
		elseif ($question['flagcount'] && !isset($question['lastuserid']))
			$qa_content['error']=qa_lang_html('question/q_hidden_flagged');
		elseif ($question['authorlast'])
			$qa_content['error']=qa_lang_html('question/q_hidden_author');
		else
			$qa_content['error']=qa_lang_html('question/q_hidden_other');

		$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags());

		return $qa_content;
	}
	
	$permiterror=qa_user_post_permit_error('permit_view_q_page', $question, null, false);
	
	if ( $permiterror && (qa_is_human_probably() || !qa_opt('allow_view_q_bots')) ) {
		$qa_content=qa_content_prepare();
		$topage=qa_q_request($questionid, $question['title']);
		
		switch ($permiterror) {
			case 'login':
				$qa_content['error']=qa_insert_login_links(qa_lang_html('main/view_q_must_login'), $topage);
				break;
				
			case 'confirm':
				$qa_content['error']=qa_insert_login_links(qa_lang_html('main/view_q_must_confirm'), $topage);
				break;
				
			case 'approve':
				$qa_content['error']=qa_lang_html('main/view_q_must_be_approved');
				break;
				
			default:
				$qa_content['error']=qa_lang_html('users/no_permission');
				break;
		}
		
		return $qa_content;
	}


//	Determine if captchas will be required
	
	$captchareason=qa_user_captcha_reason(qa_user_level_for_post($question));
	$usecaptcha=($captchareason!=false);


//	If we're responding to an HTTP POST, include file that handles all posting/editing/etc... logic
//	This is in a separate file because it's a *lot* of logic, and will slow down ordinary page views

	$pagestart=qa_get_start();
	$pagestate=qa_get_state();
	$showid=qa_get('show');
	$pageerror=null;
	$formtype=null;
	$formpostid=null;
	$jumptoanchor=null;
	$commentsall=null;
	
	if (substr($pagestate, 0, 13)=='showcomments-') {
		$commentsall=substr($pagestate, 13);
		$pagestate=null;
	
	} elseif (isset($showid)) {
		foreach ($commentsfollows as $comment)
			if ($comment['postid']==$showid) {
				$commentsall=$comment['parentid'];
				break;
			}
	}
	
	if (qa_is_http_post() || strlen($pagestate))
		require QA_INCLUDE_DIR.'qa-page-question-post.php';
	
	$formrequested=isset($formtype);

	if ((!$formrequested) && $question['answerbutton']) {
		$immedoption=qa_opt('show_a_form_immediate');

		if ( ($immedoption=='always') || (($immedoption=='if_no_as') && (!$question['isbyuser']) && (!$question['acount'])) )
			$formtype='a_add'; // show answer form by default
	}
	
	
//	Get information on the users referenced

	$usershtml=qa_userids_handles_html(array_merge(array($question), $answers, $commentsfollows), true);
	
	
//	Prepare content for theme
	
	$qa_content=qa_content_prepare(true, array_keys(qa_category_path($categories, $question['categoryid'])));
	
	if (isset($userid) && !$formrequested)
		$qa_content['favorite']=qa_favorite_form(QA_ENTITY_QUESTION, $questionid, $favorite, 
			qa_lang($favorite ? 'question/remove_q_favorites' : 'question/add_q_favorites'));

	$qa_content['script_rel'][]='qa-content/qa-question.js?'.QA_VERSION;

	if (isset($pageerror))
		$qa_content['error']=$pageerror; // might also show voting error set in qa-index.php
	
	elseif ($question['queued'])
		$qa_content['error']=$question['isbyuser'] ? qa_lang_html('question/q_your_waiting_approval') : qa_lang_html('question/q_waiting_your_approval');
	
	if ($question['hidden'])
		$qa_content['hidden']=true;
	
	qa_sort_by($commentsfollows, 'created');


//	Prepare content for the question...
	
	if ($formtype=='q_edit') { // ...in edit mode
		$qa_content['title']=qa_lang_html($question['editable'] ? 'question/edit_q_title' :
			(qa_using_categories() ? 'question/recat_q_title' : 'question/retag_q_title'));
		$qa_content['form_q_edit']=qa_page_q_edit_q_form($qa_content, $question, @$qin, @$qerrors, $completetags, $categories);
		$qa_content['q_view']['raw']=$question;

	} else { // ...in view mode
		$qa_content['q_view']=qa_page_q_question_view($question, $parentquestion, $closepost, $usershtml, $formrequested);

		$qa_content['title']=$qa_content['q_view']['title'];

		$qa_content['description']=qa_html(qa_shorten_string_line(qa_viewer_text($question['content'], $question['format']), 150));
		
		$categorykeyword=@$categories[$question['categoryid']]['title'];
		
		$qa_content['keywords']=qa_html(implode(',', array_merge(
			(qa_using_categories() && strlen($categorykeyword)) ? array($categorykeyword) : array(),
			qa_tagstring_to_tags($question['tags'])
		))); // as far as I know, META keywords have zero effect on search rankings or listings, but many people have asked for this
	}
	

//	Prepare content for an answer being edited (if any) or to be added

	if ($formtype=='a_edit') {
		$qa_content['a_form']=qa_page_q_edit_a_form($qa_content, 'a'.$formpostid, $answers[$formpostid],
			$question, $answers, $commentsfollows, @$aeditin[$formpostid], @$aediterrors[$formpostid]);

		$qa_content['a_form']['c_list']=qa_page_q_comment_follow_list($question, $answers[$formpostid],
			$commentsfollows, true, $usershtml, $formrequested, $formpostid);

		$jumptoanchor='a'.$formpostid;
	
	} elseif (($formtype=='a_add') || ($question['answerbutton'] && !$formrequested)) {
		$qa_content['a_form']=qa_page_q_add_a_form($qa_content, 'anew', $captchareason, $question, @$anewin, @$anewerrors, $formtype=='a_add', $formrequested);
		
		if ($formrequested)
			$jumptoanchor='anew';
		elseif ($formtype=='a_add')
			$qa_content['script_onloads'][]=array(
				"qa_element_revealed=document.getElementById('anew');"
			);
	}


//	Prepare content for comments on the question, plus add or edit comment forms

	if ($formtype=='q_close') {
		$qa_content['q_view']['c_form']=qa_page_q_close_q_form($qa_content, $question, 'close', @$closein, @$closeerrors);
		$jumptoanchor='close';
	
	} elseif ((($formtype=='c_add') && ($formpostid==$questionid)) || ($question['commentbutton'] && !$formrequested) ) { // ...to be added
		$qa_content['q_view']['c_form']=qa_page_q_add_c_form($qa_content, $question, $question, 'c'.$questionid,
			$captchareason, @$cnewin[$questionid], @$cnewerrors[$questionid], $formtype=='c_add');
		
		if (($formtype=='c_add') && ($formpostid==$questionid)) {
			$jumptoanchor='c'.$questionid;
			$commentsall=$questionid;
		}
		
	} elseif (($formtype=='c_edit') && (@$commentsfollows[$formpostid]['parentid']==$questionid)) { // ...being edited
		$qa_content['q_view']['c_form']=qa_page_q_edit_c_form($qa_content, 'c'.$formpostid, $commentsfollows[$formpostid],
			@$ceditin[$formpostid], @$cediterrors[$formpostid]);

		$jumptoanchor='c'.$formpostid;
		$commentsall=$questionid;
	}

	$qa_content['q_view']['c_list']=qa_page_q_comment_follow_list($question, $question, $commentsfollows,
		$commentsall==$questionid, $usershtml, $formrequested, $formpostid); // ...for viewing
	

//	Prepare content for existing answers (could be added to by Ajax)

	$qa_content['a_list']=array(
		'tags' => 'id="a_list"',
		'as' => array(),
	);
	
	// sort according to the site preferences
	
	if (qa_opt('sort_answers_by')=='votes') {
		foreach ($answers as $answerid => $answer)
			$answers[$answerid]['sortvotes']=$answer['downvotes']-$answer['upvotes'];

		qa_sort_by($answers, 'sortvotes', 'created');

	} else
		qa_sort_by($answers, 'created');
	
	// further changes to ordering to deal with queued, hidden and selected answers
	
	$countfortitle=$question['acount'];
	$nextposition=10000;
	$answerposition=array();
	
	foreach ($answers as $answerid => $answer)
		if ($answer['viewable']) {
			$position=$nextposition++;
			
			if ($answer['hidden'])
				$position+=10000;
			
			elseif ($answer['queued']) {
				$position-=10000;
				$countfortitle++; // include these in displayed count
			
			} elseif ($answer['isselected'] && qa_opt('show_selected_first'))
				$position-=5000;
	
			$answerposition[$answerid]=$position;
		}
	
	asort($answerposition, SORT_NUMERIC);
	
	// extract IDs and prepare for pagination
	
	$answerids=array_keys($answerposition);
	$countforpages=count($answerids);
	$pagesize=qa_opt('page_size_q_as');
	
	// see if we need to display a particular answer
	
	if (isset($showid)) {
		if (isset($commentsfollows[$showid]))
			$showid=$commentsfollows[$showid]['parentid'];
		
		$position=array_search($showid, $answerids);
		
		if (is_numeric($position))
			$pagestart=floor($position/$pagesize)*$pagesize;
	}
	
	// set the canonical url based on possible pagination
	
	$qa_content['canonical']=qa_path_html(qa_q_request($question['postid'], $question['title']),
		($pagestart>0) ? array('start' => $pagestart) : null, qa_opt('site_url'));
		
	// build the actual answer list

	$answerids=array_slice($answerids, $pagestart, $pagesize);
	
	foreach ($answerids as $answerid) {
		$answer=$answers[$answerid];
		
		if (!(($formtype=='a_edit') && ($formpostid==$answerid))) {
			$a_view=qa_page_q_answer_view($question, $answer, $answer['isselected'], $usershtml, $formrequested);
			
		//	Prepare content for comments on this answer, plus add or edit comment forms
			
			if ((($formtype=='c_add') && ($formpostid==$answerid)) || ($answer['commentbutton'] && !$formrequested) ) { // ...to be added
				$a_view['c_form']=qa_page_q_add_c_form($qa_content, $question, $answer, 'c'.$answerid,
					$captchareason, @$cnewin[$answerid], @$cnewerrors[$answerid], $formtype=='c_add');

				if (($formtype=='c_add') && ($formpostid==$answerid)) {
					$jumptoanchor='c'.$answerid;
					$commentsall=$answerid;
				}

			} else if (($formtype=='c_edit') && (@$commentsfollows[$formpostid]['parentid']==$answerid)) { // ...being edited
				$a_view['c_form']=qa_page_q_edit_c_form($qa_content, 'c'.$formpostid, $commentsfollows[$formpostid],
					@$ceditin[$formpostid], @$cediterrors[$formpostid]);
					
				$jumptoanchor='c'.$formpostid;
				$commentsall=$answerid;
			}

			$a_view['c_list']=qa_page_q_comment_follow_list($question, $answer, $commentsfollows,
				$commentsall==$answerid, $usershtml, $formrequested, $formpostid); // ...for viewing

		//	Add the answer to the list
				
			$qa_content['a_list']['as'][]=$a_view;
		}
	}
	
	if ($question['basetype']=='Q') {
		$qa_content['a_list']['title_tags']='id="a_list_title"';

		if ($countfortitle==1)
			$qa_content['a_list']['title']=qa_lang_html('question/1_answer_title');
		elseif ($countfortitle>0)
			$qa_content['a_list']['title']=qa_lang_html_sub('question/x_answers_title', $countfortitle);
		else
			$qa_content['a_list']['title_tags'].=' style="display:none;" ';
	}

	if (!$formrequested)
		$qa_content['page_links']=qa_html_page_links(qa_request(), $pagestart, $pagesize, $countforpages, qa_opt('pages_prev_next'), array(), false, 'a_list_title');


//	Some generally useful stuff
	
	if (qa_using_categories() && count($categories))
		$qa_content['navigation']['cat']=qa_category_navigation($categories, $question['categoryid']);

	if (isset($jumptoanchor))
		$qa_content['script_onloads'][]=array(
			'qa_scroll_page_to($("#"+'.qa_js($jumptoanchor).').offset().top);'
		);
		
		
//	Determine whether this request should be counted for page view statistics
	
	if (
		qa_opt('do_count_q_views') &&
		(!$formrequested) &&
		(!qa_is_http_post()) &&
		qa_is_human_probably() &&
		( (!$question['views']) || ( // if it has more than zero views
			( ($question['lastviewip']!=qa_remote_ip_address()) || (!isset($question['lastviewip'])) ) && // then it must be different IP from last view
			( ($question['createip']!=qa_remote_ip_address()) || (!isset($question['createip'])) ) && // and different IP from the creator
			( ($question['userid']!=$userid) || (!isset($question['userid'])) ) && // and different user from the creator
			( ($question['cookieid']!=$cookieid) || (!isset($question['cookieid'])) ) // and different cookieid from the creator
		) )
	)
		$qa_content['inc_views_postid']=$questionid;

		
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/