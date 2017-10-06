<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for top scoring users page


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

require_once QA_INCLUDE_DIR . 'db/users.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'app/format.php';


// Get list of all users

$start = qa_get_start();
$users = qa_db_select_with_pending(qa_db_top_users_selectspec($start, qa_opt_if_loaded('page_size_users')));

$usercount = qa_opt('cache_userpointscount');
$pagesize = qa_opt('page_size_users');
$users = array_slice($users, 0, $pagesize);
$usershtml = qa_userids_handles_html($users);


// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = qa_lang_html('main/highest_users');

$qa_content['ranking'] = array(
	'items' => array(),
	'rows' => ceil($pagesize / qa_opt('columns_users')),
	'type' => 'users',
	'sort' => 'points',
);

if (count($users)) {
	foreach ($users as $userid => $user) {
		if (QA_FINAL_EXTERNAL_USERS)
			$avatarhtml = qa_get_external_avatar_html($user['userid'], qa_opt('avatar_users_size'), true);
		else {
			$avatarhtml = qa_get_user_avatar_html($user['flags'], $user['email'], $user['handle'],
				$user['avatarblobid'], $user['avatarwidth'], $user['avatarheight'], qa_opt('avatar_users_size'), true);
		}

		// avatar and handle now listed separately for use in themes
		$qa_content['ranking']['items'][] = array(
			'avatar' => $avatarhtml,
			'label' => $usershtml[$user['userid']],
			'score' => qa_html(qa_format_number($user['points'], 0, true)),
			'raw' => $user,
		);
	}
} else {
	$qa_content['title'] = qa_lang_html('main/no_active_users');
}

$qa_content['canonical'] = qa_get_canonical();

$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $usercount, qa_opt('pages_prev_next'));

$qa_content['navigation']['sub'] = qa_users_sub_navigation();


return $qa_content;
