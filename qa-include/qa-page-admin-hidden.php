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
	
	$allowhideshow=!qa_user_permit_error('permit_hide_show');
	$allowdeletehidden=!qa_user_permit_error('permit_delete_hidden');
	
	if (!($allowhideshow || $allowdeletehidden)) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}
		
		
//	Check to see if any have been reshown or deleted

	qa_admin_check_clicks();


//	Combine sets of questions, get information for users

	$questions=qa_any_sort_by_date(array_merge($hiddenquestions, $hiddenanswers, $hiddencomments));

	$usershtml=qa_userids_handles_html(qa_any_get_userids_handles($questions));


//	Create list of actual hidden postids and see which ones have dependents

	$qhiddenpostid=array();
	foreach ($questions as $key => $question)
		$qhiddenpostid[$key]=isset($question['opostid']) ? $question['opostid'] : $question['postid'];
		
	$dependcounts=qa_db_postids_count_dependents($qhiddenpostid);
	

//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/recent_hidden_title');
	
	$qa_content['error']=qa_admin_page_error();
	
	$qa_content['q_list']=array(
		'form' => array(
			'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
		),
		
		'qs' => array(),
	);
	
	if (count($questions)) {
		foreach ($questions as $key => $question) {
			$elementid='p'.$qhiddenpostid[$key];

			$htmloptions=qa_post_html_defaults('Q');
			$htmloptions['voteview']=false;
			$htmloptions['tagsview']=!isset($question['opostid']);
			$htmloptions['answersview']=false;
			$htmloptions['updateview']=false;
			$htmloptions['contentview']=true;
			$htmloptions['flagsview']=true;
			$htmloptions['elementid']=$elementid;

			$htmlfields=qa_any_to_q_html_fields($question, $userid, qa_cookie_get(), $usershtml, null, $htmloptions);
			
			if (isset($htmlfields['what_url'])) // link directly to relevant content
				$htmlfields['url']=$htmlfields['what_url'];
				
			$htmlfields['what_2']=qa_lang_html('main/hidden');

			if (@$htmloptions['whenview'])
				$htmlfields['when_2']=qa_when_to_html($question[isset($question['opostid']) ? 'oupdated' : 'updated'], @$htmloptions['fulldatedays']);
			
			$buttons=array();
			
			if ($allowhideshow)
				$buttons['reshow']=array(
					'tags' => 'NAME="admin_'.qa_html($qhiddenpostid[$key]).'_reshow" onclick="return qa_admin_click(this);"',
					'label' => qa_lang_html('question/reshow_button'),
				);
				
			if ($allowdeletehidden && !$dependcounts[$qhiddenpostid[$key]])
				$buttons['delete']=array(
					'tags' => 'NAME="admin_'.qa_html($qhiddenpostid[$key]).'_delete" onclick="return qa_admin_click(this);"',
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