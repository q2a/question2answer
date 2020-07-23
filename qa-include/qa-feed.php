<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Handles all requests to RSS feeds, first checking if they should be available


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

@ini_set('display_errors', 0); // we don't want to show PHP errors to RSS readers

qa_report_process_stage('init_feed');

require_once QA_INCLUDE_DIR . 'app/options.php';


// Functions used within this file

/**
 * Database failure handler function for RSS feeds - outputs HTTP and text errors
 * @param string $type
 * @param int $errno
 * @param string $error
 * @param string $query
 */
function qa_feed_db_fail_handler($type, $errno = null, $error = null, $query = null)
{
	qa_500();
	echo qa_lang_html('main/general_error');
	qa_exit('error');
}


/**
 * Common function called when a non-existent feed is requested - outputs HTTP and text errors
 */
function qa_feed_not_found()
{
	qa_404();
	echo qa_lang_html('misc/feed_not_found');
	qa_exit();
}


/**
 * Common function to load appropriate set of questions for requested feed, check category exists, and set up page title
 * @param array $categoryslugs
 * @param string|null $allkey
 * @param string|null $catkey
 * @param string $title
 * @param array $questionselectspec1
 * @param array $questionselectspec2
 * @param array $questionselectspec3
 * @param array $questionselectspec4
 * @return array
 */
function qa_feed_load_ifcategory($categoryslugs, $allkey, $catkey, &$title,
	$questionselectspec1 = null, $questionselectspec2 = null, $questionselectspec3 = null, $questionselectspec4 = null)
{
	$countslugs = @count($categoryslugs);

	list($questions1, $questions2, $questions3, $questions4, $categories, $categoryid) = qa_db_select_with_pending(
		$questionselectspec1,
		$questionselectspec2,
		$questionselectspec3,
		$questionselectspec4,
		$countslugs ? qa_db_category_nav_selectspec($categoryslugs, false) : null,
		$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);

	if ($countslugs && !isset($categoryid))
		qa_feed_not_found();

	if (isset($allkey))
		$title = (isset($categoryid) && isset($catkey)) ? qa_lang_sub($catkey, $categories[$categoryid]['title']) : qa_lang($allkey);

	return array_merge(
		is_array($questions1) ? $questions1 : array(),
		is_array($questions2) ? $questions2 : array(),
		is_array($questions3) ? $questions3 : array(),
		is_array($questions4) ? $questions4 : array()
	);
}


// Connect to database and get the type of feed and category requested (in some cases these are overridden later)

$qa_db->connect('qa_feed_db_fail_handler');
qa_initialize_postdb_plugins();

$requestlower = strtolower(qa_request());
$foursuffix = substr($requestlower, -4);

if ($foursuffix == '.rss' || $foursuffix == '.xml') {
	$requestlower = substr($requestlower, 0, -4);
}

$requestlowerparts = explode('/', $requestlower);

$feedtype = @$requestlowerparts[1];
$feedparams = array_slice($requestlowerparts, 2);


// Choose which option needs to be checked to determine if this feed can be requested, and stop if no matches

$feedoption = null;
$categoryslugs = $feedparams;

switch ($feedtype) {
	case 'questions':
		$feedoption = 'feed_for_questions';
		break;

	case 'hot':
		$feedoption = 'feed_for_hot';
		if (!QA_ALLOW_UNINDEXED_QUERIES)
			$categoryslugs = null;
		break;

	case 'unanswered':
		$feedoption = 'feed_for_unanswered';
		if (!QA_ALLOW_UNINDEXED_QUERIES)
			$categoryslugs = null;
		break;

	case 'answers':
	case 'comments':
	case 'activity':
		$feedoption = 'feed_for_activity';
		break;

	case 'qa':
		$feedoption = 'feed_for_qa';
		break;

	case 'tag':
		if (strlen(@$feedparams[0])) {
			$feedoption = 'feed_for_tag_qs';
			$categoryslugs = null;
		}
		break;

	case 'search':
		if (strlen(@$feedparams[0])) {
			$feedoption = 'feed_for_search';
			$categoryslugs = null;
		}
		break;
}

$countslugs = @count($categoryslugs);

if (!isset($feedoption))
	qa_feed_not_found();


// Check that all the appropriate options are in place to allow this feed to be retrieved

if (!(qa_opt($feedoption) && ($countslugs ? (qa_using_categories() && qa_opt('feed_per_category')) : true)))
	qa_feed_not_found();


