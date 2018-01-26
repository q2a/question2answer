<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: User management (application level) for basic user operations


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

define('QA_USER_LEVEL_BASIC', 0);
define('QA_USER_LEVEL_APPROVED', 10);
define('QA_USER_LEVEL_EXPERT', 20);
define('QA_USER_LEVEL_EDITOR', 50);
define('QA_USER_LEVEL_MODERATOR', 80);
define('QA_USER_LEVEL_ADMIN', 100);
define('QA_USER_LEVEL_SUPER', 120);

define('QA_USER_FLAGS_EMAIL_CONFIRMED', 1);
define('QA_USER_FLAGS_USER_BLOCKED', 2);
define('QA_USER_FLAGS_SHOW_AVATAR', 4);
define('QA_USER_FLAGS_SHOW_GRAVATAR', 8);
define('QA_USER_FLAGS_NO_MESSAGES', 16);
define('QA_USER_FLAGS_NO_MAILINGS', 32);
define('QA_USER_FLAGS_WELCOME_NOTICE', 64);
define('QA_USER_FLAGS_MUST_CONFIRM', 128);
define('QA_USER_FLAGS_NO_WALL_POSTS', 256);
define('QA_USER_FLAGS_MUST_APPROVE', 512); // @deprecated

define('QA_FIELD_FLAGS_MULTI_LINE', 1);
define('QA_FIELD_FLAGS_LINK_URL', 2);
define('QA_FIELD_FLAGS_ON_REGISTER', 4);

if (!defined('QA_FORM_EXPIRY_SECS')) {
	// how many seconds a form is valid for submission
	define('QA_FORM_EXPIRY_SECS', 86400);
}
if (!defined('QA_FORM_KEY_LENGTH')) {
	define('QA_FORM_KEY_LENGTH', 32);
}


