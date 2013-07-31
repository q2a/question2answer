<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-hidden.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for admin page showing hidden questions, answers and comments


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

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

	
//	Find recently hidden questions, answers, comments

	$userid=qa_get_logged_in_userid();
	
	list($hiddenquestions, $hiddenanswers, $hiddencomments)=qa_db_select_with_pending(
		qa_db_qs_selectspec($userid, 'created', 0, null, null, 'Q_HIDDEN', true),
		qa_db_recent_a_qs_selectspec($userid, 0, null, null, 'A_HIDDEN', true),
		qa_db_recent_c_qs_selectspec($userid, 0, null, null, 'C_HIDDEN', true)
	);
	
	
//	Check admin privileges (do late to allow one DB query)
	
	if (qa_user_maximum_permit_error('permit_hide_show') && qa_user_maximum_permit_error('permit_delete_hidden')) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}
		
		
//	Check to see if any have been reshown or deleted

	$pageerror=qa_admin_check_clicks();


//	Combine sets of questions and remove those this user has no permissions for

	$questions=qa_any_sort_by_date(array_merge($hiddenquestions, $hiddenanswers, $hiddencomments));

	if (qa_user_permit_error('permit_hide_show') && qa_user_permit_error('permit_delete_hidden')) // not allowed to see all hidden posts
		foreach ($questions as $index => $question)
			if (qa_user_post_permit_error('permit_hide_show', $question) && qa_user_post_permit_error('permit_delete_hidden', $question))
				unset($questions[$index]);


//	Get information for users

	$usershtml=qa_userids_handles_html(qa_any_get_userids_handles($questions));


//	Create list of actual hidden postids and see which ones have dependents

	$qhiddenpostid=array();
	foreach ($questions as $key => $question)
		$qhiddenpostid[$key]=isset($question['opostid']) ? $question['opostid'] : $question['postid'];
		
	$dependcounts=qa_db_postids_count_dependents($qhiddenpostid);
	

//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/recent_hidden_title');
	$qa_content['error']=isset($pageerror) ? $pageerror : qa_admin_page_error();
	
	$qa_content['q_list']=array(
		'form' => array(
			'tags' => 'method="post" action="'.qa_self_html().'"',

			'hidden' => array(
				'code' => qa_get_form_security_code('admin/click'),
			),
		),
		
		'qs' => array(),
	);
	
	if (count($questions)) {
		foreach ($questions as $key => $question) {
			$elementid='p'.$qhiddenpostid[$key];

			$htmloptions=qa_post_html_options($question);
			$htmloptions['voteview']=false;
			$htmloptions['tagsview']=!isset($question['opostid']);
			$htmloptions['answersview']=false;
			$htmloptions['viewsview']=false;
			$htmloptions['updateview']=false;
			$htmloptions['contentview']=true;
			$htmloptions['flagsview']=true;
			$htmloptions['elementid']=$elementid;

			$htmlfields=qa_any_to_q_html_fields($question, $userid, qa_cookie_get(), $usershtml, null, $htmloptions);
			
			if (isset($htmlfields['what_url'])) // link directly to relevant content
				$htmlfields['url']=$htmlfields['what_url'];
				
			$htmlfields['what_2']=qa_lang_html('main/hidden');

			if (@$htmloptions['whenview']) {
				$updated=@$question[isset($question['opostid']) ? 'oupdated' : 'updated'];
				if (isset($updated))
					$htmlfields['when_2']=qa_when_to_html($updated, @$htmloptions['fulldatedays']);
			}
			
			$buttons=array();
			
			if (!qa_user_post_permit_error('permit_hide_show', $question))
				$buttons['reshow']=array(
					'tags' => 'name="admin_'.qa_html($qhiddenpostid[$key]).'_reshow" onclick="return qa_admin_click(this);"',
					'label' => qa_lang_html('question/reshow_button'),
				);
				
			if ((!qa_user_post_permit_error('permit_delete_hidden', $question)) && !$dependcounts[$qhiddenpostid[$key]])
				$buttons['delete']=array(
					'tags' => 'name="admin_'.qa_html($qhiddenpostid[$key]).'_delete" onclick="return qa_admin_click(this);"',
					'label' => qa_lang_html('question/delete_button'),
				);
				
			if (count($buttons))
				$htmlfields['form']=array(
					'style' => 'light',
					'buttons' => $buttons,
				);

			$qa_content['q_list']['qs'][]=$htmlfields;
		}

	} else
		$qa_content['title']=qa_lang_html('admin/no_hidden_found');
		

	$qa_content['navigation']['sub']=qa_admin_sub_navigation();
	$qa_content['script_rel'][]='qa-content/qa-admin.js?'.QA_VERSION;

	
	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/