<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for login page


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


if (qa_is_logged_in()) {
	qa_redirect('');
}

// Check we're not using Q2A's single-sign on integration and that we're not logged in
if (QA_FINAL_EXTERNAL_USERS) {
	$request = qa_request();
	$topath = qa_get('to'); // lets user switch between login and register without losing destination page
	$userlinks = qa_get_login_links(qa_path_to_root(), isset($topath) ? $topath : qa_path($request, $_GET, ''));

	if (!empty($userlinks['login'])) {
		qa_redirect_raw($userlinks['login']);
	}
	qa_fatal_error('User login should be handled by external code');
}


// Process submitted form after checking we haven't reached rate limit

$passwordsent = qa_get('ps');
$emailexists = qa_get('ee');

$inemailhandle = qa_post_text('emailhandle');
$inpassword = qa_post_text('password');
$inremember = qa_post_text('remember');

if (qa_clicked('dologin') && (strlen($inemailhandle) || strlen($inpassword))) {
	require_once QA_INCLUDE_DIR . 'app/limits.php';

	if (qa_user_limits_remaining(QA_LIMIT_LOGINS)) {
		require_once QA_INCLUDE_DIR . 'db/users.php';
		require_once QA_INCLUDE_DIR . 'db/selects.php';

		if (!qa_check_form_security_code('login', qa_post_text('code'))) {
			$pageerror = qa_lang_html('misc/form_security_again');
		}
		else {
			qa_limits_increment(null, QA_LIMIT_LOGINS);

			$errors = array();

			if (qa_opt('allow_login_email_only') || strpos($inemailhandle, '@') !== false) { // handles can't contain @ symbols
				$matchusers = qa_db_user_find_by_email($inemailhandle);
			} else {
				$matchusers = qa_db_user_find_by_handle($inemailhandle);
			}

			if (count($matchusers) == 1) { // if matches more than one (should be impossible), don't log in
				$inuserid = $matchusers[0];
				$userinfo = qa_db_select_with_pending(qa_db_user_account_selectspec($inuserid, true));

				$legacyPassOk = hash_equals(strtolower($userinfo['passcheck']), strtolower(qa_db_calc_passcheck($inpassword, $userinfo['passsalt'])));

				if (QA_PASSWORD_HASH) {
					$haspassword = isset($userinfo['passhash']);
					$haspasswordold = isset($userinfo['passsalt']) && isset($userinfo['passcheck']);
					$passOk = password_verify($inpassword, $userinfo['passhash']);

					if (($haspasswordold && $legacyPassOk) || ($haspassword && $passOk)) {
						// upgrade password or rehash, when options like the cost parameter changed
						if ($haspasswordold || password_needs_rehash($userinfo['passhash'], PASSWORD_BCRYPT)) {
							qa_db_user_set_password($inuserid, $inpassword);
						}
					} else {
						$errors['password'] = qa_lang('users/password_wrong');
					}
				} else {
					if (!$legacyPassOk) {
						$errors['password'] = qa_lang('users/password_wrong');
					}
				}

				if (!isset($errors['password'])) {
					// login and redirect
					require_once QA_INCLUDE_DIR . 'app/users.php';
					qa_set_logged_in_user($inuserid, $userinfo['handle'], !empty($inremember));

					$topath = qa_get('to');

					if (isset($topath))
						qa_redirect_raw(qa_path_to_root() . $topath); // path already provided as URL fragment
					elseif ($passwordsent)
						qa_redirect('account');
					else
						qa_redirect('');
				}

			} else {
				$errors['emailhandle'] = qa_lang('users/user_not_found');
			}
		}

	} else {
		$pageerror = qa_lang('users/login_limit');
	}

} else {
	$inemailhandle = qa_get('e');
}


// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = qa_lang_html('users/login_title');

$qa_content['error'] = @$pageerror;

if (empty($inemailhandle) || isset($errors['emailhandle']))
	$forgotpath = qa_path('forgot');
else
	$forgotpath = qa_path('forgot', array('e' => $inemailhandle));

$forgothtml = '<a href="' . qa_html($forgotpath) . '">' . qa_lang_html('users/forgot_link') . '</a>';

$qa_content['form'] = array(
	'tags' => 'method="post" action="' . qa_self_html() . '"',

	'style' => 'tall',

	'ok' => $passwordsent ? qa_lang_html('users/password_sent') : ($emailexists ? qa_lang_html('users/email_exists') : null),

	'fields' => array(
		'email_handle' => array(
			'label' => qa_opt('allow_login_email_only') ? qa_lang_html('users/email_label') : qa_lang_html('users/email_handle_label'),
			'tags' => 'name="emailhandle" id="emailhandle" dir="auto"',
			'value' => qa_html(@$inemailhandle),
			'error' => qa_html(@$errors['emailhandle']),
		),

		'password' => array(
			'type' => 'password',
			'label' => qa_lang_html('users/password_label'),
			'tags' => 'name="password" id="password" dir="auto"',
			'value' => qa_html(@$inpassword),
			'error' => empty($errors['password']) ? '' : (qa_html(@$errors['password']) . ' - ' . $forgothtml),
			'note' => $passwordsent ? qa_lang_html('users/password_sent') : $forgothtml,
		),

		'remember' => array(
			'type' => 'checkbox',
			'label' => qa_lang_html('users/remember_label'),
			'tags' => 'name="remember"',
			'value' => !empty($inremember),
		),
	),

	'buttons' => array(
		'login' => array(
			'label' => qa_lang_html('users/login_button'),
		),
	),

	'hidden' => array(
		'dologin' => '1',
		'code' => qa_get_form_security_code('login'),
	),
);

$loginmodules = qa_load_modules_with('login', 'login_html');

foreach ($loginmodules as $module) {
	ob_start();
	$module->login_html(qa_opt('site_url') . qa_get('to'), 'login');
	$html = ob_get_clean();

	if (strlen($html))
		@$qa_content['custom'] .= '<br>' . $html . '<br>';
}

$qa_content['focusid'] = (isset($inemailhandle) && !isset($errors['emailhandle'])) ? 'password' : 'emailhandle';


return $qa_content;