if (QA_FINAL_EXTERNAL_USERS) {
	// If we're using single sign-on integration (WordPress or otherwise), load PHP file for that

	if (defined('QA_FINAL_WORDPRESS_INTEGRATE_PATH')) {
		require_once QA_INCLUDE_DIR . 'util/external-users-wp.php';
	} elseif (defined('QA_FINAL_JOOMLA_INTEGRATE_PATH')) {
		require_once QA_INCLUDE_DIR . 'util/external-users-joomla.php';
	} else {
		require_once QA_EXTERNAL_DIR . 'qa-external-users.php';
	}

	// Access functions for user information

	/**
	 * Return array of information about the currently logged in user, cache to ensure only one call to external code
	 */
	function qa_get_logged_in_user_cache()
	{
		global $qa_cached_logged_in_user;

		if (!isset($qa_cached_logged_in_user)) {
			$user = qa_get_logged_in_user();

			if (isset($user)) {
				$user['flags'] = isset($user['blocked']) ? QA_USER_FLAGS_USER_BLOCKED : 0;
				$qa_cached_logged_in_user = $user;
			} else
				$qa_cached_logged_in_user = false;
		}

		return @$qa_cached_logged_in_user;
	}


	/**
	 * Return $field of the currently logged in user, or null if not available
	 * @param $field
	 * @return null
	 */
	function qa_get_logged_in_user_field($field)
	{
		$user = qa_get_logged_in_user_cache();

		return isset($user[$field]) ? $user[$field] : null;
	}


	/**
	 * Return the userid of the currently logged in user, or null if none
	 */
	function qa_get_logged_in_userid()
	{
		return qa_get_logged_in_user_field('userid');
	}


	/**
	 * Return the number of points of the currently logged in user, or null if none is logged in
	 */
	function qa_get_logged_in_points()
	{
		global $qa_cached_logged_in_points;

		if (!isset($qa_cached_logged_in_points)) {
			require_once QA_INCLUDE_DIR . 'db/selects.php';

			$qa_cached_logged_in_points = qa_db_select_with_pending(qa_db_user_points_selectspec(qa_get_logged_in_userid(), true));
		}

		return $qa_cached_logged_in_points['points'];
	}


	/**
	 * Return HTML to display for the avatar of $userid, constrained to $size pixels, with optional $padding to that size
	 * @param $userid
	 * @param $size
	 * @param bool $padding
	 * @return mixed|null|string
	 */
	function qa_get_external_avatar_html($userid, $size, $padding = false)
	{
		if (function_exists('qa_avatar_html_from_userid'))
			return qa_avatar_html_from_userid($userid, $size, $padding);
		else
			return null;
	}


} else {

	/**
	 * Open a PHP session if one isn't opened already
	 */
	function qa_start_session()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		@ini_set('session.gc_maxlifetime', 86400); // worth a try, but won't help in shared hosting environment
		@ini_set('session.use_trans_sid', false); // sessions need cookies to work, since we redirect after login
		@ini_set('session.cookie_domain', QA_COOKIE_DOMAIN);

		if (!isset($_SESSION))
			session_start();
	}


	/**
	 * Returns a suffix to be used for names of session variables to prevent them being shared between multiple Q2A sites on the same server
	 */
	function qa_session_var_suffix()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		global $qa_session_suffix;

		if (!$qa_session_suffix) {
			$prefix = defined('QA_MYSQL_USERS_PREFIX') ? QA_MYSQL_USERS_PREFIX : QA_MYSQL_TABLE_PREFIX;
			$qa_session_suffix = md5(QA_FINAL_MYSQL_HOSTNAME . '/' . QA_FINAL_MYSQL_USERNAME . '/' . QA_FINAL_MYSQL_PASSWORD . '/' . QA_FINAL_MYSQL_DATABASE . '/' . $prefix);
		}

		return $qa_session_suffix;
	}


	/**
	 * Returns a verification code used to ensure that a user session can't be generated by another PHP script running on the same server
	 * @param $userid
	 * @return mixed|string
	 */
	function qa_session_verify_code($userid)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		return sha1($userid . '/' . QA_MYSQL_TABLE_PREFIX . '/' . QA_FINAL_MYSQL_DATABASE . '/' . QA_FINAL_MYSQL_PASSWORD . '/' . QA_FINAL_MYSQL_USERNAME . '/' . QA_FINAL_MYSQL_HOSTNAME);
	}


	/**
	 * Set cookie in browser for username $handle with $sessioncode (in database).
	 * Pass true if user checked 'Remember me' (either now or previously, as learned from cookie).
	 * @param $handle
	 * @param $sessioncode
	 * @param $remember
	 * @return mixed
	 */
	function qa_set_session_cookie($handle, $sessioncode, $remember)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		// if $remember is true, store in browser for a month, otherwise store only until browser is closed
		setcookie('qa_session', $handle . '/' . $sessioncode . '/' . ($remember ? 1 : 0), $remember ? (time() + 2592000) : 0, '/', QA_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true);
	}


	/**
	 * Remove session cookie from browser
	 */
	function qa_clear_session_cookie()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		setcookie('qa_session', false, 0, '/', QA_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true);
	}


	/**
	 * Set the session variables to indicate that $userid is logged in from $source
	 * @param $userid
	 * @param $source
	 * @return mixed
	 */
	function qa_set_session_user($userid, $source)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$suffix = qa_session_var_suffix();

		$_SESSION['qa_session_userid_' . $suffix] = $userid;
		$_SESSION['qa_session_source_' . $suffix] = $source;
		// prevents one account on a shared server being able to create a log in a user to Q2A on another account on same server
		$_SESSION['qa_session_verify_' . $suffix] = qa_session_verify_code($userid);
	}


	/**
	 * Clear the session variables indicating that a user is logged in
	 */
	function qa_clear_session_user()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$suffix = qa_session_var_suffix();

		unset($_SESSION['qa_session_userid_' . $suffix]);
		unset($_SESSION['qa_session_source_' . $suffix]);
		unset($_SESSION['qa_session_verify_' . $suffix]);
	}


	/**
	 * Call for successful log in by $userid and $handle or successful log out with $userid=null.
	 * $remember states if 'Remember me' was checked in the login form.
	 * @param $userid
	 * @param string $handle
	 * @param bool $remember
	 * @param $source
	 * @return mixed
	 */
	function qa_set_logged_in_user($userid, $handle = '', $remember = false, $source = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'app/cookies.php';

		qa_start_session();

		if (isset($userid)) {
			qa_set_session_user($userid, $source);

			// PHP sessions time out too quickly on the server side, so we also set a cookie as backup.
			// Logging in from a second browser will make the previous browser's 'Remember me' no longer
			// work - I'm not sure if this is the right behavior - could see it either way.

			require_once QA_INCLUDE_DIR . 'db/selects.php';

			$userinfo = qa_db_single_select(qa_db_user_account_selectspec($userid, true));

			// if we have logged in before, and are logging in the same way as before, we don't need to change the sessioncode/source
			// this means it will be possible to automatically log in (via cookies) to the same account from more than one browser

			if (empty($userinfo['sessioncode']) || ($source !== $userinfo['sessionsource'])) {
				$sessioncode = qa_db_user_rand_sessioncode();
				qa_db_user_set($userid, 'sessioncode', $sessioncode);
				qa_db_user_set($userid, 'sessionsource', $source);
			} else
				$sessioncode = $userinfo['sessioncode'];

			qa_db_user_logged_in($userid, qa_remote_ip_address());
			qa_set_session_cookie($handle, $sessioncode, $remember);

			qa_report_event('u_login', $userid, $userinfo['handle'], qa_cookie_get());

		} else {
			$olduserid = qa_get_logged_in_userid();
			$oldhandle = qa_get_logged_in_handle();

			qa_clear_session_cookie();
			qa_clear_session_user();

			qa_report_event('u_logout', $olduserid, $oldhandle, qa_cookie_get());
		}
	}


	/**
	 * Call to log in a user based on an external identity provider $source with external $identifier
	 * A new user is created based on $fields if it's a new combination of $source and $identifier
	 * @param $source
	 * @param $identifier
	 * @param $fields
	 * @return mixed
	 */
	function qa_log_in_external_user($source, $identifier, $fields)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'db/users.php';

		$users = qa_db_user_login_find($source, $identifier);
		$countusers = count($users);

		if ($countusers > 1)
			qa_fatal_error('External login mapped to more than one user'); // should never happen

		if ($countusers) // user exists so log them in
			qa_set_logged_in_user($users[0]['userid'], $users[0]['handle'], false, $source);

		else { // create and log in user
			require_once QA_INCLUDE_DIR . 'app/users-edit.php';

			qa_db_user_login_sync(true);

			$users = qa_db_user_login_find($source, $identifier); // check again after table is locked

			if (count($users) == 1) {
				qa_db_user_login_sync(false);
				qa_set_logged_in_user($users[0]['userid'], $users[0]['handle'], false, $source);

			} else {
				$handle = qa_handle_make_valid(@$fields['handle']);

				if (strlen(@$fields['email'])) { // remove email address if it will cause a duplicate
					$emailusers = qa_db_user_find_by_email($fields['email']);
					if (count($emailusers)) {
						qa_redirect('login', array('e' => $fields['email'], 'ee' => '1'));
						unset($fields['email']);
						unset($fields['confirmed']);
					}
				}

				$userid = qa_create_new_user((string)@$fields['email'], null /* no password */, $handle,
					isset($fields['level']) ? $fields['level'] : QA_USER_LEVEL_BASIC, @$fields['confirmed']);

				qa_db_user_login_add($userid, $source, $identifier);
				qa_db_user_login_sync(false);

				$profilefields = array('name', 'location', 'website', 'about');

				foreach ($profilefields as $fieldname) {
					if (strlen(@$fields[$fieldname]))
						qa_db_user_profile_set($userid, $fieldname, $fields[$fieldname]);
				}

				if (strlen(@$fields['avatar']))
					qa_set_user_avatar($userid, $fields['avatar']);

				qa_set_logged_in_user($userid, $handle, false, $source);
			}
		}
	}


	/**
	 * Return the userid of the currently logged in user, or null if none logged in
	 */
	function qa_get_logged_in_userid()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		global $qa_logged_in_userid_checked;

		$suffix = qa_session_var_suffix();

		if (!$qa_logged_in_userid_checked) { // only check once
			qa_start_session(); // this will load logged in userid from the native PHP session, but that's not enough

			$sessionuserid = @$_SESSION['qa_session_userid_' . $suffix];

			if (isset($sessionuserid)) // check verify code matches
				if (!hash_equals(qa_session_verify_code($sessionuserid), @$_SESSION['qa_session_verify_' . $suffix]))
					qa_clear_session_user();

			if (!empty($_COOKIE['qa_session'])) {
				@list($handle, $sessioncode, $remember) = explode('/', $_COOKIE['qa_session']);

				if ($remember)
					qa_set_session_cookie($handle, $sessioncode, $remember); // extend 'remember me' cookies each time

				$sessioncode = trim($sessioncode); // trim to prevent passing in blank values to match uninitiated DB rows

				// Try to recover session from the database if PHP session has timed out
				if (!isset($_SESSION['qa_session_userid_' . $suffix]) && !empty($handle) && !empty($sessioncode)) {
					require_once QA_INCLUDE_DIR . 'db/selects.php';

					$userinfo = qa_db_single_select(qa_db_user_account_selectspec($handle, false)); // don't get any pending

					if (strtolower(trim($userinfo['sessioncode'])) == strtolower($sessioncode))
						qa_set_session_user($userinfo['userid'], $userinfo['sessionsource']);
					else
						qa_clear_session_cookie(); // if cookie not valid, remove it to save future checks
				}
			}

			$qa_logged_in_userid_checked = true;
		}

		return @$_SESSION['qa_session_userid_' . $suffix];
	}


	/**
	 * Get the source of the currently logged in user, from call to qa_log_in_external_user() or null if logged in normally
	 */
	function qa_get_logged_in_source()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$userid = qa_get_logged_in_userid();
		$suffix = qa_session_var_suffix();

		if (isset($userid))
			return @$_SESSION['qa_session_source_' . $suffix];
	}


	/**
	 * Return array of information about the currently logged in user, cache to ensure only one call to external code
	 */
	function qa_get_logged_in_user_cache()
	{
		global $qa_cached_logged_in_user;

		if (!isset($qa_cached_logged_in_user)) {
			$userid = qa_get_logged_in_userid();

			if (isset($userid)) {
				require_once QA_INCLUDE_DIR . 'db/selects.php';
				$qa_cached_logged_in_user = qa_db_get_pending_result('loggedinuser', qa_db_user_account_selectspec($userid, true));

				if (!isset($qa_cached_logged_in_user)) {
					// the user can no longer be found (should only apply to deleted users)
					qa_clear_session_user();
					qa_redirect(''); // implicit exit;
				}
			}
		}

		return $qa_cached_logged_in_user;
	}


	/**
	 * Return $field of the currently logged in user
	 * @param $field
	 * @return mixed|null
	 */
	function qa_get_logged_in_user_field($field)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$usercache = qa_get_logged_in_user_cache();

		return isset($usercache[$field]) ? $usercache[$field] : null;
	}


	/**
	 * Return the number of points of the currently logged in user, or null if none is logged in
	 */
	function qa_get_logged_in_points()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		return qa_get_logged_in_user_field('points');
	}


	/**
	 * Return column type to use for users (if not using single sign-on integration)
	 */
	function qa_get_mysql_user_column_type()
	{
		return 'INT UNSIGNED';
	}


	/**
	 * Return the URL to the $blobId with a stored size of $width and $height.
	 * Constrain the image to $size (width AND height)
	 *
	 * @param string $blobId The blob ID from the image
	 * @param int|null $size The resulting image's size. If omitted the original image size will be used. If the
	 * size is present it must be greater than 0
	 * @param bool $absolute Whether the link returned should be absolute or relative
	 * @return string|null The URL to the avatar or null if the $blobId was empty or the $size not valid
	 */
	function qa_get_avatar_blob_url($blobId, $size = null, $absolute = false)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'util/image.php';

		if (strlen($blobId) == 0 || (isset($size) && (int)$size <= 0)) {
			return null;
		}

		$params = array('qa_blobid' => $blobId);
		if (isset($size)) {
			$params['qa_size'] = $size;
		}

		$rootUrl = $absolute ? qa_opt('site_url') : null;

		return qa_path('image', $params, $rootUrl, QA_URL_FORMAT_PARAMS);
	}


	/**
	 * Get HTML to display a username, linked to their user page.
	 *
	 * @param string $handle  The username.
	 * @param bool $microdata  Whether to include microdata.
	 * @param bool $favorited  Show the user as favorited.
	 * @return string  The user HTML.
	 */
	function qa_get_one_user_html($handle, $microdata = false, $favorited = false)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		if (strlen($handle) === 0) {
			return qa_lang('main/anonymous');
		}

		$url = qa_path_html('user/' . $handle);
		$favclass = $favorited ? ' qa-user-favorited' : '';
		$mfAttr = $microdata ? ' itemprop="url"' : '';

		$userHandle = $microdata ? '<span itemprop="name">' . qa_html($handle) . '</span>' : qa_html($handle);
		$userHtml = '<a href="' . $url . '" class="qa-user-link' . $favclass . '"' . $mfAttr . '>' . $userHandle . '</a>';

		if ($microdata) {
			$userHtml = '<span itemprop="author" itemscope itemtype="http://schema.org/Person">' . $userHtml . '</span>';
		}

		return $userHtml;
	}


	/**
	 * Return where the avatar will be fetched from for the given user flags. The possible return values are
	 * 'gravatar' for an avatar that will be fetched from Gravatar, 'local-user' for an avatar fetched locally from
	 * the user's profile, 'local-default' for an avatar fetched locally from the default avatar blob ID, and NULL
	 * if the avatar could not be fetched from any of these sources
	 *
	 * @param int $flags The user's flags
	 * @param string|null $email The user's email
	 * @param string|null $blobId The blob ID for a locally stored avatar.
	 * @return string|null The source of the avatar: 'gravatar', 'local-user', 'local-default' and null
	 */
	function qa_get_user_avatar_source($flags, $email, $blobId)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		if (qa_opt('avatar_allow_gravatar') && (($flags & QA_USER_FLAGS_SHOW_GRAVATAR) > 0) && isset($email)) {
			return 'gravatar';
		} elseif (qa_opt('avatar_allow_upload') && (($flags & QA_USER_FLAGS_SHOW_AVATAR) > 0) && isset($blobId)) {
			return 'local-user';
		} elseif ((qa_opt('avatar_allow_gravatar') || qa_opt('avatar_allow_upload')) && qa_opt('avatar_default_show') && strlen(qa_opt('avatar_default_blobid') > 0)) {
			return 'local-default';
		} else {
			return null;
		}
	}


	/**
	 * Return the avatar URL, either Gravatar or from a blob ID, constrained to $size pixels.
	 *
	 * @param int $flags The user's flags
	 * @param string $email The user's email. Only needed to return the Gravatar link
	 * @param string $blobId The blob ID. Only needed to return the locally stored avatar
	 * @param int $size The size to constrain the final image
	 * @param bool $absolute Whether the link returned should be absolute or relative
	 * @return null|string The URL to the user's avatar or null if none could be found (not even as a default site avatar)
	 */
	function qa_get_user_avatar_url($flags, $email, $blobId, $size = null, $absolute = false)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$avatarSource = qa_get_user_avatar_source($flags, $email, $blobId);

		switch ($avatarSource) {
			case 'gravatar':
				return qa_get_gravatar_url($email, $size);
			case 'local-user':
				return qa_get_avatar_blob_url($blobId, $size, $absolute);
			case 'local-default':
				return qa_get_avatar_blob_url(qa_opt('avatar_default_blobid'), $size, $absolute);
			default: // NULL
				return null;
		}
	}


	/**
	 * Return HTML to display for the user's avatar, constrained to $size pixels, with optional $padding to that size
	 *
	 * @param int $flags The user's flags
	 * @param string $email The user's email. Only needed to return the Gravatar HTML
	 * @param string $blobId The blob ID. Only needed to return the locally stored avatar HTML
	 * @param string $handle The handle of the user that the avatar will link to
	 * @param int $width The width to constrain the image
	 * @param int $height The height to constrain the image
	 * @param int $size The size to constrain the final image
	 * @param bool $padding HTML padding to add to the image
	 * @return string|null The HTML to the user's avatar or null if no valid source for the avatar could be found
	 */
	function qa_get_user_avatar_html($flags, $email, $handle, $blobId, $width, $height, $size, $padding = false)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'app/format.php';
		if (strlen($handle) == 0) {
			return null;
		}

		$avatarSource = qa_get_user_avatar_source($flags, $email, $blobId);

		switch ($avatarSource) {
			case 'gravatar':
				$html = qa_get_gravatar_html($email, $size);
				break;
			case 'local-user':
				$html = qa_get_avatar_blob_html($blobId, $width, $height, $size, $padding);
				break;
			case 'local-default':
				$html = qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), $size, $padding);
				break;
			default: // NULL
				return null;
		}

		return sprintf('<a href="%s" class="qa-avatar-link">%s</a>', qa_path_html('user/' . $handle), $html);
	}


	/**
	 * Return email address for user $userid (if not using single sign-on integration)
	 * @param $userid
	 * @return string
	 */
	function qa_get_user_email($userid)
	{
		$userinfo = qa_db_select_with_pending(qa_db_user_account_selectspec($userid, true));

		return $userinfo['email'];
	}


	/**
	 * Called after a database write $action performed by a user $userid
	 * @param $userid
	 * @param $action
	 * @return mixed
	 */
	function qa_user_report_action($userid, $action)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'db/users.php';

		qa_db_user_written($userid, qa_remote_ip_address());
	}


	/**
	 * Return textual representation of the user $level
	 * @param $level
	 * @return mixed|string
	 */
	function qa_user_level_string($level)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		if ($level >= QA_USER_LEVEL_SUPER)
			$string = 'users/level_super';
		elseif ($level >= QA_USER_LEVEL_ADMIN)
			$string = 'users/level_admin';
		elseif ($level >= QA_USER_LEVEL_MODERATOR)
			$string = 'users/level_moderator';
		elseif ($level >= QA_USER_LEVEL_EDITOR)
			$string = 'users/level_editor';
		elseif ($level >= QA_USER_LEVEL_EXPERT)
			$string = 'users/level_expert';
		elseif ($level >= QA_USER_LEVEL_APPROVED)
			$string = 'users/approved_user';
		else
			$string = 'users/registered_user';

		return qa_lang($string);
	}


	/**
	 * Return an array of links to login, register, email confirm and logout pages (if not using single sign-on integration)
	 * @param $rooturl
	 * @param $tourl
	 * @return array
	 */
	function qa_get_login_links($rooturl, $tourl)
	{
		return array(
			'login' => qa_path('login', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
			'register' => qa_path('register', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
			'confirm' => qa_path('confirm', null, $rooturl),
			'logout' => qa_path('logout', null, $rooturl),
		);
	}

} // end of: if (QA_FINAL_EXTERNAL_USERS) { ... } else { ... }


/**
 * Return whether someone is logged in at the moment
 */
function qa_is_logged_in()
{
	$userid = qa_get_logged_in_userid();
	return isset($userid);
}


/**
 * Return displayable handle/username of currently logged in user, or null if none
 */
function qa_get_logged_in_handle()
{
	return qa_get_logged_in_user_field(QA_FINAL_EXTERNAL_USERS ? 'publicusername' : 'handle');
}


/**
 * Return email of currently logged in user, or null if none
 */
function qa_get_logged_in_email()
{
	return qa_get_logged_in_user_field('email');
}


/**
 * Return level of currently logged in user, or null if none
 */
function qa_get_logged_in_level()
{
	return qa_get_logged_in_user_field('level');
}


/**
 * Return flags (see QA_USER_FLAGS_*) of currently logged in user, or null if none
 */
function qa_get_logged_in_flags()
{
	if (QA_FINAL_EXTERNAL_USERS)
		return qa_get_logged_in_user_field('blocked') ? QA_USER_FLAGS_USER_BLOCKED : 0;
	else
		return qa_get_logged_in_user_field('flags');
}


/**
 * Return an array of all the specific (e.g. per category) level privileges for the logged in user, retrieving from the database if necessary
 */
function qa_get_logged_in_levels()
{
	require_once QA_INCLUDE_DIR . 'db/selects.php';

	return qa_db_get_pending_result('userlevels', qa_db_user_levels_selectspec(qa_get_logged_in_userid(), true));
}


/**
 * Return an array mapping each userid in $userids to that user's handle (public username), or to null if not found
 * @param $userids
 * @return array
 */
function qa_userids_to_handles($userids)
{
	if (QA_FINAL_EXTERNAL_USERS)
		$rawuseridhandles = qa_get_public_from_userids($userids);

	else {
		require_once QA_INCLUDE_DIR . 'db/users.php';
		$rawuseridhandles = qa_db_user_get_userid_handles($userids);
	}

	$gotuseridhandles = array();
	foreach ($userids as $userid)
		$gotuseridhandles[$userid] = @$rawuseridhandles[$userid];

	return $gotuseridhandles;
}


/**
 * Return an string mapping the received userid to that user's handle (public username), or to null if not found
 * @param $userid
 * @return mixed|null
 */
function qa_userid_to_handle($userid)
{
	$handles = qa_userids_to_handles(array($userid));
	return empty($handles) ? null : $handles[$userid];
}


/**
 * Return an array mapping each handle in $handles the user's userid, or null if not found. If $exactonly is true then
 * $handles must have the correct case and accents. Otherwise, handles are case- and accent-insensitive, and the keys
 * of the returned array will match the $handles provided, not necessary those in the DB.
 * @param $handles
 * @param bool $exactonly
 * @return array
 */
function qa_handles_to_userids($handles, $exactonly = false)
{
	require_once QA_INCLUDE_DIR . 'util/string.php';

	if (QA_FINAL_EXTERNAL_USERS)
		$rawhandleuserids = qa_get_userids_from_public($handles);

	else {
		require_once QA_INCLUDE_DIR . 'db/users.php';
		$rawhandleuserids = qa_db_user_get_handle_userids($handles);
	}

	$gothandleuserids = array();

	if ($exactonly) { // only take the exact matches
		foreach ($handles as $handle)
			$gothandleuserids[$handle] = @$rawhandleuserids[$handle];

	} else { // normalize to lowercase without accents, and then find matches
		$normhandleuserids = array();
		foreach ($rawhandleuserids as $handle => $userid)
			$normhandleuserids[qa_string_remove_accents(qa_strtolower($handle))] = $userid;

		foreach ($handles as $handle)
			$gothandleuserids[$handle] = @$normhandleuserids[qa_string_remove_accents(qa_strtolower($handle))];
	}

	return $gothandleuserids;
}


/**
 * Return the userid corresponding to $handle (not case- or accent-sensitive)
 * @param $handle
 * @return mixed|null
 */
function qa_handle_to_userid($handle)
{
	if (QA_FINAL_EXTERNAL_USERS)
		$handleuserids = qa_get_userids_from_public(array($handle));

	else {
		require_once QA_INCLUDE_DIR . 'db/users.php';
		$handleuserids = qa_db_user_get_handle_userids(array($handle));
	}

	if (count($handleuserids) == 1)
		return reset($handleuserids); // don't use $handleuserids[$handle] since capitalization might be different

	return null;
}


/**
 * Return the level of the logged in user for a post with $categoryids (expressing the full hierarchy to the final category)
 * @param $categoryids
 * @return mixed|null
 */
function qa_user_level_for_categories($categoryids)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'app/updates.php';

	$level = qa_get_logged_in_level();

	if (count($categoryids)) {
		$userlevels = qa_get_logged_in_levels();

		$categorylevels = array(); // create a map
		foreach ($userlevels as $userlevel) {
			if ($userlevel['entitytype'] == QA_ENTITY_CATEGORY)
				$categorylevels[$userlevel['entityid']] = $userlevel['level'];
		}

		foreach ($categoryids as $categoryid) {
			$level = max($level, @$categorylevels[$categoryid]);
		}
	}

	return $level;
}


