<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/pages/users-newest.php
	Description: Controller for newest users page


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

// Check we're not using single-sign on integration

if (QA_FINAL_EXTERNAL_USERS) {
	qa_fatal_error('User accounts are handled by external code');
}


// Check we have permission to view this page (moderator or above)

if (qa_user_permit_error('permit_view_new_users_page')) {
	$qa_content = qa_content_prepare();
	$qa_content['error'] = qa_lang_html('users/no_permission');
	return $qa_content;
}


// Get list of all users

$start = qa_get_start();
$users = qa_db_select_with_pending(qa_db_newest_users_selectspec($start, qa_opt_if_loaded('page_size_users')));

$userCount = qa_opt('cache_userpointscount');
$pageSize = qa_opt('page_size_users');
$users = array_slice($users, 0, $pageSize);
$usersHtml = qa_userids_handles_html($users);

// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = qa_lang_html('main/newest_users');

$qa_content['ranking'] = array(
	'items' => array(),
	'rows' => ceil($pageSize / qa_opt('columns_users')),
	'type' => 'users',
	'sort' => 'date',
);

if (!empty($users)) {
	foreach ($users as $user) {
		$avatarHtml = qa_get_user_avatar_html($user['flags'], $user['email'], $user['handle'],
			$user['avatarblobid'], $user['avatarwidth'], $user['avatarheight'], qa_opt('avatar_users_size'), true);

		$when = qa_when_to_html($user['created'], 7);
		$qa_content['ranking']['items'][] = array(
			'avatar' => $avatarHtml,
			'label' => $usersHtml[$user['userid']],
			'score' => $when['data'],
			'raw' => $user,
		);
	}
} else {
	$qa_content['title'] = qa_lang_html('main/no_active_users');
}

$qa_content['canonical'] = qa_get_canonical();

$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pageSize, $userCount, qa_opt('pages_prev_next'));

$qa_content['navigation']['sub'] = qa_users_sub_navigation();


return $qa_content;
