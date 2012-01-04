<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-search-basic.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Basic module for indexing and searching Q2A posts


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


	class qa_search_basic {
	
		function index_post($postid, $type, $questionid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid)
		{
			require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
			
		//	Get words from each textual element
		
			$titlewords=array_unique(qa_string_to_words($title));
			$contentcount=array_count_values(qa_string_to_words($text));
			$tagwords=array_unique(qa_string_to_words($tagstring));
			$wholetags=array_unique(qa_tagstring_to_tags($tagstring));
			
		//	Map all words to their word IDs
			
			$words=array_unique(array_merge($titlewords, array_keys($contentcount), $tagwords, $wholetags));
			$wordtoid=qa_db_word_mapto_ids_add($words);
			
		//	Add to title words index
			
			$titlewordids=qa_array_filter_by_keys($wordtoid, $titlewords);
			qa_db_titlewords_add_post_wordids($postid, $titlewordids);
		
		//	Add to content words index (including word counts)
		
			$contentwordidcounts=array();
			foreach ($contentcount as $word => $count)
				if (isset($wordtoid[$word]))
					$contentwordidcounts[$wordtoid[$word]]=$count;
	
			qa_db_contentwords_add_post_wordidcounts($postid, $type, $questionid, $contentwordidcounts);
			
		//	Add to tag words index
		
			$tagwordids=qa_array_filter_by_keys($wordtoid, $tagwords);
			qa_db_tagwords_add_post_wordids($postid, $tagwordids);
		
		//	Add to whole tags index
	
			$wholetagids=qa_array_filter_by_keys($wordtoid, $wholetags);
			qa_db_posttags_add_post_wordids($postid, $wholetagids);
			
		//	Update counts cached in database (will be skipped if qa_suspend_update_counts() was called
			
			qa_db_word_titlecount_update($titlewordids);
			qa_db_word_contentcount_update(array_keys($contentwordidcounts));
			qa_db_word_tagwordcount_update($tagwordids);
			qa_db_word_tagcount_update($wholetagids);
			qa_db_tagcount_update();
		}
		

		function unindex_post($postid)
		{
			require_once QA_INCLUDE_DIR.'qa-db-post-update.php';

			$titlewordids=qa_db_titlewords_get_post_wordids($postid);
			qa_db_titlewords_delete_post($postid);
			qa_db_word_titlecount_update($titlewordids);
	
			$contentwordids=qa_db_contentwords_get_post_wordids($postid);
			qa_db_contentwords_delete_post($postid);
			qa_db_word_contentcount_update($contentwordids);
			
			$tagwordids=qa_db_tagwords_get_post_wordids($postid);
			qa_db_tagwords_delete_post($postid);
			qa_db_word_tagwordcount_update($tagwordids);
	
			$wholetagids=qa_db_posttags_get_post_wordids($postid);
			qa_db_posttags_delete_post($postid);
			qa_db_word_tagcount_update($wholetagids);
		}

		
		function move_post($postid, $categoryid)
		{
			// for now, the built-in search engine ignores categories
		}
		
		
		function index_page($pageid, $request, $title, $content, $format, $text)
		{
			// for now, the built-in search engine ignores custom pages
		}
		
		
		function unindex_page($pageid)
		{
			// for now, the built-in search engine ignores custom pages
		}
		
		
		function process_search($query, $start, $count, $userid, $absoluteurls, $fullcontent)
		{
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'qa-util-string.php';

			$words=qa_string_to_words($query);
			
			$questions=qa_db_select_with_pending(
				qa_db_search_posts_selectspec($userid, $words, $words, $words, $words, trim($query), $start, $fullcontent, $count)
			);
			
			$results=array();
			
			foreach ($questions as $question) {
				qa_search_set_max_match($question, $type, $postid); // to link straight to best part

				$results[]=array(
					'question' => $question,
					'match_type' => $type,
					'match_postid' => $postid,
				);
			}
			
			return $results;
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/