/**
 * Return the level of the logged in user for $post, as retrieved from the database
 * @param $post
 * @return mixed|null
 */
function qa_user_level_for_post($post)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	if (strlen(@$post['categoryids']))
		return qa_user_level_for_categories(explode(',', $post['categoryids']));

	return null;
}


/**
 * Return the maximum possible level of the logged in user in any context (i.e. for any category)
 */
function qa_user_level_maximum()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$level = qa_get_logged_in_level();

	$userlevels = qa_get_logged_in_levels();
	foreach ($userlevels as $userlevel) {
		$level = max($level, $userlevel['level']);
	}

	return $level;
}


/**
 * Check whether the logged in user has permission to perform $permitoption on post $post (from the database)
 * Other parameters and the return value are as for qa_user_permit_error(...)
 * @param $permitoption
 * @param $post
 * @param $limitaction
 * @param bool $checkblocks
 * @return bool|string
 */
function qa_user_post_permit_error($permitoption, $post, $limitaction = null, $checkblocks = true)
{
	return qa_user_permit_error($permitoption, $limitaction, qa_user_level_for_post($post), $checkblocks);
}


/**
 * Check whether the logged in user would have permittion to perform $permitoption in any context (i.e. for any category)
 * Other parameters and the return value are as for qa_user_permit_error(...)
 * @param $permitoption
 * @param $limitaction
 * @param bool $checkblocks
 * @return bool|string
 */
