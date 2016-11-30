<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-ajax-asktitle.php
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

	require_once QA_INCLUDE_DIR.'db/selects.php';
	require_once QA_INCLUDE_DIR.'util/string.php';
	require_once QA_INCLUDE_DIR.'app/users.php';
	require_once QA_INCLUDE_DIR.'app/format.php';


//	Collect the information we need from the database

	$intitle = qa_post_text('title');
	$doaskcheck = qa_opt('do_ask_check_qs');
	$doexampletags = qa_using_tags() && qa_opt('do_example_tags');

	if ($doaskcheck || $doexampletags) {
		$countqs = max($doexampletags ? QA_DB_RETRIEVE_ASK_TAG_QS : 0, $doaskcheck ? qa_opt('page_size_ask_check_qs') : 0);

		$relatedquestions = qa_db_select_with_pending(
			qa_db_search_posts_selectspec(null, qa_string_to_words($intitle), null, null, null, null, 0, false, $countqs)
		);
	}


//	Collect example tags if appropriate

	if ($doexampletags) {
		$tagweight = array();
		foreach ($relatedquestions as $question) {
			$tags = qa_tagstring_to_tags($question['tags']);
			foreach ($tags as $tag)
				@$tagweight[$tag] += exp($question['score']);
		}

		arsort($tagweight, SORT_NUMERIC);

		$exampletags = array();

		$minweight = exp(qa_match_to_min_score(qa_opt('match_example_tags')));
		$maxcount = qa_opt('page_size_ask_tags');

		foreach ($tagweight as $tag => $weight) {
			if ($weight < $minweight)
				break;

			$exampletags[] = $tag;
			if (count($exampletags) >= $maxcount)
				break;
		}
	}
	else
		$exampletags = array();


//	Output the response header and example tags

	echo "QA_AJAX_RESPONSE\n1\n";

	echo strtr(qa_html(implode(',', $exampletags)), "\r\n", '  ') . "\n";


//	Collect and output the list of related questions

	if ($doaskcheck) {
		$minscore = qa_match_to_min_score(qa_opt('match_ask_check_qs'));
		$maxcount = qa_opt('page_size_ask_check_qs');

		$relatedquestions = array_slice($relatedquestions, 0, $maxcount);
		$limitedquestions = array();

		foreach ($relatedquestions as $question) {
			if ($question['score'] < $minscore)
				break;

			$limitedquestions[] = $question;
		}

		$themeclass = qa_load_theme_class(qa_get_site_theme(), 'ajax-asktitle', null, null);
		$themeclass->initialize();
		$themeclass->q_ask_similar($limitedquestions, qa_lang_html('question/ask_same_q'));
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/