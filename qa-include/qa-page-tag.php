<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-tag.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for page for a specific tag


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-updates.php';
	
	$tag=qa_request_part(1); // picked up from qa-page.php
	$start=qa_get_start();
	$userid=qa_get_logged_in_userid();


//	Find the questions with this tag

	if (!strlen($tag))
		qa_redirect('tags');

	list($questions, $tagword)=qa_db_select_with_pending(
		qa_db_tag_recent_qs_selectspec($userid, $tag, $start, false, qa_opt_if_loaded('page_size_tag_qs')),
		qa_db_tag_word_selectspec($tag)
	);
	
	$pagesize=qa_opt('page_size_tag_qs');
	$questions=array_slice($questions, 0, $pagesize);
	$usershtml=qa_userids_handles_html($questions);


//	Prepare content for theme
	
	$qa_content=qa_content_prepare(true);
	
	$qa_content['title']=qa_lang_html_sub('main/questions_tagged_x', qa_html($tag));
	
	if (isset($userid) && isset($tagword)) {
		$favoritemap=qa_get_favorite_non_qs_map();
		$favorite=@$favoritemap['tag'][qa_strtolower($tagword['word'])];
		
		$qa_content['favorite']=qa_favorite_form(QA_ENTITY_TAG, $tagword['wordid'], $favorite,
			qa_lang_sub($favorite ? 'main/remove_x_favorites' : 'main/add_tag_x_favorites', $tagword['word']));
	}

	if (!count($questions))
		$qa_content['q_list']['title']=qa_lang_html('main/no_questions_found');

	$qa_content['q_list']['form']=array(
		'tags' => 'method="post" action="'.qa_self_html().'"',

		'hidden' => array(
			'code' => qa_get_form_security_code('vote'),
		),
	);

	$qa_content['q_list']['qs']=array();
	foreach ($questions as $postid => $question)
		$qa_content['q_list']['qs'][]=qa_post_html_fields($question, $userid, qa_cookie_get(), $usershtml, null, qa_post_html_options($question));
		
	$qa_content['page_links']=qa_html_page_links(qa_request(), $start, $pagesize, $tagword['tagcount'], qa_opt('pages_prev_next'));

	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=qa_html_suggest_qs_tags(true);

	if (qa_opt('feed_for_tag_qs'))
		$qa_content['feed']=array(
			'url' => qa_path_html(qa_feed_request('tag/'.$tag)),
			'label' => qa_lang_html_sub('main/questions_tagged_x', qa_html($tag)),
		);

		
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/