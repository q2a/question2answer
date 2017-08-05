<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for page listing user's favorites


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


// Check that we're logged in

$userid = qa_get_logged_in_userid();

if (!isset($userid))
	qa_redirect('login');


// Get lists of favorites for this user

$pagesize_qs = qa_opt('page_size_qs');
$pagesize_users = qa_opt('page_size_users');
$pagesize_tags = qa_opt('page_size_tags');

list($numQs, $questions, $numUsers, $users, $numTags, $tags, $categories) = qa_db_select_with_pending(
	qa_db_selectspec_count(qa_db_user_favorite_qs_selectspec($userid)),
	qa_db_user_favorite_qs_selectspec($userid, $pagesize_qs),

	QA_FINAL_EXTERNAL_USERS ? null : qa_db_selectspec_count(qa_db_user_favorite_users_selectspec($userid)),
	QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_favorite_users_selectspec($userid, $pagesize_users),

	qa_db_selectspec_count(qa_db_user_favorite_tags_selectspec($userid)),
	qa_db_user_favorite_tags_selectspec($userid, $pagesize_tags),

	qa_db_user_favorite_categories_selectspec($userid)
);

$usershtml = qa_userids_handles_html(QA_FINAL_EXTERNAL_USERS ? $questions : array_merge($questions, $users));


// Prepare and return content for theme

$qa_content = qa_content_prepare(true);

$qa_content['title'] = qa_lang_html('misc/my_favorites_title');


// Favorite questions

$qa_content['q_list'] = qa_favorite_q_list_view($questions, $usershtml);
$qa_content['q_list']['title'] = count($questions) ? qa_lang_html('main/nav_qs') : qa_lang_html('misc/no_favorite_qs');
if ($numQs['count'] > count($questions)) {
	$url = qa_path_html('favorites/questions', array('start' => $pagesize_qs));
	$qa_content['q_list']['footer'] = '<p class="qa-link-next"><a href="' . $url . '">' . qa_lang_html('misc/more_favorite_qs') . '</a></p>';
}


// Favorite users

if (!QA_FINAL_EXTERNAL_USERS) {
	$qa_content['ranking_users'] = qa_favorite_users_view($users, $usershtml);
	$qa_content['ranking_users']['title'] = count($users) ? qa_lang_html('main/nav_users') : qa_lang_html('misc/no_favorite_users');
	if ($numUsers['count'] > count($users)) {
		$url = qa_path_html('favorites/users', array('start' => $pagesize_users));
		$qa_content['ranking_users']['footer'] = '<p class="qa-link-next"><a href="' . $url . '">' . qa_lang_html('misc/more_favorite_users') . '</a></p>';
	}
}


// Favorite tags

if (qa_using_tags()) {
	$qa_content['ranking_tags'] = qa_favorite_tags_view($tags);
	$qa_content['ranking_tags']['title'] = count($tags) ? qa_lang_html('main/nav_tags') : qa_lang_html('misc/no_favorite_tags');
	if ($numTags['count'] > count($tags)) {
		$url = qa_path_html('favorites/tags', array('start' => $pagesize_tags));
		$qa_content['ranking_tags']['footer'] = '<p class="qa-link-next"><a href="' . $url . '">' . qa_lang_html('misc/more_favorite_tags') . '</a></p>';
	}
}


// Favorite categories (no pagination)

if (qa_using_categories()) {
	$qa_content['nav_list_categories'] = qa_favorite_categories_view($categories);
	$qa_content['nav_list_categories']['title'] = count($categories) ? qa_lang_html('main/nav_categories') : qa_lang_html('misc/no_favorite_categories');
}


// Sub navigation for account pages and suggestion

$qa_content['suggest_next'] = qa_lang_html_sub('misc/suggest_favorites_add', '<span class="qa-favorite-image">&nbsp;</span>');

$qa_content['navigation']['sub'] = qa_user_sub_navigation(qa_get_logged_in_handle(), 'favorites', true);


return $qa_content;
