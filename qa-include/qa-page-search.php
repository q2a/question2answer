<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-search.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for search page


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

	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-search.php';


//	Perform the search if appropriate

	if (strlen(qa_get('q'))) {
	
	//	Pull in input parameters
	
		$inquery=trim(qa_get('q'));
		$userid=qa_get_logged_in_userid();
		$start=qa_get_start();
		
		$display=qa_opt_if_loaded('page_size_search');
		$count=2*(isset($display) ? $display : QA_DB_RETRIEVE_QS_AS)+1;
			// get enough results to be able to give some idea of how many pages of search results there are
		
	//	Perform the search using appropriate module

		$results=qa_get_search_results($inquery, $start, $count, $userid, false, false);
		
	//	Count and truncate results
		
		$pagesize=qa_opt('page_size_search');
		$gotcount=count($results);
		$results=array_slice($results, 0, $pagesize);
		
	//	Retrieve extra information on users	
		
		$fullquestions=array();
		
		foreach ($results as $result)
			if (isset($result['question']))
				$fullquestions[]=$result['question'];
				
		$usershtml=qa_userids_handles_html($fullquestions);
		
	//	Report the search event
		
		qa_report_event('search', $userid, qa_get_logged_in_handle(), qa_cookie_get(), array(
			'query' => $inquery,
			'start' => $start,
		));
	}


//	Prepare content for theme

	$qa_content=qa_content_prepare(true);

	if (strlen(qa_get('q'))) {
		$qa_content['search']['value']=qa_html($inquery);
	
		if (count($results))
			$qa_content['title']=qa_lang_html_sub('main/results_for_x', qa_html($inquery));
		else
			$qa_content['title']=qa_lang_html_sub('main/no_results_for_x', qa_html($inquery));
			
		$qa_content['q_list']['form']=array(
			'tags' => 'method="post" action="'.qa_self_html().'"',

			'hidden' => array(
				'code' => qa_get_form_security_code('vote'),
			),
		);
		
		$qa_content['q_list']['qs']=array();
		
		$qdefaults=qa_post_html_defaults('Q');
		
		foreach ($results as $result)
			if (!isset($result['question'])) { // if we have any non-question results, display with less statistics
				$qdefaults['voteview']=false;
				$qdefaults['answersview']=false;
				$qdefaults['viewsview']=false;
				break;
			}
		
		foreach ($results as $result) {
			if (isset($result['question']))
				$fields=qa_post_html_fields($result['question'], $userid, qa_cookie_get(),
					$usershtml, null, qa_post_html_options($result['question'], $qdefaults));
			
			elseif (isset($result['url']))
				$fields=array(
					'what' => qa_html($result['url']),
					'meta_order' => qa_lang_html('main/meta_order'),
				);

			else
				continue; // nothing to show here
			
			if (isset($qdefaults['blockwordspreg']))
				$result['title']=qa_block_words_replace($result['title'], $qdefaults['blockwordspreg']);
				
			$fields['title']=qa_html($result['title']);
			$fields['url']=qa_html($result['url']);
			
			$qa_content['q_list']['qs'][]=$fields;
		}

		$qa_content['page_links']=qa_html_page_links(qa_request(), $start, $pagesize, $start+$gotcount,
			qa_opt('pages_prev_next'), array('q' => $inquery), $gotcount>=$count);
		
		if (qa_opt('feed_for_search'))
			$qa_content['feed']=array(
				'url' => qa_path_html(qa_feed_request('search/'.$inquery)),
				'label' => qa_lang_html_sub('main/results_for_x', qa_html($inquery)),
			);

		if (empty($qa_content['page_links']))
			$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags());

	} else
		$qa_content['error']=qa_lang_html('main/search_explanation');
	

		
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/