<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-categories.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for page listing categories


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


	$categoryslugs=qa_request_parts(1);
	$countslugs=count($categoryslugs);


//	Get information about appropriate categories and redirect to questions page if category has no sub-categories
	
	$userid=qa_get_logged_in_userid();
	list($categories, $categoryid, $favoritecats)=qa_db_select_with_pending(
		qa_db_category_nav_selectspec($categoryslugs, false, false, true),
		$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null,
		isset($userid) ? qa_db_user_favorite_categories_selectspec($userid) : null
	);
	
	if ($countslugs && !isset($categoryid))
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';
	

//	Function for recursive display of categories

	function qa_category_nav_to_browse(&$navigation, $categories, $categoryid, $favoritemap)
	{
		foreach ($navigation as $key => $navlink) {
			$category=$categories[$navlink['categoryid']];
			
			if (!$category['childcount'])
				unset($navigation[$key]['url']);
			elseif ($navlink['selected']) {
				$navigation[$key]['state']='open';
				$navigation[$key]['url']=qa_path_html('categories/'.qa_category_path_request($categories, $category['parentid']));
			} else
				$navigation[$key]['state']='closed';
				
			if (@$favoritemap[$navlink['categoryid']])
				$navigation[$key]['favorited']=true;
				
			$navigation[$key]['note']='';
			
			$navigation[$key]['note'].=
				' - <a href="'.qa_path_html('questions/'.implode('/', array_reverse(explode('/', $category['backpath'])))).'">'.( ($category['qcount']==1)
					? qa_lang_html_sub('main/1_question', '1', '1')
					: qa_lang_html_sub('main/x_questions', number_format($category['qcount']))
				).'</a>';
				
			if (strlen($category['content']))
				$navigation[$key]['note'].=qa_html(' - '.$category['content']);
			
			if (isset($navlink['subnav']))
				qa_category_nav_to_browse($navigation[$key]['subnav'], $categories, $categoryid, $favoritemap);
		}
	}
		

//	Prepare content for theme

	$qa_content=qa_content_prepare(false, array_keys(qa_category_path($categories, $categoryid)));

	$qa_content['title']=qa_lang_html('misc/browse_categories');
	
	if (count($categories)) {
		$navigation=qa_category_navigation($categories, $categoryid, 'categories/', false);
		
		unset($navigation['all']);
		
		$favoritemap=array();
		if (isset($favoritecats))
			foreach ($favoritecats as $category)
				$favoritemap[$category['categoryid']]=true;

		qa_category_nav_to_browse($navigation, $categories, $categoryid, $favoritemap);
		
		$qa_content['nav_list']=array(
			'nav' => $navigation,
			'type' => 'browse-cat',
		);

	} else {
		$qa_content['title']=qa_lang_html('main/no_categories_found');
		$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags());
	}

	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/