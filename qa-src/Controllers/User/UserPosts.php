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

use Q2A\Http\Exceptions\PageNotFoundException;

require_once QA_INCLUDE_DIR . 'db/users.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'app/users.php';
require_once QA_INCLUDE_DIR . 'app/format.php';

class UserPosts extends \Q2A\Controllers\BaseController
{
	/**
	 * @param string $handle
	 *
	 * @return array
	 * @throws PageNotFoundException
	 */
	public function activity($handle)
	{
		$profileUserId = $this->getUserIdOrFail($handle);

		// Find the recent activity for this user

		$loggedInUserId = qa_get_logged_in_userid();

		list($questions, $answerQs, $commentQs, $editQs) = $this->fetchActivityDataFromDb($handle, $loggedInUserId, $profileUserId);

		// Get information on user references

		$questions = qa_any_sort_and_dedupe(array_merge($questions, $answerQs, $commentQs, $editQs));
		$questions = array_slice($questions, 0, qa_opt('page_size_activity'));
		$usersHtml = qa_userids_handles_html(qa_any_get_userids_handles($questions), false);

		// Prepare content for theme

		$qa_content = qa_content_prepare(true);

		$qa_content['title'] = qa_lang_html_sub(
			empty($questions) ? 'profile/no_posts_by_x' : 'profile/recent_activity_by_x',
			$this->userHtml($handle, $profileUserId)
		);

		// Recent activity by this user

		$this->addForm($qa_content);

		$qa_content['q_list']['qs'] = array();

		$htmlDefaults = qa_post_html_defaults('Q');
		$htmlDefaults['whoview'] = false;
		$htmlDefaults['voteview'] = false;
		$htmlDefaults['avatarsize'] = 0;

		foreach ($questions as $question) {
			$qa_content['q_list']['qs'][] = qa_any_to_q_html_fields(
				$question,
				$loggedInUserId,
				qa_cookie_get(),
				$usersHtml,
				null,
				array('voteview' => false) + qa_post_html_options($question, $htmlDefaults)
			);
		}

		// Sub menu for navigation in user pages

		$this->addNavigation($qa_content, $handle, $loggedInUserId, $profileUserId, 'activity');

		return $qa_content;
	}

	/**
	 * @param string $handle
	 *
	 * @return array
	 * @throws PageNotFoundException
	 */
	public function questions($handle)
	{
		$profileUserId = $this->getUserIdOrFail($handle);

		// Find the questions for this user

		$loggedInUserId = qa_get_logged_in_userid();

		list($userPoints, $questions) = $this->fetchQuestionsDataFromDb($handle, $loggedInUserId, $profileUserId);

		// Get information on user questions

		$pageSize = qa_opt('page_size_qs');
		$count = (int)@$userPoints['qposts'];
		$questions = array_slice($questions, 0, $pageSize);
		$usersHtml = qa_userids_handles_html($questions, false);

		// Prepare content for theme

		$qa_content = qa_content_prepare(true);

		$qa_content['title'] = qa_lang_html_sub(
			empty($questions) ? 'profile/no_questions_by_x' : 'profile/questions_by_x',
			$this->userHtml($handle, $profileUserId)
		);

		// Recent questions by this user

		$this->addForm($qa_content);

		$qa_content['q_list']['qs'] = array();

		$htmlDefaults = qa_post_html_defaults('Q');
		$htmlDefaults['whoview'] = false;
		$htmlDefaults['avatarsize'] = 0;

		foreach ($questions as $question) {
			$qa_content['q_list']['qs'][] = qa_post_html_fields(
				$question,
				$loggedInUserId,
				qa_cookie_get(),
				$usersHtml,
				null,
				qa_post_html_options($question, $htmlDefaults)
			);
		}

		$this->addPageLinks($qa_content, $pageSize, $count);
		$this->addNavigation($qa_content, $handle, $loggedInUserId, $profileUserId, 'questions');

		return $qa_content;
	}

	/**
	 * @param string $handle
	 *
	 * @return array
	 * @throws PageNotFoundException
	 */
	public function answers($handle)
	{
		$profileUserId = $this->getUserIdOrFail($handle);

		// Find the questions for this user

		$loggedInUserId = qa_get_logged_in_userid();

		list($userPoints, $questions) = $this->fetchAnswersDataFromDb($handle, $loggedInUserId, $profileUserId);

		// Get information on user questions

		$pagesize = qa_opt('page_size_activity');
		$count = (int)@$userPoints['aposts'];
		$questions = array_slice($questions, 0, $pagesize);
		$usersHtml = qa_userids_handles_html($questions, false);

		// Prepare content for theme

		$qa_content = qa_content_prepare(true);

		$qa_content['title'] = qa_lang_html_sub(
			empty($questions) ? 'profile/no_answers_by_x' : 'profile/answers_by_x',
			$this->userHtml($handle, $profileUserId)
		);

		// Recent questions by this user

		$this->addForm($qa_content);

		$qa_content['q_list']['qs'] = array();

		$htmlDefaults = qa_post_html_defaults('Q');
		$htmlDefaults['whoview'] = false;
		$htmlDefaults['avatarsize'] = 0;
		$htmlDefaults['ovoteview'] = true;
		$htmlDefaults['answersview'] = false;

		$voteView = qa_get_vote_view('A', false, false);

		foreach ($questions as $question) {
			$options = qa_post_html_options($question, $htmlDefaults);
			$options['voteview'] = $voteView;

			$qa_content['q_list']['qs'][] = qa_other_to_q_html_fields(
				$question,
				$loggedInUserId,
				qa_cookie_get(),
				$usersHtml,
				null,
				$options
			);
		}

		$this->addPageLinks($qa_content, $pagesize, $count);
		$this->addNavigation($qa_content, $handle, $loggedInUserId, $profileUserId, 'answers');

		return $qa_content;
	}