function qa_user_maximum_permit_error($permitoption, $limitaction = null, $checkblocks = true)
{
	return qa_user_permit_error($permitoption, $limitaction, qa_user_level_maximum(), $checkblocks);
}


/**
 * Check whether the logged in user has permission to perform an action.
 *
 * @param string $permitoption The permission to check (if null, this simply checks whether the user is blocked).
 * @param string $limitaction Constant from /qa-include/app/limits.php to check against user or IP rate limits.
 * @param int $userlevel A QA_USER_LEVEL_* constant to consider the user at a different level to usual (e.g. if
 *   they are performing this action in a category for which they have elevated privileges).
 * @param bool $checkblocks Whether to check the user's blocked status.
 * @param array $userfields Cache for logged in user, containing keys 'userid', 'level' (optional), 'flags'.
 *
 * @return bool|string The permission error, or false if no error. Possible errors, in order of priority:
 *   'login' => the user should login or register
 *   'level' => a special privilege level (e.g. expert) or minimum number of points is required
 *   'userblock' => the user has been blocked
 *   'ipblock' => the ip address has been blocked
 *   'confirm' => the user should confirm their email address
 *   'approve' => the user needs to be approved by the site admins (no longer used as global permission)
 *   'limit' => the user or IP address has reached a rate limit (if $limitaction specified)
 *   false => the operation can go ahead
 */
