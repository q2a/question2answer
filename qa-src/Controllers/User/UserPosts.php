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

use Q2A\Controllers\BaseController;
use Q2A\Database\DbConnection;
use Q2A\Http\Exceptions\PageNotFoundException;

class UserPosts extends BaseController
{
	protected $userid;
	protected $userhtml;

	public function __construct(DbConnection $db)
	{
		require_once QA_INCLUDE_DIR . 'db/users.php';
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'app/users.php';
		require_once QA_INCLUDE_DIR . 'app/format.php';

		parent::__construct($db);
	}

	/**
	 * @param string $handle
	 *
	 * @return array
	 * @throws PageNotFoundException
	 */
	public function activity($handle)
	{
		$this->userHtml($handle);

		// Find the recent activity for this user

		$loginuserid = qa_get_logged_in_userid();
		$identifier = QA_FINAL_EXTERNAL_USERS ? $this->userid : $handle;

		list($useraccount, $questions, $answerqs, $commentqs, $editqs) = qa_db_select_with_pending(
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_account_selectspec($handle, false),
			qa_db_user_recent_qs_selectspec($loginuserid, $identifier, qa_opt_if_loaded('page_size_activity')),
			qa_db_user_recent_a_qs_selectspec($loginuserid, $identifier),
			qa_db_user_recent_c_qs_selectspec($loginuserid, $identifier),
			qa_db_user_recent_edit_qs_selectspec($loginuserid, $identifier)
		);

		if (!QA_FINAL_EXTERNAL_USERS && !is_array($useraccount)) { // check the user exists
			throw new PageNotFoundException();
		}
		// Get information on user references

		$questions = qa_any_sort_and_dedupe(array_merge($questions, $answerqs, $commentqs, $editqs));
		$questions = array_slice($questions, 0, qa_opt('page_size_activity'));
		$usershtml = qa_userids_handles_html(qa_any_get_userids_handles($questions), false);


		// Prepare content for theme

		$qa_content = qa_content_prepare(true);

		if (count($questions)) {
			$qa_content['title'] = qa_lang_html_sub('profile/recent_activity_by_x', $this->userhtml);
		} else {
			$qa_content['title'] = qa_lang_html_sub('profile/no_posts_by_x', $this->userhtml);
		}


		// Recent activity by this user

		$qa_content['q_list']['form'] = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'hidden' => array(
				'code' => qa_get_form_security_code('vote'),
			),
		);

		$qa_content['q_list']['qs'] = array();

		$htmldefaults = qa_post_html_defaults('Q');
		$htmldefaults['whoview'] = false;
		$htmldefaults['voteview'] = false;
		$htmldefaults['avatarsize'] = 0;

		foreach ($questions as $question) {
			$qa_content['q_list']['qs'][] = qa_any_to_q_html_fields(
				$question,
				$loginuserid,
				qa_cookie_get(),
				$usershtml,
				null,
				array('voteview' => false) + qa_post_html_options($question, $htmldefaults)
			);
		}


		// Sub menu for navigation in user pages

		$ismyuser = isset($loginuserid) && $loginuserid == (QA_FINAL_EXTERNAL_USERS ? $this->userid : $useraccount['userid']);
		$qa_content['navigation']['sub'] = qa_user_sub_navigation($handle, 'activity', $ismyuser);


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
		$this->userHtml($handle);

		$start = qa_get_start();

		// Find the questions for this user

		$loginuserid = qa_get_logged_in_userid();
		$identifier = QA_FINAL_EXTERNAL_USERS ? $this->userid : $handle;

		list($useraccount, $userpoints, $questions) = qa_db_select_with_pending(
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_account_selectspec($handle, false),
			qa_db_user_points_selectspec($identifier),
			qa_db_user_recent_qs_selectspec($loginuserid, $identifier, qa_opt_if_loaded('page_size_qs'), $start)
		);

		if (!QA_FINAL_EXTERNAL_USERS && !is_array($useraccount)) { // check the user exists
			throw new PageNotFoundException();
		}


		// Get information on user questions

		$pagesize = qa_opt('page_size_qs');
		$count = (int)@$userpoints['qposts'];
		$questions = array_slice($questions, 0, $pagesize);
		$usershtml = qa_userids_handles_html($questions, false);


		// Prepare content for theme

		$qa_content = qa_content_prepare(true);

