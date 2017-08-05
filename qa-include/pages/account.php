<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for user account page


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
require_once QA_INCLUDE_DIR . 'app/format.php';
require_once QA_INCLUDE_DIR . 'app/users.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'util/image.php';


// Check we're not using single-sign on integration, that we're logged in

if (QA_FINAL_EXTERNAL_USERS)
	qa_fatal_error('User accounts are handled by external code');

$userid = qa_get_logged_in_userid();

if (!isset($userid))
	qa_redirect('login');


// Get current information on user

list($useraccount, $userprofile, $userpoints, $userfields) = qa_db_select_with_pending(
	qa_db_user_account_selectspec($userid, true),
	qa_db_user_profile_selectspec($userid, true),
	qa_db_user_points_selectspec($userid, true),
	qa_db_userfields_selectspec()
);

$changehandle = qa_opt('allow_change_usernames') || (!$userpoints['qposts'] && !$userpoints['aposts'] && !$userpoints['cposts']);
$doconfirms = qa_opt('confirm_user_emails') && $useraccount['level'] < QA_USER_LEVEL_EXPERT;
$isconfirmed = ($useraccount['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED) ? true : false;

$haspasswordold = isset($useraccount['passsalt']) && isset($useraccount['passcheck']);
if (QA_PASSWORD_HASH) {
	$haspassword = isset($useraccount['passhash']);
} else {
	$haspassword = $haspasswordold;
}
$permit_error = qa_user_permit_error();
$isblocked = $permit_error !== false;
$pending_confirmation = $doconfirms && $permit_error == 'confirm';

// Process profile if saved

// If the post_max_size is exceeded then the $_POST array is empty so no field processing can be done
if (qa_post_limit_exceeded())
	$errors['avatar'] = qa_lang('main/file_upload_limit_exceeded');
else {
	require_once QA_INCLUDE_DIR . 'app/users-edit.php';

	if (qa_clicked('dosaveprofile') && !$isblocked) {
		$inhandle = $changehandle ? qa_post_text('handle') : $useraccount['handle'];
		$inemail = qa_post_text('email');
		$inmessages = qa_post_text('messages');
		$inwallposts = qa_post_text('wall');
		$inmailings = qa_post_text('mailings');
		$inavatar = qa_post_text('avatar');

		$inprofile = array();
		foreach ($userfields as $userfield)
			$inprofile[$userfield['fieldid']] = qa_post_text('field_' . $userfield['fieldid']);

		if (!qa_check_form_security_code('account', qa_post_text('code')))
			$errors['page'] = qa_lang_html('misc/form_security_again');
		else {
			$errors = qa_handle_email_filter($inhandle, $inemail, $useraccount);

			if (!isset($errors['handle']))
				qa_db_user_set($userid, 'handle', $inhandle);

			if (!isset($errors['email']) && $inemail !== $useraccount['email']) {
				qa_db_user_set($userid, 'email', $inemail);
				qa_db_user_set_flag($userid, QA_USER_FLAGS_EMAIL_CONFIRMED, false);
				$isconfirmed = false;

				if ($doconfirms)
					qa_send_new_confirm($userid);
			}

			if (qa_opt('allow_private_messages'))
				qa_db_user_set_flag($userid, QA_USER_FLAGS_NO_MESSAGES, !$inmessages);

			if (qa_opt('allow_user_walls'))
				qa_db_user_set_flag($userid, QA_USER_FLAGS_NO_WALL_POSTS, !$inwallposts);

			if (qa_opt('mailing_enabled'))
				qa_db_user_set_flag($userid, QA_USER_FLAGS_NO_MAILINGS, !$inmailings);

			qa_db_user_set_flag($userid, QA_USER_FLAGS_SHOW_AVATAR, ($inavatar == 'uploaded'));
			qa_db_user_set_flag($userid, QA_USER_FLAGS_SHOW_GRAVATAR, ($inavatar == 'gravatar'));

			if (is_array(@$_FILES['file'])) {
				$avatarfileerror = $_FILES['file']['error'];

				// Note if $_FILES['file']['error'] === 1 then upload_max_filesize has been exceeded
				if ($avatarfileerror === 1)
					$errors['avatar'] = qa_lang('main/file_upload_limit_exceeded');
				elseif ($avatarfileerror === 0 && $_FILES['file']['size'] > 0) {
					require_once QA_INCLUDE_DIR . 'app/limits.php';

					switch (qa_user_permit_error(null, QA_LIMIT_UPLOADS)) {
						case 'limit':
							$errors['avatar'] = qa_lang('main/upload_limit');
							break;

						default:
							$errors['avatar'] = qa_lang('users/no_permission');
							break;

						case false:
							qa_limits_increment($userid, QA_LIMIT_UPLOADS);
							$toobig = qa_image_file_too_big($_FILES['file']['tmp_name'], qa_opt('avatar_store_size'));

							if ($toobig)
								$errors['avatar'] = qa_lang_sub('main/image_too_big_x_pc', (int)($toobig * 100));
							elseif (!qa_set_user_avatar($userid, file_get_contents($_FILES['file']['tmp_name']), $useraccount['avatarblobid']))
								$errors['avatar'] = qa_lang_sub('main/image_not_read', implode(', ', qa_gd_image_formats()));
							break;
					}
				}  // There shouldn't be any need to catch any other error
			}

			if (count($inprofile)) {
				$filtermodules = qa_load_modules_with('filter', 'filter_profile');
				foreach ($filtermodules as $filtermodule)
					$filtermodule->filter_profile($inprofile, $errors, $useraccount, $userprofile);
			}

			foreach ($userfields as $userfield) {
				if (!isset($errors[$userfield['fieldid']]))
					qa_db_user_profile_set($userid, $userfield['title'], $inprofile[$userfield['fieldid']]);
			}

			list($useraccount, $userprofile) = qa_db_select_with_pending(
				qa_db_user_account_selectspec($userid, true), qa_db_user_profile_selectspec($userid, true)
			);

			qa_report_event('u_save', $userid, $useraccount['handle'], qa_cookie_get());

			if (empty($errors))
				qa_redirect('account', array('state' => 'profile-saved'));

			qa_logged_in_user_flush();
		}
	} elseif (qa_clicked('dosaveprofile') && $pending_confirmation) {
		// only allow user to update email if they are not confirmed yet
		$inemail = qa_post_text('email');

		if (!qa_check_form_security_code('account', qa_post_text('code')))
			$errors['page'] = qa_lang_html('misc/form_security_again');

		else {
			$errors = qa_handle_email_filter($useraccount['handle'], $inemail, $useraccount);

			if (!isset($errors['email']) && $inemail !== $useraccount['email']) {
				qa_db_user_set($userid, 'email', $inemail);
				qa_db_user_set_flag($userid, QA_USER_FLAGS_EMAIL_CONFIRMED, false);
				$isconfirmed = false;

				if ($doconfirms)
					qa_send_new_confirm($userid);
			}

			qa_report_event('u_save', $userid, $useraccount['handle'], qa_cookie_get());

			if (empty($errors))
				qa_redirect('account', array('state' => 'profile-saved'));

			qa_logged_in_user_flush();
		}
	}


	// Process change password if clicked

	if (qa_clicked('dochangepassword')) {
		$inoldpassword = qa_post_text('oldpassword');
		$innewpassword1 = qa_post_text('newpassword1');
		$innewpassword2 = qa_post_text('newpassword2');

		if (!qa_check_form_security_code('password', qa_post_text('code')))
			$errors['page'] = qa_lang_html('misc/form_security_again');
		else {
			$errors = array();
			$legacyPassError = !hash_equals(strtolower($useraccount['passcheck']), strtolower(qa_db_calc_passcheck($inoldpassword, $useraccount['passsalt'])));

			if (QA_PASSWORD_HASH) {
				$passError = !password_verify($inoldpassword, $useraccount['passhash']);
				if (($haspasswordold && $legacyPassError) || (!$haspasswordold && $haspassword && $passError)) {
					$errors['oldpassword'] = qa_lang('users/password_wrong');
				}
			} else {
				if ($haspassword && $legacyPassError) {
					$errors['oldpassword'] = qa_lang('users/password_wrong');
				}
			}

			$useraccount['password'] = $inoldpassword;
			$errors = $errors + qa_password_validate($innewpassword1, $useraccount); // array union

			if ($innewpassword1 != $innewpassword2)
				$errors['newpassword2'] = qa_lang('users/password_mismatch');

			if (empty($errors)) {
				qa_db_user_set_password($userid, $innewpassword1);
				qa_db_user_set($userid, 'sessioncode', ''); // stop old 'Remember me' style logins from still working
				qa_set_logged_in_user($userid, $useraccount['handle'], false, $useraccount['sessionsource']); // reinstate this specific session

				qa_report_event('u_password', $userid, $useraccount['handle'], qa_cookie_get());

				qa_redirect('account', array('state' => 'password-changed'));
			}
		}
	}
}

// Prepare content for theme

$qa_content = qa_content_prepare();

$qa_content['title'] = qa_lang_html('profile/my_account_title');
$qa_content['error'] = @$errors['page'];

$qa_content['form_profile'] = array(
	'tags' => 'enctype="multipart/form-data" method="post" action="' . qa_self_html() . '"',

	'style' => 'wide',

	'fields' => array(
		'duration' => array(
			'type' => 'static',
			'label' => qa_lang_html('users/member_for'),
			'value' => qa_time_to_string(qa_opt('db_time') - $useraccount['created']),
		),

		'type' => array(
			'type' => 'static',
			'label' => qa_lang_html('users/member_type'),
			'value' => qa_html(qa_user_level_string($useraccount['level'])),
			'note' => $isblocked ? qa_lang_html('users/user_blocked') : null,
		),

		'handle' => array(
			'label' => qa_lang_html('users/handle_label'),
			'tags' => 'name="handle"',
			'value' => qa_html(isset($inhandle) ? $inhandle : $useraccount['handle']),
			'error' => qa_html(@$errors['handle']),
			'type' => ($changehandle && !$isblocked) ? 'text' : 'static',
		),

		'email' => array(
			'label' => qa_lang_html('users/email_label'),
			'tags' => 'name="email"',
			'value' => qa_html(isset($inemail) ? $inemail : $useraccount['email']),
			'error' => isset($errors['email']) ? qa_html($errors['email']) :
				($pending_confirmation ? qa_insert_login_links(qa_lang_html('users/email_please_confirm')) : null),
			'type' => $pending_confirmation ? 'text' : ($isblocked ? 'static' : 'text'),
		),

		'messages' => array(
			'label' => qa_lang_html('users/private_messages'),
			'tags' => 'name="messages"' . ($pending_confirmation ? ' disabled' : ''),
			'type' => 'checkbox',
			'value' => !($useraccount['flags'] & QA_USER_FLAGS_NO_MESSAGES),
			'note' => qa_lang_html('users/private_messages_explanation'),
		),

		'wall' => array(
			'label' => qa_lang_html('users/wall_posts'),
			'tags' => 'name="wall"' . ($pending_confirmation ? ' disabled' : ''),
			'type' => 'checkbox',
			'value' => !($useraccount['flags'] & QA_USER_FLAGS_NO_WALL_POSTS),
			'note' => qa_lang_html('users/wall_posts_explanation'),
		),

		'mailings' => array(
			'label' => qa_lang_html('users/mass_mailings'),
			'tags' => 'name="mailings"',
			'type' => 'checkbox',
			'value' => !($useraccount['flags'] & QA_USER_FLAGS_NO_MAILINGS),
			'note' => qa_lang_html('users/mass_mailings_explanation'),
		),

		'avatar' => null, // for positioning
	),

	'buttons' => array(
		'save' => array(
			'tags' => 'onclick="qa_show_waiting_after(this, false);"',
			'label' => qa_lang_html('users/save_profile'),
		),
	),

	'hidden' => array(
		'dosaveprofile' => array(
			'tags' => 'name="dosaveprofile"',
			'value' => '1',
		),
		'code' => array(
			'tags' => 'name="code"',
			'value' => qa_get_form_security_code('account'),
		),
	),
);

if (qa_get_state() == 'profile-saved')
	$qa_content['form_profile']['ok'] = qa_lang_html('users/profile_saved');

if (!qa_opt('allow_private_messages'))
	unset($qa_content['form_profile']['fields']['messages']);

if (!qa_opt('allow_user_walls'))
	unset($qa_content['form_profile']['fields']['wall']);

if (!qa_opt('mailing_enabled'))
	unset($qa_content['form_profile']['fields']['mailings']);

if ($isblocked && !$pending_confirmation) {
	unset($qa_content['form_profile']['buttons']['save']);
	$qa_content['error'] = qa_lang_html('users/no_permission');
}

// Avatar upload stuff

if (qa_opt('avatar_allow_gravatar') || qa_opt('avatar_allow_upload')) {
	$avataroptions = array();

	if (qa_opt('avatar_default_show') && strlen(qa_opt('avatar_default_blobid'))) {
		$avataroptions[''] = '<span style="margin:2px 0; display:inline-block;">' .
			qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), 32) .
			'</span> ' . qa_lang_html('users/avatar_default');
	} else
		$avataroptions[''] = qa_lang_html('users/avatar_none');

	$avatarvalue = $avataroptions[''];

	if (qa_opt('avatar_allow_gravatar') && !$pending_confirmation) {
		$avataroptions['gravatar'] = '<span style="margin:2px 0; display:inline-block;">' .
			qa_get_gravatar_html($useraccount['email'], 32) . ' ' . strtr(qa_lang_html('users/avatar_gravatar'), array(
				'^1' => '<a href="http://www.gravatar.com/" target="_blank">',
				'^2' => '</a>',
			)) . '</span>';

		if ($useraccount['flags'] & QA_USER_FLAGS_SHOW_GRAVATAR)
			$avatarvalue = $avataroptions['gravatar'];
	}

	if (qa_has_gd_image() && qa_opt('avatar_allow_upload') && !$pending_confirmation) {
		$avataroptions['uploaded'] = '<input name="file" type="file">';

		if (isset($useraccount['avatarblobid']))
			$avataroptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' .
				qa_get_avatar_blob_html($useraccount['avatarblobid'], $useraccount['avatarwidth'], $useraccount['avatarheight'], 32) .
				'</span>' . $avataroptions['uploaded'];

		if ($useraccount['flags'] & QA_USER_FLAGS_SHOW_AVATAR)
			$avatarvalue = $avataroptions['uploaded'];
	}

	$qa_content['form_profile']['fields']['avatar'] = array(
		'type' => 'select-radio',
		'label' => qa_lang_html('users/avatar_label'),
		'tags' => 'name="avatar"',
		'options' => $avataroptions,
		'value' => $avatarvalue,
		'error' => qa_html(@$errors['avatar']),
	);

} else {
	unset($qa_content['form_profile']['fields']['avatar']);
}


