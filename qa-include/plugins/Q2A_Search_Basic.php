<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-search-basic.php
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

class Q2A_Search_Basic extends Q2A_Plugin_Module_Search
{
	public function getInternalId()
	{
		return 'Q2A_Search_Basic';
	}

	public function getDisplayName() {
		return 'Basic Search';
	}

	public function indexPost($postId, $type, $questionId, $parentId, $title, $content, $format, $text, $tagString, $categoryId)
	{
		require_once QA_INCLUDE_DIR.'db/post-create.php';

	//	Get words from each textual element

		$titlewords=array_unique(qa_string_to_words($title));
		$contentcount=array_count_values(qa_string_to_words($text));
		$tagwords=array_unique(qa_string_to_words($tagString));
		$wholetags=array_unique(qa_tagstring_to_tags($tagString));

	//	Map all words to their word IDs

		$words=array_unique(array_merge($titlewords, array_keys($contentcount), $tagwords, $wholetags));
		$wordtoid=qa_db_word_mapto_ids_add($words);

	//	Add to title words index

		$titlewordids=qa_array_filter_by_keys($wordtoid, $titlewords);
		qa_db_titlewords_add_post_wordids($postId, $titlewordids);

	//	Add to content words index (including word counts)

		$contentwordidcounts=array();
		foreach ($contentcount as $word => $count)
			if (isset($wordtoid[$word]))
				$contentwordidcounts[$wordtoid[$word]]=$count;

		qa_db_contentwords_add_post_wordidcounts($postId, $type, $questionId, $contentwordidcounts);

	//	Add to tag words index

		$tagwordids=qa_array_filter_by_keys($wordtoid, $tagwords);
		qa_db_tagwords_add_post_wordids($postId, $tagwordids);

	//	Add to whole tags index

		$wholetagids=qa_array_filter_by_keys($wordtoid, $wholetags);
		qa_db_posttags_add_post_wordids($postId, $wholetagids);

	//	Update counts cached in database (will be skipped if qa_suspend_update_counts() was called

		qa_db_word_titlecount_update($titlewordids);
		qa_db_word_contentcount_update(array_keys($contentwordidcounts));
		qa_db_word_tagwordcount_update($tagwordids);
		qa_db_word_tagcount_update($wholetagids);
		qa_db_tagcount_update();
	}

	public function unindexPost($postId)
	{
		require_once QA_INCLUDE_DIR.'db/post-update.php';

		$titlewordids=qa_db_titlewords_get_post_wordids($postId);
		qa_db_titlewords_delete_post($postId);
		qa_db_word_titlecount_update($titlewordids);

		$contentwordids=qa_db_contentwords_get_post_wordids($postId);
		qa_db_contentwords_delete_post($postId);
		qa_db_word_contentcount_update($contentwordids);

		$tagwordids=qa_db_tagwords_get_post_wordids($postId);
		qa_db_tagwords_delete_post($postId);
		qa_db_word_tagwordcount_update($tagwordids);

		$wholetagids=qa_db_posttags_get_post_wordids($postId);
		qa_db_posttags_delete_post($postId);
		qa_db_word_tagcount_update($wholetagids);
	}

	public function movePost($postId, $categoryId)
	{
		// for now, the built-in search engine ignores categories
	}

	public function indexPage($pageId, $request, $title, $content, $format, $text)
	{
		// for now, the built-in search engine ignores custom pages
	}

	public function unindexPage($pageId)
	{
		// for now, the built-in search engine ignores custom pages
	}

	public function processSearch($query, $start, $count, $userId, $absoluteUrls, $fullContent)
	{
		require_once QA_INCLUDE_DIR.'db/selects.php';
		require_once QA_INCLUDE_DIR.'util/string.php';

		$words=qa_string_to_words($query);

		$questions=qa_db_select_with_pending(
			qa_db_search_posts_selectspec($userId, $words, $words, $words, $words, trim($query), $start, $fullContent, $count)
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
