<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-asktitle.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Server-side response to Ajax request based on ask a question title


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	

//	Collect the information we need from the database

	$intitle=qa_post_text('title');
	$doaskcheck=qa_opt('do_ask_check_qs');
	$doexampletags=qa_using_tags() && qa_opt('do_example_tags');

	if ($doaskcheck || $doexampletags) {
		$countqs=max($doexampletags ? QA_DB_RETRIEVE_ASK_TAG_QS : 0, $doaskcheck ? qa_opt('page_size_ask_check_qs') : 0);
	
		$relatedquestions=qa_db_select_with_pending(
			qa_db_search_posts_selectspec(null, qa_string_to_words($intitle), null, null, null, null, 0, false, $countqs)
		);
	}
	

//	Collect example tags if appropriate

	if ($doexampletags) {
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		$tagweight=array();
		foreach ($relatedquestions as $question) {
			$tags=qa_tagstring_to_tags($question['tags']);
			foreach ($tags as $tag)
				@$tagweight[$tag]+=exp($question['score']);
		}
		
		arsort($tagweight, SORT_NUMERIC);
		
		$exampletags=array();
		
		$minweight=exp(qa_match_to_min_score(qa_opt('match_example_tags')));
		$maxcount=qa_opt('page_size_ask_tags');

		foreach ($tagweight as $tag => $weight) {
			if ($weight<$minweight)
				break;

			$exampletags[]=$tag;
			if (count($exampletags)>=$maxcount)
				break;
		}

	} else
		$exampletags=array();


//	Output the response header and example tags

	echo "QA_AJAX_RESPONSE\n1\n";
	
	echo strtr(qa_html(implode(',', $exampletags)), "\r\n", '  ')."\n";
	

//	Collect and output the list of related questions

	if ($doaskcheck) {
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		$count=0;
		$minscore=qa_match_to_min_score(qa_opt('match_ask_check_qs'));
		$maxcount=qa_opt('page_size_ask_check_qs');
		
		foreach ($relatedquestions as $question) {
			if ($question['score']<$minscore)
				break;
				
			if (!$count)
				echo qa_lang_html('question/ask_same_q').'<br/>';
			
			echo strtr(
				'<a href="'.qa_q_path_html($question['postid'], $question['title']).'" target="_blank">'.qa_html($question['title']).'</a><br/>',
				"\r\n", '  '
			)."\n";
			
			if ((++$count)>=$maxcount)
				break;
		}
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/