// Other profile fields

foreach ($userfields as $userfield) {
	$value = @$inprofile[$userfield['fieldid']];
	if (!isset($value))
		$value = @$userprofile[$userfield['title']];

	$label = trim(qa_user_userfield_label($userfield), ':');
	if (strlen($label))
		$label .= ':';

	$qa_content['form_profile']['fields'][$userfield['title']] = array(
		'label' => qa_html($label),
		'tags' => 'name="field_' . $userfield['fieldid'] . '"',
		'value' => qa_html($value),
		'error' => qa_html(@$errors[$userfield['fieldid']]),
		'rows' => ($userfield['flags'] & QA_FIELD_FLAGS_MULTI_LINE) ? 8 : null,
		'type' => $isblocked ? 'static' : 'text',
	);
}


// Raw information for plugin layers to access

$qa_content['raw']['account'] = $useraccount;
$qa_content['raw']['profile'] = $userprofile;
$qa_content['raw']['points'] = $userpoints;


// Change password form

$qa_content['form_password'] = array(
	'tags' => 'method="post" action="' . qa_self_html() . '"',

	'style' => 'wide',

	'title' => qa_lang_html('users/change_password'),

	'fields' => array(
		'old' => array(
			'label' => qa_lang_html('users/old_password'),
			'tags' => 'name="oldpassword"',
			'value' => qa_html(@$inoldpassword),
			'type' => 'password',
			'error' => qa_html(@$errors['oldpassword']),
		),

		'new_1' => array(
			'label' => qa_lang_html('users/new_password_1'),
			'tags' => 'name="newpassword1"',
			'type' => 'password',
			'error' => qa_html(@$errors['password']),
		),

		'new_2' => array(
			'label' => qa_lang_html('users/new_password_2'),
			'tags' => 'name="newpassword2"',
			'type' => 'password',
			'error' => qa_html(@$errors['newpassword2']),
		),
	),

	'buttons' => array(
		'change' => array(
			'label' => qa_lang_html('users/change_password'),
		),
	),

	'hidden' => array(
		'dochangepassword' => array(
			'tags' => 'name="dochangepassword"',
			'value' => '1',
		),
		'code' => array(
			'tags' => 'name="code"',
			'value' => qa_get_form_security_code('password'),
		),
	),
);

if (!$haspassword && !$haspasswordold) {
	$qa_content['form_password']['fields']['old']['type'] = 'static';
	$qa_content['form_password']['fields']['old']['value'] = qa_lang_html('users/password_none');
}

if (qa_get_state() == 'password-changed')
	$qa_content['form_profile']['ok'] = qa_lang_html('users/password_changed');


$qa_content['navigation']['sub'] = qa_user_sub_navigation($useraccount['handle'], 'account', true);


return $qa_content;