function qa_user_permit_error($permitoption = null, $limitaction = null, $userlevel = null, $checkblocks = true, $userfields = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'app/limits.php';

	if (!isset($userfields))
		$userfields = qa_get_logged_in_user_cache();

	$userid = isset($userfields['userid']) ? $userfields['userid'] : null;

	if (!isset($userlevel))
		$userlevel = isset($userfields['level']) ? $userfields['level'] : null;

	$flags = isset($userfields['flags']) ? $userfields['flags'] : null;
	if (!$checkblocks)
		$flags &= ~QA_USER_FLAGS_USER_BLOCKED;

	$error = qa_permit_error($permitoption, $userid, $userlevel, $flags);

	if ($checkblocks && !$error && qa_is_ip_blocked())
		$error = 'ipblock';

	if (!$error && isset($userid) && ($flags & QA_USER_FLAGS_MUST_CONFIRM) && qa_opt('confirm_user_emails'))
		$error = 'confirm';

	if (isset($limitaction) && !$error) {
		if (qa_user_limits_remaining($limitaction) <= 0)
			$error = 'limit';
	}

	return $error;
}


/**
 * Check whether user can perform $permitoption. Result as for qa_user_permit_error(...).
 *
 * @param string $permitoption Permission option name (from database) for action.
 * @param int $userid ID of user (null for no user).
 * @param int $userlevel Level to check against.
 * @param int $userflags Flags for this user.
 * @param int $userpoints User's points: if $userid is currently logged in, you can set $userpoints=null to retrieve them only if necessary.
 *
 * @return string|bool Reason the user is not permitted, or false if the operation can go ahead.
 */
