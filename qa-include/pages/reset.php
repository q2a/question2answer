<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for password reset page (comes after forgot page)


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

// Check we're not using single-sign on integration and that we're not logged in

if (QA_FINAL_EXTERNAL_USERS) {
	qa_fatal_error('User login is handled by external code');
}

if (qa_is_logged_in()) {
	qa_redirect('');
}

// Fetch the email or handle from POST or GET
$emailHandle = qa_post_text('emailhandle');
if (!isset($emailHandle)) {
	$emailHandle = qa_get('e');
}
$emailHandle = trim($emailHandle); // if $emailHandle is null, trim returns an empty string

// Fetch the code from POST or GET
$code = qa_post_text('code');
if (!isset($code)) {
	$code = qa_get('c');
}
$code = trim($code); // if $code is null, trim returns an empty string

$forgotPath = strlen($emailHandle) > 0 ? qa_path('forgot', array('e' => $emailHandle)) : qa_path('forgot');

$focusId = 'code';

$errors = array();
$fields = array(
	'email_handle' => array(
		'type' => 'static',
		'label' => qa_lang_html(qa_opt('allow_login_email_only') ? 'users/email_label' : 'users/email_handle_label'),
		'value' => qa_html($emailHandle),
	),
	'code' => array(
		'label' => qa_lang_html('users/email_code_label'),
		'tags' => 'name="code" id="code"',
		'value' => isset($code) ? qa_html($code) : null,
		'note_force' => true,
		'note' => qa_lang_html('users/email_code_emailed') . ' - ' .
			'<a href="' . qa_html($forgotPath) . '">' . qa_lang_html('users/email_code_another') . '</a>',
	),
);
$buttons = array(
	'next' => array(
		'tags' => 'name="donext"',
		'label' => qa_lang_html('misc/next_step'),
	),
);
$hidden = array(
	'formcode' => qa_get_form_security_code('reset'),
);

if (strlen($emailHandle) > 0) {
	require_once QA_INCLUDE_DIR . 'app/users-edit.php';
	require_once QA_INCLUDE_DIR . 'db/users.php';

	$hidden['emailhandle'] = $emailHandle;

	$matchingUsers = qa_opt('allow_login_email_only') || strpos($emailHandle, '@') !== false // handles can't contain @ symbols
		? qa_db_user_find_by_email($emailHandle)
		: qa_db_user_find_by_handle($emailHandle);

	// Make sure there is only one match
	if (count($matchingUsers) == 1) {
		require_once QA_INCLUDE_DIR . 'db/selects.php';

		// strlen() check is vital otherwise we can reset code for most users by entering the empty string
		if (strlen($code) > 0) {
			$userId = $matchingUsers[0];
			$userInfo = qa_db_select_with_pending(qa_db_user_account_selectspec($userId, true));

			if (strtolower(trim($userInfo['emailcode'])) == strtolower($code)) {
				// User input a valid code so no need to ask for it but pass it to the next step
				unset($fields['code']);
				$hidden['code'] = $code;

				$buttons = array(
					'change' => array(
						'tags' => 'name="dochangepassword"',
						'label' => qa_lang_html('users/change_password'),
					),
				);

				$focusId = 'newpassword1';

				if (qa_clicked('dochangepassword')) {
					$newPassword = qa_post_text('newpassword1');
					$repeatPassword = qa_post_text('newpassword2');

					if (!qa_check_form_security_code('reset', qa_post_text('formcode'))) {
						$errors['page'] = qa_lang_html('misc/form_security_again');
					} else {
						$passwordError = qa_password_validate($newPassword, $userInfo);
						if (!empty($passwordError)) {
							$errors['new_1'] = $passwordError['password'];
						}

						if ($newPassword != $repeatPassword) {
							$errors['new_2'] = qa_lang('users/password_mismatch');
						}

						if (empty($errors)) {
							// Update password, login user, fire events and redirect to home page
							qa_finish_reset_user($userId, $newPassword);
							qa_redirect('');
						}
					}
				}

				$fields['new_1'] = array(
					'label' => qa_lang_html('users/new_password_1'),
					'tags' => 'name="newpassword1" id="newpassword1"',
					'type' => 'password',
					'error' => qa_html(isset($errors['new_1']) ? $errors['new_1'] : null),
				);

				$fields['new_2'] = array(
					'label' => qa_lang_html('users/new_password_2'),
					'tags' => 'name="newpassword2"',
					'type' => 'password',
					'error' => qa_html(isset($errors['new_2']) ? $errors['new_2'] : null),
				);
			} else {
				// User input wrong code so show field with error
				$fields['code']['error'] = qa_lang('users/email_code_wrong');
			}
		} elseif (qa_clicked('donext')) {
			// If user submitted the form with an empty code
			$fields['code']['error'] = qa_lang('users/email_code_wrong');
		}
	} else {
		// If match more than one (should be impossible), consider it a non-match
		$errors['page'] = qa_lang_html('users/user_not_found');
	}
} else {
	// If there is no handle notify the user
	$errors['page'] = qa_lang_html('users/user_not_found');
}


// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = qa_lang_html('users/reset_title');
$qa_content['error'] = isset($errors['page']) ? $errors['page'] : null;

if (!isset($errors['page'])) {
	// Using this form action instead of qa_self_html() to get rid of the 's' (success) GET parameter from forgot.php
	$qa_content['form'] = array(
		'tags' => 'method="post" action="' . qa_path_html('reset') . '"',

		'style' => 'tall',

		'ok' => qa_get('s') ? qa_lang_html('users/email_code_emailed') : null,

		'fields' => $fields,

		'buttons' => $buttons,

		'hidden' => $hidden,
	);
}

$qa_content['focusid'] = $focusId;

return $qa_content;
