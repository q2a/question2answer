<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for email confirmation page (can also request a new code)


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

// Check we're not using single-sign on integration, that we're not already confirmed, and that we're not blocked

if (QA_FINAL_EXTERNAL_USERS) {
	qa_fatal_error('User login is handled by external code');
}

// Check if we've been asked to send a new link or have a successful email confirmation

// Fetch the handle from POST or GET
$handle = qa_post_text('username');
if (!isset($handle)) {
	$handle = qa_get('u');
}
$handle = trim($handle); // if $handle is null, trim returns an empty string

// Fetch the code from POST or GET
$code = qa_post_text('code');
if (!isset($code)) {
	$code = qa_get('c');
}
$code = trim($code); // if $code is null, trim returns an empty string

$loggedInUserId = qa_get_logged_in_userid();
$emailConfirmationSent = false;
$userConfirmed = false;

$pageError = null;

if (isset($loggedInUserId) && qa_clicked('dosendconfirm')) { // A logged in user requested to be sent a confirmation link
	if (!qa_check_form_security_code('confirm', qa_post_text('formcode'))) {
		$pageError = qa_lang_html('misc/form_security_again');
	} else {
		// For qa_send_new_confirm
		require_once QA_INCLUDE_DIR . 'app/users-edit.php';

		qa_send_new_confirm($loggedInUserId);
		$emailConfirmationSent = true;
	}
} elseif (strlen($code) > 0) { // If there is a code present in the URL
	// For qa_db_select_with_pending, qa_db_user_account_selectspec
	require_once QA_INCLUDE_DIR . 'db/selects.php';

	// For qa_complete_confirm
	require_once QA_INCLUDE_DIR . 'app/users-edit.php';

	if (strlen($handle) > 0) { // If there is a handle present in the URL
		$userInfo = qa_db_select_with_pending(qa_db_user_account_selectspec($handle, false));

		if (strtolower(trim($userInfo['emailcode'])) == strtolower($code)) {
			qa_complete_confirm($userInfo['userid'], $userInfo['email'], $userInfo['handle']);
			$userConfirmed = true;
		}
	}

	if (!$userConfirmed && isset($loggedInUserId)) { // As a backup, also match code on URL against logged in user
		$userInfo = qa_db_select_with_pending(qa_db_user_account_selectspec($loggedInUserId, true));
		$flags = $userInfo['flags'];

		if (($flags & QA_USER_FLAGS_EMAIL_CONFIRMED) > 0 && ($flags & QA_USER_FLAGS_MUST_CONFIRM) == 0) {
			$userConfirmed = true; // if they confirmed before, just show message as if it happened now
		} elseif (strtolower(trim($userInfo['emailcode'])) == strtolower($code)) {
			qa_complete_confirm($userInfo['userid'], $userInfo['email'], $userInfo['handle']);
			$userConfirmed = true;
		}
	}
}

// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = qa_lang_html('users/confirm_title');
$qa_content['error'] = $pageError;

if ($emailConfirmationSent) {
	$qa_content['success'] = qa_lang_html('users/confirm_emailed');

	$email = qa_get_logged_in_email();
	$handle = qa_get_logged_in_handle();

	$qa_content['form'] = array(
		'tags' => 'method="post" action="' . qa_self_html() . '"',

		'style' => 'tall',

		'fields' => array(
			'email' => array(
				'label' => qa_lang_html('users/email_label'),
				'value' => qa_html($email) . strtr(qa_lang_html('users/change_email_link'), array(
						'^1' => '<a href="' . qa_path_html('account') . '">',
						'^2' => '</a>',
					)),
				'type' => 'static',
			),
			'code' => array(
				'label' => qa_lang_html('users/email_code_label'),
				'tags' => 'name="code" id="code"',
				'value' => isset($code) ? qa_html($code) : null,
				'note' => qa_lang_html('users/email_code_emailed') . ' - ' .
					'<a href="' . qa_path_html('confirm') . '">' . qa_lang_html('users/email_code_another') . '</a>',
			),
		),

		'buttons' => array(
			'confirm' => array( // This button does not actually need a name attribute
				'label' => qa_lang_html('users/confirm_button'),
			),
		),

		'hidden' => array(
			'formcode' => qa_get_form_security_code('confirm'),
			'username' => qa_html($handle),
		),
	);

	$qa_content['focusid'] = 'code';
} elseif ($userConfirmed) {
	$qa_content['success'] = qa_lang_html('users/confirm_complete');

	if (!isset($loggedInUserId)) {
		$qa_content['suggest_next'] = strtr(
			qa_lang_html('users/log_in_to_access'),
			array(
				'^1' => '<a href="' . qa_path_html('login', array('e' => $handle)) . '">',
				'^2' => '</a>',
			)
		);
	}
} elseif (isset($loggedInUserId)) { // if logged in, allow sending a fresh link
	require_once QA_INCLUDE_DIR . 'util/string.php';

	if (strlen($code) > 0) {
		$qa_content['error'] = qa_lang_html('users/confirm_wrong_resend');
	}

	$email = qa_get_logged_in_email();

	$qa_content['form'] = array(
		'tags' => 'method="post" action="' . qa_path_html('confirm') . '"',

		'style' => 'tall',

		'fields' => array(
			'email' => array(
				'label' => qa_lang_html('users/email_label'),
				'value' => qa_html($email) . strtr(qa_lang_html('users/change_email_link'), array(
						'^1' => '<a href="' . qa_path_html('account') . '">',
						'^2' => '</a>',
					)),
				'type' => 'static',
			),
		),

		'buttons' => array(
			'send' => array(
				'tags' => 'name="dosendconfirm"',
				'label' => qa_lang_html('users/send_confirm_button'),
			),
		),

		'hidden' => array(
			'formcode' => qa_get_form_security_code('confirm'),
		),
	);

	if (!qa_email_validate($email)) {
		$qa_content['error'] = qa_lang_html('users/email_invalid');
		unset($qa_content['form']['buttons']['send']);
	}
} else { // User is not logged in
	$qa_content['error'] = qa_insert_login_links(qa_lang_html('users/confirm_wrong_log_in'), 'confirm');
}

return $qa_content;