function qa_permit_error($permitoption, $userid, $userlevel, $userflags, $userpoints = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$permit = isset($permitoption) ? qa_opt($permitoption) : QA_PERMIT_ALL;

	if (isset($userid) && ($permit == QA_PERMIT_POINTS || $permit == QA_PERMIT_POINTS_CONFIRMED || $permit == QA_PERMIT_APPROVED_POINTS)) {
		// deal with points threshold by converting as appropriate

		if (!isset($userpoints) && $userid == qa_get_logged_in_userid())
			$userpoints = qa_get_logged_in_points(); // allow late retrieval of points (to avoid unnecessary DB query when using external users)

		if ($userpoints >= qa_opt($permitoption . '_points')) {
			$permit = $permit == QA_PERMIT_APPROVED_POINTS
				? QA_PERMIT_APPROVED
				: ($permit == QA_PERMIT_POINTS_CONFIRMED ? QA_PERMIT_CONFIRMED : QA_PERMIT_USERS); // convert if user has enough points
		} else
			$permit = QA_PERMIT_EXPERTS; // otherwise show a generic message so they're not tempted to collect points just for this
	}

	return qa_permit_value_error($permit, $userid, $userlevel, $userflags);
}


/**
 * Check whether user can reach the permission level. Result as for qa_user_permit_error(...).
 *
 * @param int $permit Permission constant.
 * @param int $userid ID of user (null for no user).
 * @param int $userlevel Level to check against.
 * @param int $userflags Flags for this user.
 *
 * @return string|bool Reason the user is not permitted, or false if the operation can go ahead
 */