	/**
	 * Return the HTML to display for the handle, and if we're using external users, determine the userid.
	 *
	 * @param string $handle
	 * @param mixed $userId
	 *
	 * @return mixed|string
	 */
	private function userHtml($handle, $userId)
	{
		if (QA_FINAL_EXTERNAL_USERS) {
			$usersHtml = qa_get_users_html(array($userId), false, qa_path_to_root(), true);

			return @$usersHtml[$userId];
		} else {
			return qa_html($handle);
		}
	}

	/**
	 * @param string $handle
	 * @param mixed $loggedInUserId
	 * @param mixed $userId
	 *
	 * @return array
	 */
	private function fetchActivityDataFromDb($handle, $loggedInUserId, $userId)
	{
		$identifier = $this->getGenericUserIdentifier($userId, $handle);

		return qa_db_select_with_pending(
			qa_db_user_recent_qs_selectspec($loggedInUserId, $identifier, qa_opt_if_loaded('page_size_activity')),
			qa_db_user_recent_a_qs_selectspec($loggedInUserId, $identifier),
			qa_db_user_recent_c_qs_selectspec($loggedInUserId, $identifier),
			qa_db_user_recent_edit_qs_selectspec($loggedInUserId, $identifier)
		);
	}

	/**
	 * @param string $handle
	 * @param mixed $loginuserid
	 * @param mixed $userId
	 *
	 * @return array
	 */
	private function fetchQuestionsDataFromDb($handle, $loginuserid, $userId)
	{
		$identifier = $this->getGenericUserIdentifier($userId, $handle);

		return qa_db_select_with_pending(
			qa_db_user_points_selectspec($identifier),
			qa_db_user_recent_qs_selectspec($loginuserid, $identifier, qa_opt_if_loaded('page_size_qs'), qa_get_start())
		);
	}

	/**
	 * @param string $handle
	 * @param mixed $loggedInUserId
	 * @param mixed $userId
	 *
	 * @return array
	 */
	private function fetchAnswersDataFromDb($handle, $loggedInUserId, $userId)
	{
		$identifier = $this->getGenericUserIdentifier($userId, $handle);

		return qa_db_select_with_pending(
			qa_db_user_points_selectspec($identifier),
			qa_db_user_recent_a_qs_selectspec($loggedInUserId, $identifier, qa_opt_if_loaded('page_size_activity'), qa_get_start())
		);
	}

	/**
	 * @param string $handle
	 *
	 * @return mixed
	 * @throws PageNotFoundException
	 */
	private function getUserIdOrFail($handle)
	{
		$userId = qa_handle_to_userid($handle);

		if (is_null($userId)) { // check the user exists
			throw new PageNotFoundException();
		}

		return $userId;
	}

	/**
	 * @param mixed $userId
	 * @param string $handle
	 *
	 * @return mixed
	 */
	private function getGenericUserIdentifier($userId, $handle)
	{
		return QA_FINAL_EXTERNAL_USERS ? $userId : $handle;
	}

	/**
	 * Add page links and sub menu for navigation in user pages.
	 *
	 * @param array $qa_content
	 * @param int $pageSize
	 * @param int $count
	 */
	private function addPageLinks(&$qa_content, $pageSize, $count)
	{
		$qa_content['page_links'] = qa_html_page_links(qa_request(), qa_get_start(), $pageSize, $count, qa_opt('pages_prev_next'));
	}

	/**
	 * Add sub menu for navigation in user pages to the qa_content array.
	 *
	 * @param array $qa_content
	 * @param string $handle
	 * @param mixed $loggedInUserId
	 * @param mixed $userId
	 * @param string $selectedNavigation
	 */
	private function addNavigation(&$qa_content, $handle, $loggedInUserId, $userId, $selectedNavigation)
	{
		$isMyUser = isset($loggedInUserId) && $loggedInUserId == $userId;
		$qa_content['navigation']['sub'] = qa_user_sub_navigation($handle, $selectedNavigation, $isMyUser);
	}

	/**
	 * @param array $qa_content
	 */
	private function addForm(&$qa_content)
	{
		$qa_content['q_list']['form'] = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'hidden' => array(
				'code' => qa_get_form_security_code('vote'),
			),
		);
	}
}