// Retrieve the appropriate questions and other information for this feed

require_once QA_INCLUDE_DIR . 'db/selects.php';

$sitetitle = qa_opt('site_title');
$siteurl = qa_opt('site_url');
$full = qa_opt('feed_full_text');
$count = qa_opt('feed_number_items');
$showurllinks = qa_opt('show_url_links');

$linkrequest = $feedtype . ($countslugs ? ('/' . implode('/', $categoryslugs)) : '');
$linkparams = null;

switch ($feedtype) {
	case 'questions':
		$questions = qa_feed_load_ifcategory($categoryslugs, 'main/recent_qs_title', 'main/recent_qs_in_x', $title,
			qa_db_qs_selectspec(null, 'created', 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'hot':
		$questions = qa_feed_load_ifcategory($categoryslugs, 'main/hot_qs_title', 'main/hot_qs_in_x', $title,
			qa_db_qs_selectspec(null, 'hotness', 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'unanswered':
		$questions = qa_feed_load_ifcategory($categoryslugs, 'main/unanswered_qs_title', 'main/unanswered_qs_in_x', $title,
			qa_db_unanswered_qs_selectspec(null, null, 0, $categoryslugs, false, $full, $count)
		);
		break;

	case 'answers':
		$questions = qa_feed_load_ifcategory($categoryslugs, 'main/recent_as_title', 'main/recent_as_in_x', $title,
			qa_db_recent_a_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'comments':
		$questions = qa_feed_load_ifcategory($categoryslugs, 'main/recent_cs_title', 'main/recent_cs_in_x', $title,
			qa_db_recent_c_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'qa':
		$questions = qa_feed_load_ifcategory($categoryslugs, 'main/recent_qs_as_title', 'main/recent_qs_as_in_x', $title,
			qa_db_qs_selectspec(null, 'created', 0, $categoryslugs, null, false, $full, $count),
			qa_db_recent_a_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'activity':
		$questions = qa_feed_load_ifcategory($categoryslugs, 'main/recent_activity_title', 'main/recent_activity_in_x', $title,
			qa_db_qs_selectspec(null, 'created', 0, $categoryslugs, null, false, $full, $count),
			qa_db_recent_a_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count),
			qa_db_recent_c_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count),
			qa_db_recent_edit_qs_selectspec(null, 0, $categoryslugs, null, true, $full, $count)
		);
		break;

	case 'tag':
		$tag = $feedparams[0];

		$questions = qa_feed_load_ifcategory(null, null, null, $title,
			qa_db_tag_recent_qs_selectspec(null, $tag, 0, $full, $count)
		);

		$title = qa_lang_sub('main/questions_tagged_x', $tag);
		$linkrequest = 'tag/' . $tag;
		break;

	case 'search':
		require_once QA_INCLUDE_DIR . 'app/search.php';

		$query = $feedparams[0];

		$results = qa_get_search_results($query, 0, $count, null, true, $full);

		$title = qa_lang_sub('main/results_for_x', $query);
		$linkrequest = 'search';
		$linkparams = array('q' => $query);

		$questions = array();

		foreach ($results as $result) {
			$setarray = array(
				'title' => $result['title'],
				'url' => $result['url'],
			);

			if (isset($result['question']))
				$questions[] = array_merge($result['question'], $setarray);
			elseif (isset($result['url']))
				$questions[] = $setarray;
		}
		break;
}


// Remove duplicate questions (perhaps referenced in an answer and a comment) and cut down to size

require_once QA_INCLUDE_DIR . 'app/format.php';
require_once QA_INCLUDE_DIR . 'app/updates.php';
require_once QA_INCLUDE_DIR . 'app/posts.php';
require_once QA_INCLUDE_DIR . 'util/string.php';

if ($feedtype != 'search' && $feedtype != 'hot') // leave search results and hot questions sorted by relevance
	$questions = qa_any_sort_and_dedupe($questions);

// If there are no questions, raise a 404 error but show the feed so users can still subscribe if wanted
if (count($questions) === 0) {
	qa_404();
}

$questions = array_slice($questions, 0, $count);
$blockwordspreg = qa_get_block_words_preg();


// Prepare the XML output

$lines = array();

$lines[] = '<?xml version="1.0" encoding="utf-8"?>';
$lines[] = '<rss version="2.0">';
$lines[] = '<channel>';

$lines[] = '<title>' . qa_xml($sitetitle . ' - ' . $title) . '</title>';
$lines[] = '<link>' . qa_xml(qa_path($linkrequest, $linkparams, $siteurl)) . '</link>';
$lines[] = '<description>Powered by Question2Answer</description>';

foreach ($questions as $question) {
	// Determine whether this is a question, answer or comment, and act accordingly
	$options = array('blockwordspreg' => @$blockwordspreg, 'showurllinks' => $showurllinks);

	$time = null;
	$htmlcontent = null;

	if (isset($question['opostid'])) {
		$time = $question['otime'];

		if ($full)
			$htmlcontent = qa_viewer_html($question['ocontent'], $question['oformat'], $options);

	} elseif (isset($question['postid'])) {
		$time = $question['created'];

		if ($full)
			$htmlcontent = qa_viewer_html($question['content'], $question['format'], $options);
	}

	if ($feedtype == 'search') {
		$titleprefix = '';
		$urlxml = qa_xml($question['url']);

	} else {
		switch (@$question['obasetype'] . '-' . @$question['oupdatetype']) {
			case 'Q-':
			case '-':
				$langstring = null;
				break;

			case 'Q-' . QA_UPDATE_VISIBLE:
				$langstring = $question['hidden'] ? 'misc/feed_hidden_prefix' : 'misc/feed_reshown_prefix';
				break;

			case 'Q-' . QA_UPDATE_CLOSED:
				$langstring = qa_post_is_closed($question) ? 'misc/feed_closed_prefix' : 'misc/feed_reopened_prefix';
				break;

			case 'Q-' . QA_UPDATE_TAGS:
				$langstring = 'misc/feed_retagged_prefix';
				break;

			case 'Q-' . QA_UPDATE_CATEGORY:
				$langstring = 'misc/feed_recategorized_prefix';
				break;

			case 'A-':
				$langstring = 'misc/feed_a_prefix';
				break;

			case 'A-' . QA_UPDATE_SELECTED:
				$langstring = 'misc/feed_a_selected_prefix';
				break;

			case 'A-' . QA_UPDATE_VISIBLE:
				$langstring = $question['ohidden'] ? 'misc/feed_hidden_prefix' : 'misc/feed_a_reshown_prefix';
				break;

			case 'A-' . QA_UPDATE_CONTENT:
				$langstring = 'misc/feed_a_edited_prefix';
				break;

			case 'C-':
				$langstring = 'misc/feed_c_prefix';
				break;

			case 'C-' . QA_UPDATE_TYPE:
				$langstring = 'misc/feed_c_moved_prefix';
				break;

			case 'C-' . QA_UPDATE_VISIBLE:
				$langstring = $question['ohidden'] ? 'misc/feed_hidden_prefix' : 'misc/feed_c_reshown_prefix';
				break;

			case 'C-' . QA_UPDATE_CONTENT:
				$langstring = 'misc/feed_c_edited_prefix';
				break;

			case 'Q-' . QA_UPDATE_CONTENT:
			default:
				$langstring = 'misc/feed_edited_prefix';
				break;

		}

		$titleprefix = isset($langstring) ? qa_lang($langstring) : '';

		$urlxml = qa_xml(qa_q_path($question['postid'], $question['title'], true, @$question['obasetype'], @$question['opostid']));
	}

	if (isset($blockwordspreg))
		$question['title'] = qa_block_words_replace($question['title'], $blockwordspreg);

	// Build the inner XML structure for each item

	$lines[] = '<item>';
	$lines[] = '<title>' . qa_xml($titleprefix . $question['title']) . '</title>';
	$lines[] = '<link>' . $urlxml . '</link>';

	if (isset($htmlcontent))
		$lines[] = '<description>' . qa_xml($htmlcontent) . '</description>';

	if (isset($question['categoryname']))
		$lines[] = '<category>' . qa_xml($question['categoryname']) . '</category>';

	$lines[] = '<guid isPermaLink="true">' . $urlxml . '</guid>';

	if (isset($time))
		$lines[] = '<pubDate>' . qa_xml(gmdate('r', $time)) . '</pubDate>';

	$lines[] = '</item>';
}

$lines[] = '</channel>';
$lines[] = '</rss>';


// Disconnect here, once all output is ready to go

$qa_db->disconnect();


// Output the XML - and we're done!

header('Content-type: text/xml; charset=utf-8');
echo implode("\n", $lines);