function qa_permit_value_error($permit, $userid, $userlevel, $userflags)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	if (!isset($userid) && $permit < QA_PERMIT_ALL)
		return 'login';

	$levelError =
		($permit <= QA_PERMIT_SUPERS && $userlevel < QA_USER_LEVEL_SUPER) ||
		($permit <= QA_PERMIT_ADMINS && $userlevel < QA_USER_LEVEL_ADMIN) ||
		($permit <= QA_PERMIT_MODERATORS && $userlevel < QA_USER_LEVEL_MODERATOR) ||
		($permit <= QA_PERMIT_EDITORS && $userlevel < QA_USER_LEVEL_EDITOR) ||
		($permit <= QA_PERMIT_EXPERTS && $userlevel < QA_USER_LEVEL_EXPERT);

	if ($levelError)
		return 'level';

	if (isset($userid) && ($userflags & QA_USER_FLAGS_USER_BLOCKED))
		return 'userblock';

	if ($permit >= QA_PERMIT_USERS)
		return false;

	if ($permit >= QA_PERMIT_CONFIRMED) {
		$confirmed = ($userflags & QA_USER_FLAGS_EMAIL_CONFIRMED);
		// not currently supported by single sign-on integration; approved users and above don't need confirmation
		if (!QA_FINAL_EXTERNAL_USERS && qa_opt('confirm_user_emails') && $userlevel < QA_USER_LEVEL_APPROVED && !$confirmed) {
			return 'confirm';
		}
	} elseif ($permit >= QA_PERMIT_APPROVED) {
		// check user is approved, only if we require it
		if (qa_opt('moderate_users') && $userlevel < QA_USER_LEVEL_APPROVED) {
			return 'approve';
		}
	}

	return false;
}


/**
 * Return whether a captcha is required for posts submitted by the current user. You can pass in a QA_USER_LEVEL_*
 * constant in $userlevel to consider the user at a different level to usual (e.g. if they are performing this action
 * in a category for which they have elevated privileges).
 *
 * Possible results:
 * 'login' => captcha required because the user is not logged in
 * 'approve' => captcha required because the user has not been approved
 * 'confirm' => captcha required because the user has not confirmed their email address
 * false => captcha is not required
 * @param $userlevel
 * @return bool|mixed|string
 */
function qa_user_captcha_reason($userlevel = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$reason = false;
	if (!isset($userlevel))
		$userlevel = qa_get_logged_in_level();

	if ($userlevel < QA_USER_LEVEL_APPROVED) { // approved users and above aren't shown captchas
		$userid = qa_get_logged_in_userid();

		if (qa_opt('captcha_on_anon_post') && !isset($userid))
			$reason = 'login';
		elseif (qa_opt('moderate_users') && qa_opt('captcha_on_unapproved'))
			$reason = 'approve';
		elseif (qa_opt('confirm_user_emails') && qa_opt('captcha_on_unconfirmed') && !(qa_get_logged_in_flags() & QA_USER_FLAGS_EMAIL_CONFIRMED))
			$reason = 'confirm';
	}

	return $reason;
}


/**
 * Return whether a captcha should be presented to the logged in user for writing posts. You can pass in a
 * QA_USER_LEVEL_* constant in $userlevel to consider the user at a different level to usual.
 * @param $userlevel
 * @return bool|mixed
 */
function qa_user_use_captcha($userlevel = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return qa_user_captcha_reason($userlevel) != false;
}


/**
 * Return whether moderation is required for posts submitted by the current user. You can pass in a QA_USER_LEVEL_*
 * constant in $userlevel to consider the user at a different level to usual (e.g. if they are performing this action
 * in a category for which they have elevated privileges).
 *
 * Possible results:
 * 'login' => moderation required because the user is not logged in
 * 'approve' => moderation required because the user has not been approved
 * 'confirm' => moderation required because the user has not confirmed their email address
 * 'points' => moderation required because the user has insufficient points
 * false => moderation is not required
 * @param $userlevel
 * @return bool|string
 */
function qa_user_moderation_reason($userlevel = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$reason = false;
	if (!isset($userlevel))
		$userlevel = qa_get_logged_in_level();

	if ($userlevel < QA_USER_LEVEL_EXPERT && qa_user_permit_error('permit_moderate')) {
		// experts and above aren't moderated; if the user can approve posts, no point in moderating theirs
		$userid = qa_get_logged_in_userid();

		if (isset($userid)) {
			if (qa_opt('moderate_users') && qa_opt('moderate_unapproved') && ($userlevel < QA_USER_LEVEL_APPROVED))
				$reason = 'approve';
			elseif (qa_opt('confirm_user_emails') && qa_opt('moderate_unconfirmed') && !(qa_get_logged_in_flags() & QA_USER_FLAGS_EMAIL_CONFIRMED))
				$reason = 'confirm';
			elseif (qa_opt('moderate_by_points') && (qa_get_logged_in_points() < qa_opt('moderate_points_limit')))
				$reason = 'points';

		} elseif (qa_opt('moderate_anon_post'))
			$reason = 'login';
	}

	return $reason;
}


/**
 * Return the label to display for $userfield as retrieved from the database, using default if no name set
 * @param $userfield
 * @return string
 */