		if (count($questions)) {
			$qa_content['title'] = qa_lang_html_sub('profile/questions_by_x', $this->userhtml);
		} else {
			$qa_content['title'] = qa_lang_html_sub('profile/no_questions_by_x', $this->userhtml);
		}


		// Recent questions by this user

		$qa_content['q_list']['form'] = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'hidden' => array(
				'code' => qa_get_form_security_code('vote'),
			),
		);

		$qa_content['q_list']['qs'] = array();

		$htmldefaults = qa_post_html_defaults('Q');
		$htmldefaults['whoview'] = false;
		$htmldefaults['avatarsize'] = 0;

		foreach ($questions as $question) {
			$qa_content['q_list']['qs'][] = qa_post_html_fields(
				$question,
				$loginuserid,
				qa_cookie_get(),
				$usershtml,
				null,
				qa_post_html_options($question, $htmldefaults)
			);
		}

		$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));


		// Sub menu for navigation in user pages

		$ismyuser = isset($loginuserid) && $loginuserid == (QA_FINAL_EXTERNAL_USERS ? $this->userid : $useraccount['userid']);
		$qa_content['navigation']['sub'] = qa_user_sub_navigation($handle, 'questions', $ismyuser);


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
		$this->userHtml($handle);

		$start = qa_get_start();


		// Find the questions for this user

		$loginuserid = qa_get_logged_in_userid();
		$identifier = QA_FINAL_EXTERNAL_USERS ? $this->userid : $handle;

		list($useraccount, $userpoints, $questions) = qa_db_select_with_pending(
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_account_selectspec($handle, false),
			qa_db_user_points_selectspec($identifier),
			qa_db_user_recent_a_qs_selectspec($loginuserid, $identifier, qa_opt_if_loaded('page_size_activity'), $start)
		);

		if (!QA_FINAL_EXTERNAL_USERS && !is_array($useraccount)) { // check the user exists
			throw new PageNotFoundException();
		}


		// Get information on user questions

		$pagesize = qa_opt('page_size_activity');
		$count = (int)@$userpoints['aposts'];
		$questions = array_slice($questions, 0, $pagesize);
		$usershtml = qa_userids_handles_html($questions, false);


		// Prepare content for theme

		$qa_content = qa_content_prepare(true);

		if (count($questions)) {
			$qa_content['title'] = qa_lang_html_sub('profile/answers_by_x', $this->userhtml);
		} else {
			$qa_content['title'] = qa_lang_html_sub('profile/no_answers_by_x', $this->userhtml);
		}


		// Recent questions by this user

		$qa_content['q_list']['form'] = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'hidden' => array(
				'code' => qa_get_form_security_code('vote'),
			),
		);

		$qa_content['q_list']['qs'] = array();

		$htmldefaults = qa_post_html_defaults('Q');
		$htmldefaults['whoview'] = false;
		$htmldefaults['avatarsize'] = 0;
		$htmldefaults['ovoteview'] = true;
		$htmldefaults['answersview'] = false;

		foreach ($questions as $question) {
			$options = qa_post_html_options($question, $htmldefaults);
			$options['voteview'] = qa_get_vote_view('A', false, false);

			$qa_content['q_list']['qs'][] = qa_other_to_q_html_fields($question, $loginuserid, qa_cookie_get(), $usershtml, null, $options);
		}

		$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));


		// Sub menu for navigation in user pages

		$ismyuser = isset($loginuserid) && $loginuserid == (QA_FINAL_EXTERNAL_USERS ? $this->userid : $useraccount['userid']);
		$qa_content['navigation']['sub'] = qa_user_sub_navigation($handle, 'answers', $ismyuser);


		return $qa_content;
	}

	/**
	 * Return the HTML to display for the handle, and if we're using external users, determine the userid.
	 *
	 * @param string $handle
	 * @throws PageNotFoundException
	 */
	private function userHtml($handle)
	{
		if (QA_FINAL_EXTERNAL_USERS) {
			$this->userid = qa_handle_to_userid($handle);
			if (!isset($this->userid)) { // check the user exists
				throw new PageNotFoundException();
			}

			$usershtml = qa_get_users_html(array($this->userid), false, qa_path_to_root(), true);
			$this->userhtml = @$usershtml[$this->userid];
		} else {
			$this->userhtml = qa_html($handle);
		}
	}
}
