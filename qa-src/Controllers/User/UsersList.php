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

namespace Q2A\Controllers\User;

use Q2A\Auth\NoPermissionException;
use Q2A\Controllers\BaseController;
use Q2A\Database\DbConnection;
use Q2A\Middleware\Auth\InternalUsersOnly;
use Q2A\Middleware\Auth\MinimumUserLevel;

class UsersList extends BaseController
{
	public function __construct(DbConnection $db)
	{
		require_once QA_INCLUDE_DIR . 'db/users.php';
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'app/users.php';
		require_once QA_INCLUDE_DIR . 'app/format.php';

		parent::__construct($db);

		$this->addMiddleware(new InternalUsersOnly(), array('newest', 'special', 'blocked'));
		$this->addMiddleware(new MinimumUserLevel(QA_USER_LEVEL_MODERATOR), array('blocked'));
	}

	/**
	 * Display top users page (ordered by points)
	 * @return array $qa_content
	 */
	public function top()
	{
		// callables to fetch user data
		$fetchUsers = function ($start, $pageSize) {
			return array(
				qa_opt('cache_userpointscount'),
				qa_db_select_with_pending(qa_db_top_users_selectspec($start, $pageSize))
			);
		};
		$userScore = function ($user) {
			return qa_html(qa_format_number($user['points'], 0, true));
		};

		$qa_content = $this->rankedUsersContent($fetchUsers, $userScore);

		$qa_content['title'] = empty($qa_content['ranking']['items'])
			? qa_lang_html('main/no_active_users')
			: qa_lang_html('main/highest_users');

		$qa_content['ranking']['sort'] = 'points';

		return $qa_content;
	}

	/**
	 * Display newest users page
	 *
	 * @return array $qa_content
	 * @throws NoPermissionException
	 */
	public function newest()
	{
		// check we have permission to view this page (moderator or above)
		if (qa_user_permit_error('permit_view_new_users_page')) {
			throw new NoPermissionException();
		}

		// callables to fetch user data
		$fetchUsers = function ($start, $pageSize) {
			return array(
				qa_opt('cache_userpointscount'),
				qa_db_select_with_pending(qa_db_newest_users_selectspec($start, $pageSize))
			);
		};
		$userDate = function ($user) {
			$when = qa_when_to_html($user['created'], 7);
			return $when['data'];
		};

		$qa_content = $this->rankedUsersContent($fetchUsers, $userDate);

		$qa_content['title'] = empty($qa_content['ranking']['items'])
			? qa_lang_html('main/no_active_users')
			: qa_lang_html('main/newest_users');

		$qa_content['ranking']['sort'] = 'date';

		return $qa_content;
	}

	/**
	 * Display special users page (admins, moderators, etc)
	 *
	 * @return array $qa_content
	 * @throws NoPermissionException
	 */
	public function special()
	{
		// check we have permission to view this page (moderator or above)
		if (qa_user_permit_error('permit_view_special_users_page')) {
			throw new NoPermissionException();
		}

		// callables to fetch user data
		$fetchUsers = function ($start, $pageSize) {
			// here we fetch *all* users to get the total instead of a separate query; there are unlikely to be many special users
			$users = qa_db_select_with_pending(qa_db_users_from_level_selectspec(QA_USER_LEVEL_EXPERT));
			return array(count($users), $users);
		};
		$userLevel = function ($user) {
			return qa_html(qa_user_level_string($user['level']));
		};

		$qa_content = $this->rankedUsersContent($fetchUsers, $userLevel);

		$qa_content['title'] = qa_lang_html('users/special_users');

		$qa_content['ranking']['sort'] = 'level';

		return $qa_content;
	}

	/**
	 * Display blocked users page
	 * @return array $qa_content
	 */
	public function blocked()
	{
		// callables to fetch user data
		$fetchUsers = function ($start, $pageSize) {
			list($totalUsers, $users) = qa_db_select_with_pending(
				qa_db_selectspec_count(qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED)),
				qa_db_users_with_flag_selectspec(QA_USER_FLAGS_USER_BLOCKED, $start, $pageSize)
			);

			return array($totalUsers['count'], $users);
		};
		$userLevel = function ($user) {
			return qa_html(qa_user_level_string($user['level']));
		};

		$qa_content = $this->rankedUsersContent($fetchUsers, $userLevel);

		$qa_content['title'] = empty($qa_content['ranking']['items'])
			? qa_lang_html('users/no_blocked_users')
			: qa_lang_html('users/blocked_users');

		$qa_content['ranking']['sort'] = 'level';

		return $qa_content;
	}

	/**
	 * Fetch $qa_content array for a set of ranked users.
	 * @param  callable $fnUsersAndCount Function that returns the list of users for a page and the user total.
	 * @param  callable $fnUserScore Function that returns the "score" (points, date, etc) that will be displayed.
	 * @return array $qa_content
	 */
	private function rankedUsersContent($fnUsersAndCount, $fnUserScore)
	{
		// get the users to display on this page

		$request = qa_request();
		$start = qa_get_start();
		$pageSize = qa_opt('page_size_users');

		list($totalUsers, $users) = $fnUsersAndCount($start, $pageSize);

		// get userids and handles of retrieved users
		$usersHtml = qa_userids_handles_html($users);

		// prepare content for theme

		$content = qa_content_prepare();

		$content['ranking'] = array(
			'items' => array(),
			'rows' => ceil($pageSize / qa_opt('columns_users')),
			'type' => 'users',
			// 'sort' is handled by calling code
		);

		foreach ($users as $user) {
			if (QA_FINAL_EXTERNAL_USERS) {
				$avatarHtml = qa_get_external_avatar_html($user['userid'], qa_opt('avatar_users_size'), true);
			} else {
				$avatarHtml = qa_get_user_avatar_html(
					$user['flags'],
					$user['email'],
					$user['handle'],
					$user['avatarblobid'],
					$user['avatarwidth'],
					$user['avatarheight'],
					qa_opt('avatar_users_size'),
					true
				);
			}

			$content['ranking']['items'][] = array(
				'avatar' => $avatarHtml,
				'label' => $usersHtml[$user['userid']],
				'score' => $fnUserScore($user),
				'raw' => $user,
			);
		}

		$content['page_links'] = qa_html_page_links($request, $start, $pageSize, $totalUsers, qa_opt('pages_prev_next'));

		$content['canonical'] = qa_get_canonical();

		$content['navigation']['sub'] = qa_users_sub_navigation();

		return $content;
	}
}
