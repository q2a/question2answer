<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for admin page showing users with non-standard privileges


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
require_once QA_INCLUDE_DIR . 'app/users.php';
require_once QA_INCLUDE_DIR . 'app/format.php';

// Check we're not using single-sign on integration

if (QA_FINAL_EXTERNAL_USERS) {
	header('HTTP/1.1 404 Not Found');
	echo qa_lang_html('main/page_not_found');
	qa_exit();
}


// Check we have permission to view this page (moderator or above)

if (qa_user_permit_error('permit_view_special_users_page')) {
	$qa_content = qa_content_prepare();
	$qa_content['error'] = qa_lang_html('users/no_permission');
	return $qa_content;
}


// Get list of special users

$start = qa_get_start();
$specialUsersSpec = qa_db_users_from_level_selectspec(QA_USER_LEVEL_EXPERT, $start, qa_opt_if_loaded('page_size_users'));
$specialUsersSpecCount = qa_db_users_from_level_count_selectspec(QA_USER_LEVEL_EXPERT);

list($specialUsers, $specialUsersCount) = qa_db_select_with_pending($specialUsersSpec, $specialUsersSpecCount);

$count = $specialUsersCount['count'];
$pageSize = qa_opt('page_size_users');
$usershtml = qa_userids_handles_html($specialUsers);


// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = qa_lang_html('users/special_users');

$qa_content['ranking'] = array(
	'items' => array(),
	'rows' => ceil(qa_opt('page_size_users') / qa_opt('columns_users')),
	'type' => 'users',
	'sort' => 'level',
);

foreach ($specialUsers as $user) {
	$qa_content['ranking']['items'][] = array(
		'label' => $usershtml[$user['userid']],
		'score' => qa_html(qa_user_level_string($user['level'])),
		'raw' => $user,
	);
}

$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pageSize, $count, qa_opt('pages_prev_next'));

$qa_content['navigation']['sub'] = qa_users_sub_navigation();


return $qa_content;
