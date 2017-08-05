<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for sub-page listing user's favorites of a certain type


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
	header('Location: ../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'app/format.php';
require_once QA_INCLUDE_DIR . 'app/favorites.php';


// Data for functions to run

$favswitch = array(
	'questions' => array(
		'page_opt' => 'page_size_qs',
		'fn_spec' => 'qa_db_user_favorite_qs_selectspec',
		'fn_view' => 'qa_favorite_q_list_view',
		'key' => 'q_list',
	),
	'users' => array(
		'page_opt' => 'page_size_users',
		'fn_spec' => 'qa_db_user_favorite_users_selectspec',
		'fn_view' => 'qa_favorite_users_view',
		'key' => 'ranking_users',
	),
	'tags' => array(
		'page_opt' => 'page_size_tags',
		'fn_spec' => 'qa_db_user_favorite_tags_selectspec',
		'fn_view' => 'qa_favorite_tags_view',
		'key' => 'ranking_tags',
	),
);


// Check that we're logged in

$userid = qa_get_logged_in_userid();

if (!isset($userid))
	qa_redirect('login');


// Get lists of favorites of this type

$favtype = qa_request_part(1);
$start = qa_get_start();

if (!array_key_exists($favtype, $favswitch) || ($favtype === 'users' && QA_FINAL_EXTERNAL_USERS))
	return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

extract($favswitch[$favtype]); // get switch variables

$pagesize = qa_opt($page_opt);
list($totalItems, $items) = qa_db_select_with_pending(
	qa_db_selectspec_count($fn_spec($userid)),
	$fn_spec($userid, $pagesize, $start)
);

$count = $totalItems['count'];
$usershtml = qa_userids_handles_html($items);


// Prepare and return content for theme

$qa_content = qa_content_prepare(true);

$qa_content['title'] = qa_lang_html('misc/my_favorites_title');

$qa_content[$key] = $fn_view($items, $usershtml);


// Sub navigation for account pages and suggestion

$qa_content['suggest_next'] = qa_lang_html_sub('misc/suggest_favorites_add', '<span class="qa-favorite-image">&nbsp;</span>');

$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

$qa_content['navigation']['sub'] = qa_user_sub_navigation(qa_get_logged_in_handle(), 'favorites', true);


return $qa_content;
