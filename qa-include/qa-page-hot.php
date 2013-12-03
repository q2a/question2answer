<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-hot.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for page listing hot questions


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
	require_once QA_INCLUDE_DIR.'qa-app-q-list.php';
	

//	Get list of hottest questions, allow per-category if QA_ALLOW_UNINDEXED_QUERIES set in qa-config.php
	
	$categoryslugs=QA_ALLOW_UNINDEXED_QUERIES ? qa_request_parts(1) : null;
	$countslugs=@count($categoryslugs);
	
	$start=qa_get_start();
	$userid=qa_get_logged_in_userid();
	
	list($questions, $categories, $categoryid)=qa_db_select_with_pending(
		qa_db_qs_selectspec($userid, 'hotness', $start, $categoryslugs, null, false, false, qa_opt_if_loaded('page_size_hot_qs')),
		qa_db_category_nav_selectspec($categoryslugs, false, false, true),
		$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);

	if ($countslugs) {
		if (!isset($categoryid))
			return include QA_INCLUDE_DIR.'qa-page-not-found.php';
	
		$categorytitlehtml=qa_html($categories[$categoryid]['title']);
		$sometitle=qa_lang_html_sub('main/hot_qs_in_x', $categorytitlehtml);
		$nonetitle=qa_lang_html_sub('main/no_questions_in_x', $categorytitlehtml);

	} else {
		$sometitle=qa_lang_html('main/hot_qs_title');
		$nonetitle=qa_lang_html('main/no_questions_found');
	}
	

//	Prepare and return content for theme

	return qa_q_list_page_content(
		$questions, // questions
		qa_opt('page_size_hot_qs'), // questions per page
		$start, // start offset
		$countslugs ? $categories[$categoryid]['qcount'] : qa_opt('cache_qcount'), // total count
		$sometitle, // title if some questions
		$nonetitle, // title if no questions
		QA_ALLOW_UNINDEXED_QUERIES ? $categories : null, // categories for navigation
		$categoryid, // selected category id
		true, // show question counts in category navigation
		QA_ALLOW_UNINDEXED_QUERIES ? 'hot/' : null, // prefix for links in category navigation (null if no navigation)
		qa_opt('feed_for_hot') ? 'hot' : null, // prefix for RSS feed paths (null to hide)
		qa_html_suggest_ask() // suggest what to do next
	);
	

/*
	Omit PHP closing tag to help avoid accidental output
*/