function qa_user_userfield_label($userfield)
{
	if (isset($userfield['content']))
		return $userfield['content'];

	else {
		$defaultlabels = array(
			'name' => 'users/full_name',
			'about' => 'users/about',
			'location' => 'users/location',
			'website' => 'users/website',
		);

		if (isset($defaultlabels[$userfield['title']]))
			return qa_lang($defaultlabels[$userfield['title']]);
	}

	return '';
}


/**
 * Set or extend the cookie in browser of non logged-in users which identifies them for the purposes of form security (anti-CSRF protection)
 */
function qa_set_form_security_key()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	global $qa_form_key_cookie_set;

	if (!qa_is_logged_in() && !@$qa_form_key_cookie_set) {
		$qa_form_key_cookie_set = true;

		if (strlen(@$_COOKIE['qa_key']) != QA_FORM_KEY_LENGTH) {
			require_once QA_INCLUDE_DIR . 'util/string.php';
			$_COOKIE['qa_key'] = qa_random_alphanum(QA_FORM_KEY_LENGTH);
		}

		setcookie('qa_key', $_COOKIE['qa_key'], time() + 2 * QA_FORM_EXPIRY_SECS, '/', QA_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true); // extend on every page request
	}
}


/**
 * Return the form security (anti-CSRF protection) hash for an $action (any string), that can be performed within
 * QA_FORM_EXPIRY_SECS of $timestamp (in unix seconds) by the current user.
 * @param $action
 * @param $timestamp
 * @return mixed|string
 */
function qa_calc_form_security_hash($action, $timestamp)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$salt = qa_opt('form_security_salt');

	if (qa_is_logged_in())
		return sha1($salt . '/' . $action . '/' . $timestamp . '/' . qa_get_logged_in_userid() . '/' . qa_get_logged_in_user_field('passsalt'));
	else
		return sha1($salt . '/' . $action . '/' . $timestamp . '/' . @$_COOKIE['qa_key']); // lower security for non logged in users - code+cookie can be transferred
}


/**
 * Return the full form security (anti-CSRF protection) code for an $action (any string) performed within
 * QA_FORM_EXPIRY_SECS of now by the current user.
 * @param $action
 * @return mixed|string
 */
function qa_get_form_security_code($action)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	qa_set_form_security_key();

	$timestamp = qa_opt('db_time');

	return (int)qa_is_logged_in() . '-' . $timestamp . '-' . qa_calc_form_security_hash($action, $timestamp);
}


/**
 * Return whether $value matches the expected form security (anti-CSRF protection) code for $action (any string) and
 * that the code has not expired (if more than QA_FORM_EXPIRY_SECS have passed). Logs causes for suspicion.
 * @param $action
 * @param $value
 * @return bool
 */
function qa_check_form_security_code($action, $value)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$reportproblems = array();
	$silentproblems = array();

	if (!isset($value)) {
		$silentproblems[] = 'code missing';

	} elseif (!strlen($value)) {
		$silentproblems[] = 'code empty';

	} else {
		$parts = explode('-', $value);

		if (count($parts) == 3) {
			$loggedin = $parts[0];
			$timestamp = $parts[1];
			$hash = $parts[2];
			$timenow = qa_opt('db_time');

			if ($timestamp > $timenow) {
				$reportproblems[] = 'time ' . ($timestamp - $timenow) . 's in future';
			} elseif ($timestamp < ($timenow - QA_FORM_EXPIRY_SECS)) {
				$silentproblems[] = 'timeout after ' . ($timenow - $timestamp) . 's';
			}

			if (qa_is_logged_in()) {
				if (!$loggedin) {
					$silentproblems[] = 'now logged in';
				}
			} else {
				if ($loggedin) {
					$silentproblems[] = 'now logged out';
				} else {
					$key = @$_COOKIE['qa_key'];

					if (!isset($key)) {
						$silentproblems[] = 'key cookie missing';
					} elseif (!strlen($key)) {
						$silentproblems[] = 'key cookie empty';
					} elseif (strlen($key) != QA_FORM_KEY_LENGTH) {
						$reportproblems[] = 'key cookie ' . $key . ' invalid';
					}
				}
			}

			if (empty($silentproblems) && empty($reportproblems)) {
				if (!hash_equals(strtolower(qa_calc_form_security_hash($action, $timestamp)), strtolower($hash))) {
					$reportproblems[] = 'code mismatch';
				}
			}

		} else {
			$reportproblems[] = 'code ' . $value . ' malformed';
		}
	}

	if (!empty($reportproblems) && QA_DEBUG_PERFORMANCE) {
		@error_log(
			'PHP Question2Answer form security violation for ' . $action .
			' by ' . (qa_is_logged_in() ? ('userid ' . qa_get_logged_in_userid()) : 'anonymous') .
			' (' . implode(', ', array_merge($reportproblems, $silentproblems)) . ')' .
			' on ' . @$_SERVER['REQUEST_URI'] .
			' via ' . @$_SERVER['HTTP_REFERER']
		);
	}

	return (empty($silentproblems) && empty($reportproblems));
}


/**
 * Return the URL for the Gravatar corresponding to $email, constrained to $size
 *
 * @param string $email The email of the Gravatar to return
 * @param int|null $size The size of the Gravatar to return. If omitted the default size will be used
 * @return string The URL to the Gravatar of the user
 */
function qa_get_gravatar_url($email, $size = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$link = 'https://www.gravatar.com/avatar/%s';

	$params = array(md5(strtolower(trim($email))));

	$size = (int)$size;
	if ($size > 0) {
		$link .= '?s=%d';
		$params[] = $size;
	}

	return vsprintf($link, $params);
}
