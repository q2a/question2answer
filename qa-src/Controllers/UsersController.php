<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

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

namespace Q2A\Controllers;

class UsersController extends BaseController
{
	public function top()
	{
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


		// set the canonical url based on possible pagination
		$qa_content['canonical'] = qa_path_html(qa_request(), ($start > 0 ? array('start' => $start) : null), qa_opt('site_url'));

		$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $usercount, qa_opt('pages_prev_next'));

		$qa_content['navigation']['sub'] = qa_users_sub_navigation();


		return $qa_content;
	}

	public function newest()
	{
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'app/format.php';

		// Check we're not using single-sign on integration

		if (QA_FINAL_EXTERNAL_USERS)
			qa_fatal_error('User accounts are handled by external code');


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

		// set the canonical url based on possible pagination
		$qa_content['canonical'] = qa_path_html(qa_request(), ($start > 0 ? array('start' => $start) : null), qa_opt('site_url'));

		$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pageSize, $userCount, qa_opt('pages_prev_next'));

		$qa_content['navigation']['sub'] = qa_users_sub_navigation();

		return $qa_content;
	}

	public function special()
	{
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'app/users.php';
		require_once QA_INCLUDE_DIR . 'app/format.php';


		// Check we're not using single-sign on integration

		if (QA_FINAL_EXTERNAL_USERS)
			qa_fatal_error('User accounts are handled by external code');


		// Get list of special users

		$users = qa_db_select_with_pending(qa_db_users_from_level_selectspec(QA_USER_LEVEL_EXPERT));


		// Check we have permission to view this page (moderator or above)

		if (qa_user_permit_error('permit_view_special_users_page')) {
			$qa_content = qa_content_prepare();
			$qa_content['error'] = qa_lang_html('users/no_permission');
			return $qa_content;
		}


		// Get userids and handles of retrieved users

		$usershtml = qa_userids_handles_html($users);


		// Prepare content for theme

		$qa_content = qa_content_prepare();

		$qa_content['title'] = qa_lang_html('users/special_users');

		$qa_content['ranking'] = array(
			'items' => array(),
			'rows' => ceil(qa_opt('page_size_users') / qa_opt('columns_users')),
			'type' => 'users',
			'sort' => 'level',
		);

		foreach ($users as $user) {
			$qa_content['ranking']['items'][] = array(
				'label' => $usershtml[$user['userid']],
				'score' => qa_html(qa_user_level_string($user['level'])),
				'raw' => $user,
			);
		}

		$qa_content['navigation']['sub'] = qa_users_sub_navigation();

		return $qa_content;
	}

	public function blocked()
	{
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'app/users.php';
		require_once QA_INCLUDE_DIR . 'app/format.php';


		// Check we're not using single-sign on integration

		if (QA_FINAL_EXTERNAL_USERS)
			qa_fatal_error('User accounts are handled by external code');


		// Get list of blocked users

		$start = qa_get_start();
		$pagesize = qa_opt('page_size_users');

		$userSpecCount = qa_db_selectspec_count(qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED));
		$userSpec = qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED, $start, $pagesize);

		list($numUsers, $users) = qa_db_select_with_pending($userSpecCount, $userSpec);
		$count = $numUsers['count'];


		// Check we have permission to view this page (moderator or above)

		if (qa_get_logged_in_level() < QA_USER_LEVEL_MODERATOR) {
			$qa_content = qa_content_prepare();
			$qa_content['error'] = qa_lang_html('users/no_permission');
			return $qa_content;
		}


		// Get userids and handles of retrieved users

		$usershtml = qa_userids_handles_html($users);


		// Prepare content for theme

		$qa_content = qa_content_prepare();

		$qa_content['title'] = $count > 0 ? qa_lang_html('users/blocked_users') : qa_lang_html('users/no_blocked_users');

		$qa_content['ranking'] = array(
			'items' => array(),
			'rows' => ceil(count($users) / qa_opt('columns_users')),
			'type' => 'users',
			'sort' => 'level',
		);

		foreach ($users as $user) {
			$qa_content['ranking']['items'][] = array(
				'label' => $usershtml[$user['userid']],
				'score' => qa_html(qa_user_level_string($user['level'])),
				'raw' => $user,
			);
		}

		$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

		$qa_content['navigation']['sub'] = qa_users_sub_navigation();


		return $qa_content;
	}
}
