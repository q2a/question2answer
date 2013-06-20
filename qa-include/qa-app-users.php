<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-users.php
	Version: See define()s at top of qa-include/qa-base.php
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
		header('Location: ../');
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
	define('QA_USER_FLAGS_MUST_APPROVE', 512);
	
	define('QA_FIELD_FLAGS_MULTI_LINE', 1);
	define('QA_FIELD_FLAGS_LINK_URL', 2);
	define('QA_FIELD_FLAGS_ON_REGISTER', 4);
	
	@define('QA_FORM_EXPIRY_SECS', 86400); // how many seconds a form is valid for submission
	@define('QA_FORM_KEY_LENGTH', 32);

	
	if (QA_FINAL_EXTERNAL_USERS) {

	//	If we're using single sign-on integration (WordPress or otherwise), load PHP file for that

		if (defined('QA_FINAL_WORDPRESS_INTEGRATE_PATH'))
			require_once QA_INCLUDE_DIR.'qa-external-users-wp.php';
		else
			require_once QA_EXTERNAL_DIR.'qa-external-users.php';
		

	//	Access functions for user information
	
		function qa_get_logged_in_user_cache()
	/*
		Return array of information about the currently logged in user, cache to ensure only one call to external code
	*/
		{
			global $qa_cached_logged_in_user;
			
			if (!isset($qa_cached_logged_in_user)) {
				$user=qa_get_logged_in_user();
				$qa_cached_logged_in_user=isset($user) ? $user : false; // to save trying again
			}
			
			return @$qa_cached_logged_in_user;
		}
		
		
		function qa_get_logged_in_user_field($field)
	/*
		Return $field of the currently logged in user, or null if not available
	*/
		{
			$user=qa_get_logged_in_user_cache();
			
			return @$user[$field];
		}


		function qa_get_logged_in_userid()
	/*
		Return the userid of the currently logged in user, or null if none
	*/
		{
			return qa_get_logged_in_user_field('userid');
		}
		
		
		function qa_get_logged_in_points()
	/*
		Return the number of points of the currently logged in user, or null if none is logged in
	*/
		{
			global $qa_cached_logged_in_points;
			
			if (!isset($qa_cached_logged_in_points)) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php'; 
				
				$qa_cached_logged_in_points=qa_db_select_with_pending(qa_db_user_points_selectspec(qa_get_logged_in_userid(), true));
			}
			
			return $qa_cached_logged_in_points['points'];
		}
		
		
		function qa_get_external_avatar_html($userid, $size, $padding=false)
	/*
		Return HTML to display for the avatar of $userid, constrained to $size pixels, with optional $padding to that size
	*/
		{
			if (function_exists('qa_avatar_html_from_userid'))
				return qa_avatar_html_from_userid($userid, $size, $padding);
			else
				return null;
		}
		
		
	} else {
		
		function qa_start_session()
	/*
		Open a PHP session if one isn't opened already
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			@ini_set('session.gc_maxlifetime', 86400); // worth a try, but won't help in shared hosting environment
			@ini_set('session.use_trans_sid', false); // sessions need cookies to work, since we redirect after login
			@ini_set('session.cookie_domain', QA_COOKIE_DOMAIN);

			if (!isset($_SESSION))
				session_start();
		}
		
		
		function qa_session_var_suffix()
	/*
		Returns a suffix to be used for names of session variables to prevent them being shared between multiple Q2A sites on the same server
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
			
			$prefix=defined('QA_MYSQL_USERS_PREFIX') ? QA_MYSQL_USERS_PREFIX : QA_MYSQL_TABLE_PREFIX;
			
			return md5(QA_FINAL_MYSQL_HOSTNAME.'/'.QA_FINAL_MYSQL_USERNAME.'/'.QA_FINAL_MYSQL_PASSWORD.'/'.QA_FINAL_MYSQL_DATABASE.'/'.$prefix);
		}
		
		
		function qa_session_verify_code($userid)
	/*
		Returns a verification code used to ensure that a user session can't be generated by another PHP script running on the same server
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			return sha1($userid.'/'.QA_MYSQL_TABLE_PREFIX.'/'.QA_FINAL_MYSQL_DATABASE.'/'.QA_FINAL_MYSQL_PASSWORD.'/'.QA_FINAL_MYSQL_USERNAME.'/'.QA_FINAL_MYSQL_HOSTNAME);
		}

		
		function qa_set_session_cookie($handle, $sessioncode, $remember)
	/*
		Set cookie in browser for username $handle with $sessioncode (in database).
		Pass true if user checked 'Remember me' (either now or previously, as learned from cookie).
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			// if $remember is true, store in browser for a month, otherwise store only until browser is closed
			setcookie('qa_session', $handle.'/'.$sessioncode.'/'.($remember ? 1 : 0), $remember ? (time()+2592000) : 0, '/', QA_COOKIE_DOMAIN);
		}

		
		function qa_clear_session_cookie()
	/*
		Remove session cookie from browser
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			setcookie('qa_session', false, 0, '/', QA_COOKIE_DOMAIN);
		}
		
		
		function qa_set_session_user($userid, $source)
	/*
		Set the session variables to indicate that $userid is logged in from $source
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			$suffix=qa_session_var_suffix();

			$_SESSION['qa_session_userid_'.$suffix]=$userid;
			$_SESSION['qa_session_source_'.$suffix]=$source;
			$_SESSION['qa_session_verify_'.$suffix]=qa_session_verify_code($userid);
				// prevents one account on a shared server being able to create a log in a user to Q2A on another account on same server
		}
		
		
		function qa_clear_session_user()
	/*
		Clear the session variables indicating that a user is logged in
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			$suffix=qa_session_var_suffix();

			unset($_SESSION['qa_session_userid_'.$suffix]);
			unset($_SESSION['qa_session_source_'.$suffix]);
			unset($_SESSION['qa_session_verify_'.$suffix]);
		}

		
		function qa_set_logged_in_user($userid, $handle='', $remember=false, $source=null)
	/*
		Call for successful log in by $userid and $handle or successful log out with $userid=null.
		$remember states if 'Remember me' was checked in the login form.
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
			
			qa_start_session();

			if (isset($userid)) {
				qa_set_session_user($userid, $source);
				
				// PHP sessions time out too quickly on the server side, so we also set a cookie as backup.
				// Logging in from a second browser will make the previous browser's 'Remember me' no longer
				// work - I'm not sure if this is the right behavior - could see it either way.

				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				
				$userinfo=qa_db_single_select(qa_db_user_account_selectspec($userid, true));
				
				// if we have logged in before, and are logging in the same way as before, we don't need to change the sessioncode/source
				// this means it will be possible to automatically log in (via cookies) to the same account from more than one browser
				
				if (empty($userinfo['sessioncode']) || ($source!==$userinfo['sessionsource'])) {
					$sessioncode=qa_db_user_rand_sessioncode();
					qa_db_user_set($userid, 'sessioncode', $sessioncode);
					qa_db_user_set($userid, 'sessionsource', $source);
				} else
					$sessioncode=$userinfo['sessioncode'];
				
				qa_db_user_logged_in($userid, qa_remote_ip_address());
				qa_set_session_cookie($handle, $sessioncode, $remember);
				
				qa_report_event('u_login', $userid, $userinfo['handle'], qa_cookie_get());

			} else {
				$olduserid=qa_get_logged_in_userid();
				$oldhandle=qa_get_logged_in_handle();

				qa_clear_session_cookie();
				qa_clear_session_user();

				qa_report_event('u_logout', $olduserid, $oldhandle, qa_cookie_get());
			}
		}
		
		
		function qa_log_in_external_user($source, $identifier, $fields)
	/*
		Call to log in a user based on an external identity provider $source with external $identifier
		A new user is created based on $fields if it's a new combination of $source and $identifier
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			
			$users=qa_db_user_login_find($source, $identifier);
			$countusers=count($users);
			
			if ($countusers>1)
				qa_fatal_error('External login mapped to more than one user'); // should never happen
			
			if ($countusers) // user exists so log them in
				qa_set_logged_in_user($users[0]['userid'], $users[0]['handle'], false, $source);
			
			else { // create and log in user
				require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
				
				qa_db_user_login_sync(true);
				
				$users=qa_db_user_login_find($source, $identifier); // check again after table is locked
				
				if (count($users)==1) {
					qa_db_user_login_sync(false);
					qa_set_logged_in_user($users[0]['userid'], $users[0]['handle'], false, $source);
				
				} else {
					$handle=qa_handle_make_valid(@$fields['handle']);
				
					if (strlen(@$fields['email'])) { // remove email address if it will cause a duplicate
						$emailusers=qa_db_user_find_by_email($fields['email']);
						if (count($emailusers)) {
							qa_redirect('login', array('e' => $fields['email'], 'ee' => '1'));
							unset($fields['email']);
							unset($fields['confirmed']);
						}
					}
					
					$userid=qa_create_new_user((string)@$fields['email'], null /* no password */, $handle,
						isset($fields['level']) ? $fields['level'] : QA_USER_LEVEL_BASIC, @$fields['confirmed']);
					
					qa_db_user_login_add($userid, $source, $identifier);
					qa_db_user_login_sync(false);
					
					$profilefields=array('name', 'location', 'website', 'about');
					
					foreach ($profilefields as $fieldname)
						if (strlen(@$fields[$fieldname]))
							qa_db_user_profile_set($userid, $fieldname, $fields[$fieldname]);
							
					if (strlen(@$fields['avatar']))
						qa_set_user_avatar($userid, $fields['avatar']);
							
					qa_set_logged_in_user($userid, $handle, false, $source);
				}
			}
		}

		
		function qa_get_logged_in_userid()
	/*
		Return the userid of the currently logged in user, or null if none logged in
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			global $qa_logged_in_userid_checked;
			
			$suffix=qa_session_var_suffix();
			
			if (!$qa_logged_in_userid_checked) { // only check once
				qa_start_session(); // this will load logged in userid from the native PHP session, but that's not enough
				
				$sessionuserid=@$_SESSION['qa_session_userid_'.$suffix];
				
				if (isset($sessionuserid)) // check verify code matches
					if (@$_SESSION['qa_session_verify_'.$suffix] != qa_session_verify_code($sessionuserid))
						qa_clear_session_user();
				
				if (!empty($_COOKIE['qa_session'])) {
					@list($handle, $sessioncode, $remember)=explode('/', $_COOKIE['qa_session']);
					
					if ($remember)
						qa_set_session_cookie($handle, $sessioncode, $remember); // extend 'remember me' cookies each time
	
					$sessioncode=trim($sessioncode); // trim to prevent passing in blank values to match uninitiated DB rows
	
					// Try to recover session from the database if PHP session has timed out
					if ( (!isset($_SESSION['qa_session_userid_'.$suffix])) && (!empty($handle)) && (!empty($sessioncode)) ) {
						require_once QA_INCLUDE_DIR.'qa-db-selects.php';
						
						$userinfo=qa_db_single_select(qa_db_user_account_selectspec($handle, false)); // don't get any pending
						
						if (strtolower(trim($userinfo['sessioncode'])) == strtolower($sessioncode))
							qa_set_session_user($userinfo['userid'], $userinfo['sessionsource']);
						else
							qa_clear_session_cookie(); // if cookie not valid, remove it to save future checks
					}
				}
				
				$qa_logged_in_userid_checked=true;
			}
			
			return @$_SESSION['qa_session_userid_'.$suffix];
		}
		
		
		function qa_get_logged_in_source()
	/*
		Get the source of the currently logged in user, from call to qa_log_in_external_user() or null if logged in normally
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			$userid=qa_get_logged_in_userid();
			$suffix=qa_session_var_suffix();
			
			if (isset($userid))
				return @$_SESSION['qa_session_source_'.$suffix];
		}
		
		
		function qa_get_logged_in_user_field($field)
	/*
		Return $field of the currently logged in user, cache to ensure only one call to external code
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			global $qa_cached_logged_in_user;
			
			$userid=qa_get_logged_in_userid();
			
			if (isset($userid) && !isset($qa_cached_logged_in_user)) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				$qa_cached_logged_in_user=qa_db_get_pending_result('loggedinuser', qa_db_user_account_selectspec($userid, true));
				
				if (!isset($qa_cached_logged_in_user)) { // the user can no longer be found (should be because they're deleted)
					qa_clear_session_user();
					qa_fatal_error('The logged in user cannot be found');
						// it's too late here to proceed because the caller may already be branching based on whether someone is logged in
				}					
			}
			
			return @$qa_cached_logged_in_user[$field];
		}
		
		
		function qa_get_logged_in_points()
	/*
		Return the number of points of the currently logged in user, or null if none is logged in
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			return qa_get_logged_in_user_field('points');
		}

		
		function qa_get_mysql_user_column_type()
	/*
		Return column type to use for users (if not using single sign-on integration)
	*/
		{
			return 'INT UNSIGNED';
		}


		function qa_get_one_user_html($handle, $microformats=false, $favorited=false)
	/*
		Return HTML to display for user with username $handle, with microformats if $microformats is true. Set $favorited to true to show the user as favorited.
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			return strlen($handle) ? ('<a href="'.qa_path_html('user/'.$handle).'" class="qa-user-link'
				.($favorited ? ' qa-user-favorited' : '').($microformats ? ' url nickname' : '').'">'.qa_html($handle).'</a>') : '';
		}
		
		
		function qa_get_user_avatar_html($flags, $email, $handle, $blobid, $width, $height, $size, $padding=false)
	/*
		Return HTML to display for the user's avatar, constrained to $size pixels, with optional $padding to that size
		Pass the user's fields $flags, $email, $handle, and avatar $blobid, $width and $height
	*/	
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
			if (qa_opt('avatar_allow_gravatar') && ($flags & QA_USER_FLAGS_SHOW_GRAVATAR))
				$html=qa_get_gravatar_html($email, $size);
			elseif (qa_opt('avatar_allow_upload') && (($flags & QA_USER_FLAGS_SHOW_AVATAR)) && isset($blobid))
				$html=qa_get_avatar_blob_html($blobid, $width, $height, $size, $padding);
			elseif ( (qa_opt('avatar_allow_gravatar')||qa_opt('avatar_allow_upload')) && qa_opt('avatar_default_show') && strlen(qa_opt('avatar_default_blobid')) )
				$html=qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), $size, $padding);
			else
				$html=null;
				
			return (isset($html) && strlen($handle)) ? ('<a href="'.qa_path_html('user/'.$handle).'" class="qa-avatar-link">'.$html.'</a>') : $html;
		}
		

		function qa_get_user_email($userid)
	/*
		Return email address for user $userid (if not using single sign-on integration)
	*/
		{
			$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($userid, true));

			return $userinfo['email'];
		}
		

		function qa_user_report_action($userid, $action)
	/*
		Called after a database write $action performed by a user $userid
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			
			qa_db_user_written($userid, qa_remote_ip_address());
		}

		
		function qa_user_level_string($level)
	/*
		Return textual representation of the user $level
	*/
		{
			if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
			if ($level>=QA_USER_LEVEL_SUPER)
				$string='users/level_super';
			elseif ($level>=QA_USER_LEVEL_ADMIN)
				$string='users/level_admin';
			elseif ($level>=QA_USER_LEVEL_MODERATOR)
				$string='users/level_moderator';
			elseif ($level>=QA_USER_LEVEL_EDITOR)
				$string='users/level_editor';
			elseif ($level>=QA_USER_LEVEL_EXPERT)
				$string='users/level_expert';
			elseif ($level>=QA_USER_LEVEL_APPROVED)
				$string='users/approved_user';
			else
				$string='users/registered_user';
			
			return qa_lang($string);
		}

		
		function qa_get_login_links($rooturl, $tourl)
	/*
		Return an array of links to login, register, email confirm and logout pages (if not using single sign-on integration)
	*/
		{
			return array(
				'login' => qa_path('login', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
				'register' => qa_path('register', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
				'confirm' => qa_path('confirm', null, $rooturl),
				'logout' => qa_path('logout', null, $rooturl),
			);
		}

	} // end of: if (QA_FINAL_EXTERNAL_USERS) { ... } else { ... }


	function qa_is_logged_in()
/*
	Return whether someone is logged in at the moment
*/
	{	
		$userid=qa_get_logged_in_userid();
		return isset($userid);
	}
	
	
	function qa_get_logged_in_handle()
/*
	Return displayable handle/username of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field(QA_FINAL_EXTERNAL_USERS ? 'publicusername' : 'handle');
	}


	function qa_get_logged_in_email()
/*
	Return email of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field('email');
	}


	function qa_get_logged_in_level()
/*
	Return level of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field('level');
	}

	
	function qa_get_logged_in_flags()
/*
	Return flags (see QA_USER_FLAGS_*) of currently logged in user, or null if none
*/
	{
		if (QA_FINAL_EXTERNAL_USERS)
			return qa_get_logged_in_user_field('blocked') ? QA_USER_FLAGS_USER_BLOCKED : 0;
		else
			return qa_get_logged_in_user_field('flags');
	}

	
	function qa_get_logged_in_levels()
/*
	Return an array of all the specific (e.g. per category) level privileges for the logged in user, retrieving from the database if necessary
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		
		return qa_db_get_pending_result('userlevels', qa_db_user_levels_selectspec(qa_get_logged_in_userid(), true));
	}
	
	
	function qa_userids_to_handles($userids)
/*
	Return an array mapping each userid in $userids to that user's handle (public username), or to null if not found
*/
	{
		if (QA_FINAL_EXTERNAL_USERS)
			$rawuseridhandles=qa_get_public_from_userids($userids);
		
		else {
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			$rawuseridhandles=qa_db_user_get_userid_handles($userids);
		}
		
		$gotuseridhandles=array();
		foreach ($userids as $userid)
			$gotuseridhandles[$userid]=@$rawuseridhandles[$userid];
			
		return $gotuseridhandles;
	}
	
	
	function qa_handles_to_userids($handles, $exactonly=false)
/*
	Return an array mapping each handle in $handles the user's userid, or null if not found. If $exactonly is true then
	$handles must have the correct case and accents. Otherwise, handles are case- and accent-insensitive, and the keys
	of the returned array will match the $handles provided, not necessary those in the DB.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		if (QA_FINAL_EXTERNAL_USERS)
			$rawhandleuserids=qa_get_userids_from_public($handles);

		else {
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			$rawhandleuserids=qa_db_user_get_handle_userids($handles);
		}
		
		$gothandleuserids=array();

		if ($exactonly) { // only take the exact matches
			foreach ($handles as $handle)
				$gothandleuserids[$handle]=@$rawhandleuserids[$handle];
		
		} else { // normalize to lowercase without accents, and then find matches
			$normhandleuserids=array();
			foreach ($rawhandleuserids as $handle => $userid)
				$normhandleuserids[qa_string_remove_accents(qa_strtolower($handle))]=$userid;
			
			foreach ($handles as $handle)
				$gothandleuserids[$handle]=@$normhandleuserids[qa_string_remove_accents(qa_strtolower($handle))];
		}
		
		return $gothandleuserids;
	}
	
	
	function qa_handle_to_userid($handle)
/*
	Return the userid corresponding to $handle (not case- or accent-sensitive)
*/
	{
		if (QA_FINAL_EXTERNAL_USERS)
			$handleuserids=qa_get_userids_from_public(array($handle));

		else {
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			$handleuserids=qa_db_user_get_handle_userids(array($handle));
		}
		
		if (count($handleuserids)==1)
			return reset($handleuserids); // don't use $handleuserids[$handle] since capitalization might be different
		
		return null;
	}
	
	
	function qa_user_level_for_categories($categoryids)
/*
	Return the level of the logged in user for a post with $categoryids (expressing the full hierarchy to the final category)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR.'qa-app-updates.php';

		$level=qa_get_logged_in_level();
		
		if (count($categoryids)) {
			$userlevels=qa_get_logged_in_levels();
			
			$categorylevels=array(); // create a map
			foreach ($userlevels as $userlevel)
				if ($userlevel['entitytype']==QA_ENTITY_CATEGORY)
					$categorylevels[$userlevel['entityid']]=$userlevel['level'];
			
			foreach ($categoryids as $categoryid)
				$level=max($level, @$categorylevels[$categoryid]);
		}
		
		return $level;
	}
	
	
	function qa_user_level_for_post($post)
/*
	Return the level of the logged in user for $post, as retrieved from the database
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if (strlen(@$post['categoryids']))
			return qa_user_level_for_categories(explode(',', $post['categoryids']));

		return null;
	}
	
	
	function qa_user_level_maximum()
/*
	Return the maximum possible level of the logged in user in any context (i.e. for any category)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$level=qa_get_logged_in_level();

		$userlevels=qa_get_logged_in_levels();
		foreach ($userlevels as $userlevel)
			$level=max($level, $userlevel['level']);

		return $level;
	}
	

	function qa_user_post_permit_error($permitoption, $post, $limitaction=null, $checkblocks=true)
/*
	Check whether the logged in user has permission to perform $permitoption on post $post (from the database)
	Other parameters and the return value are as for qa_user_permit_error(...)
*/
	{
		return qa_user_permit_error($permitoption, $limitaction, qa_user_level_for_post($post), $checkblocks);
	}
	
	
	function qa_user_maximum_permit_error($permitoption, $limitaction=null, $checkblocks=true)
/*
	Check whether the logged in user would have permittion to perform $permitoption in any context (i.e. for any category)
	Other parameters and the return value are as for qa_user_permit_error(...)
*/
	{
		return qa_user_permit_error($permitoption, $limitaction, qa_user_level_maximum(), $checkblocks);
	}
	
	
	function qa_user_permit_error($permitoption=null, $limitaction=null, $userlevel=null, $checkblocks=true)
/*
	Check whether the logged in user has permission to perform $permitoption. If $permitoption is null, this simply
	checks whether the user is blocked. Optionally provide an $limitaction (see top of qa-app-limits.php) to also check
	against user or IP rate limits. You can pass in a QA_USER_LEVEL_* constant in $userlevel to consider the user at a
	different level to usual (e.g. if they are performing this action in a category for which they have elevated
	privileges). To ignore the user's blocked status, set $checkblocks to false.

	Possible results, in order of priority (i.e. if more than one reason, the first will be given):
	'level' => a special privilege level (e.g. expert) or minimum number of points is required
	'login' => the user should login or register
	'userblock' => the user has been blocked
	'ipblock' => the ip address has been blocked
	'confirm' => the user should confirm their email address
	'approve' => the user needs to be approved by the site admins
	'limit' => the user or IP address has reached a rate limit (if $limitaction specified)
	false => the operation can go ahead
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		$userid=qa_get_logged_in_userid();
		if (!isset($userlevel))
			$userlevel=qa_get_logged_in_level();

		$flags=qa_get_logged_in_flags();
		if (!$checkblocks)
			$flags&=~QA_USER_FLAGS_USER_BLOCKED;

		$error=qa_permit_error($permitoption, $userid, $userlevel, $flags);
		
		if ($checkblocks && (!$error) && qa_is_ip_blocked())
			$error='ipblock';
			
		if ((!$error) && isset($userid) && ($flags & QA_USER_FLAGS_MUST_CONFIRM) && qa_opt('confirm_user_emails'))
			$error='confirm';
			
		if ((!$error) && isset($userid) && ($flags & QA_USER_FLAGS_MUST_APPROVE) && qa_opt('moderate_users'))
			$error='approve';
		
		if (isset($limitaction) && !$error)
			if (qa_user_limits_remaining($limitaction)<=0)
				$error='limit';
		
		return $error;
	}
	
	
	function qa_permit_error($permitoption, $userid, $userlevel, $userflags, $userpoints=null)
/*
	Check whether $userid (null for no user) can perform $permitoption. Result as for qa_user_permit_error(...).
	If appropriate, pass the user's level in $userlevel, flags in $userflags and points in $userpoints.
	If $userid is currently logged in, you can set $userpoints=null to retrieve them only if necessary.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$permit=isset($permitoption) ? qa_opt($permitoption) : QA_PERMIT_ALL;

		if (isset($userid) && (($permit==QA_PERMIT_POINTS) || ($permit==QA_PERMIT_POINTS_CONFIRMED) || ($permit==QA_PERMIT_APPROVED_POINTS)) ) {
				// deal with points threshold by converting as appropriate
			
			if ( (!isset($userpoints)) && ($userid==qa_get_logged_in_userid()) )
				$userpoints=qa_get_logged_in_points(); // allow late retrieval of points (to avoid unnecessary DB query when using external users)
		
			if ($userpoints>=qa_opt($permitoption.'_points'))
				$permit=($permit==QA_PERMIT_APPROVED_POINTS) ? QA_PERMIT_APPROVED :
					(($permit==QA_PERMIT_POINTS_CONFIRMED) ? QA_PERMIT_CONFIRMED : QA_PERMIT_USERS); // convert if user has enough points
			else
				$permit=QA_PERMIT_EXPERTS; // otherwise show a generic message so they're not tempted to collect points just for this
		}
		
		return qa_permit_value_error($permit, $userid, $userlevel, $userflags);
	}
	
	
	function qa_permit_value_error($permit, $userid, $userlevel, $userflags)
/*
	Check whether $userid of level $userlevel with $userflags can reach the permission level in $permit
	(generally retrieved from an option, but not always). Result as for qa_user_permit_error(...).
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if ($permit>=QA_PERMIT_ALL)
			$error=false;
			
		elseif ($permit>=QA_PERMIT_USERS)
			$error=isset($userid) ? false : 'login';
			
		elseif ($permit>=QA_PERMIT_CONFIRMED) {
			if (!isset($userid))
				$error='login';
			
			elseif (
				QA_FINAL_EXTERNAL_USERS || // not currently supported by single sign-on integration
				($userlevel>=QA_PERMIT_APPROVED) || // if user approved or assigned to a higher level, no need
				($userflags & QA_USER_FLAGS_EMAIL_CONFIRMED) || // actual confirmation
				(!qa_opt('confirm_user_emails')) // if this option off, we can't ask it of the user
			)
				$error=false;
			
			else
				$error='confirm';

		} elseif ($permit>=QA_PERMIT_APPROVED) {
			if (!isset($userid))
				$error='login';
				
			elseif (
				($userlevel>=QA_USER_LEVEL_APPROVED) || // user has been approved
				(!qa_opt('moderate_users')) // if this option off, we can't ask it of the user
			)
				$error=false;
				
			else
				$error='approve';
		
		} elseif ($permit>=QA_PERMIT_EXPERTS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_EXPERT)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_EDITORS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_EDITOR)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_MODERATORS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_MODERATOR)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_ADMINS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_ADMIN)) ? false : 'level';
			
		else
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_SUPER)) ? false : 'level';
		
		if (isset($userid) && ($userflags & QA_USER_FLAGS_USER_BLOCKED) && ($error!='level'))
			$error='userblock';
		
		return $error;
	}
	
	
	function qa_user_captcha_reason($userlevel=null)
/*
	Return whether a captcha is required for posts submitted by the current user. You can pass in a QA_USER_LEVEL_*
	constant in $userlevel to consider the user at a different level to usual (e.g. if they are performing this action
	in a category for which they have elevated privileges).
	
	Possible results:
	'login' => captcha required because the user is not logged in
	'approve' => captcha required because the user has not been approved
	'confirm' => captcha required because the user has not confirmed their email address
	false => captcha is not required
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$reason=false;
		if (!isset($userlevel))
			$userlevel=qa_get_logged_in_level();
		
		if ($userlevel < QA_USER_LEVEL_APPROVED) { // approved users and above aren't shown captchas
			$userid=qa_get_logged_in_userid();
			
			if (qa_opt('captcha_on_anon_post') && !isset($userid))
				$reason='login';
			elseif (qa_opt('moderate_users') && qa_opt('captcha_on_unapproved'))
				$reason='approve';
			elseif (qa_opt('confirm_user_emails') && qa_opt('captcha_on_unconfirmed') && !(qa_get_logged_in_flags() & QA_USER_FLAGS_EMAIL_CONFIRMED) )
				$reason='confirm';
		}
		
		return $reason;
	}

	
	function qa_user_use_captcha($userlevel=null)
/*
	Return whether a captcha should be presented to the logged in user for writing posts. You can pass in a
	QA_USER_LEVEL_* constant in $userlevel to consider the user at a different level to usual.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return qa_user_captcha_reason($userlevel)!=false;
	}
	
	
	function qa_user_moderation_reason($userlevel=null)
/*
	Return whether moderation is required for posts submitted by the current user. You can pass in a QA_USER_LEVEL_*
constant in $userlevel to consider the user at a different level to usual (e.g. if they are performing this action
in a category for which they have elevated privileges).
	
	Possible results:
	'login' => moderation required because the user is not logged in
	'approve' => moderation required because the user has not been approved
	'confirm' => moderation required because the user has not confirmed their email address
	'points' => moderation required because the user has insufficient points
	false => moderation is not required
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$reason=false;
		if (!isset($userlevel))
			$userlevel=qa_get_logged_in_level();
		
		if (
			($userlevel < QA_USER_LEVEL_EXPERT) && // experts and above aren't moderated
			qa_user_permit_error('permit_moderate') // if the user can approve posts, no point in moderating theirs
		) {
			$userid=qa_get_logged_in_userid();
			
			if (isset($userid)) {
				if (qa_opt('moderate_users') && qa_opt('moderate_unapproved') && ($userlevel<QA_USER_LEVEL_APPROVED))
					$reason='approve';
				elseif (qa_opt('confirm_user_emails') && qa_opt('moderate_unconfirmed') && !(qa_get_logged_in_flags() & QA_USER_FLAGS_EMAIL_CONFIRMED) )
					$reason='confirm';
				elseif (qa_opt('moderate_by_points') && (qa_get_logged_in_points() < qa_opt('moderate_points_limit')))
					$reason='points';
			
			} elseif (qa_opt('moderate_anon_post'))
				$reason='login';
		}
		
		return $reason;
	}
	
	
	function qa_user_userfield_label($userfield)
/*
	Return the label to display for $userfield as retrieved from the database, using default if no name set
*/
	{
		if (isset($userfield['content']))
			return $userfield['content'];
		
		else {
			$defaultlabels=array(
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


	function qa_set_form_security_key()
/*
	Set or extend the cookie in browser of non logged-in users which identifies them for the purposes of form security (anti-CSRF protection)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		global $qa_form_key_cookie_set;
		
		if ( (!qa_is_logged_in()) && !@$qa_form_key_cookie_set) {
			$qa_form_key_cookie_set=true;
		
			if (strlen(@$_COOKIE['qa_key'])!=QA_FORM_KEY_LENGTH) {
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				$_COOKIE['qa_key']=qa_random_alphanum(QA_FORM_KEY_LENGTH);
			}
			
			setcookie('qa_key', $_COOKIE['qa_key'], time()+2*QA_FORM_EXPIRY_SECS, '/', QA_COOKIE_DOMAIN); // extend on every page request
		}
	}
	
	
	function qa_calc_form_security_hash($action, $timestamp)
/*
	Return the form security (anti-CSRF protection) hash for an $action (any string), that can be performed within
	QA_FORM_EXPIRY_SECS of $timestamp (in unix seconds) by the current user.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$salt=qa_opt('form_security_salt');
		
		if (qa_is_logged_in())
			return sha1($salt.'/'.$action.'/'.$timestamp.'/'.qa_get_logged_in_userid().'/'.qa_get_logged_in_user_field('passsalt'));
		else
			return sha1($salt.'/'.$action.'/'.$timestamp.'/'.@$_COOKIE['qa_key']); // lower security for non logged in users - code+cookie can be transferred
	}
	
	
	function qa_get_form_security_code($action)
/*
	Return the full form security (anti-CSRF protection) code for an $action (any string) performed within
	QA_FORM_EXPIRY_SECS of now by the current user.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		qa_set_form_security_key();
		
		$timestamp=qa_opt('db_time');
		
		return (int)qa_is_logged_in().'-'.$timestamp.'-'.qa_calc_form_security_hash($action, $timestamp);
	}
	
	
	function qa_check_form_security_code($action, $value)
/*
	Return whether $value matches the expected form security (anti-CSRF protection) code for $action (any string) and
	that the code has not expired (if more than QA_FORM_EXPIRY_SECS have passed). Logs causes for suspicion.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$reportproblems=array();
		$silentproblems=array();
		
		if (!isset($value))
			$silentproblems[]='code missing';

		else if (!strlen($value))
			$silentproblems[]='code empty';

		else {
			$parts=explode('-', $value);
			
			if (count($parts)==3) {
				$loggedin=$parts[0];
				$timestamp=$parts[1];
				$hash=$parts[2];
				$timenow=qa_opt('db_time');
				
				if ($timestamp>$timenow)
					$reportproblems[]='time '.($timestamp-$timenow).'s in future';
				elseif ($timestamp<($timenow-QA_FORM_EXPIRY_SECS))
					$silentproblems[]='timeout after '.($timenow-$timestamp).'s';
				
				if (qa_is_logged_in()) {
					if (!$loggedin)
						$silentproblems[]='now logged in';
					
				} else {
					if ($loggedin)
						$silentproblems[]='now logged out';

					else {
						$key=@$_COOKIE['qa_key'];
						
						if (!isset($key))
							$silentproblems[]='key cookie missing';
						elseif (!strlen($key))
							$silentproblems[]='key cookie empty';
						else if (strlen($key)!=QA_FORM_KEY_LENGTH)
							$reportproblems[]='key cookie '.$key.' invalid';
					}
				}

				if (empty($silentproblems) && empty($reportproblems))
					if (strtolower(qa_calc_form_security_hash($action, $timestamp))!=strtolower($hash))
						$reportproblems[]='code mismatch';

			} else
				$reportproblems[]='code '.$value.' malformed';
		}
		
		if (count($reportproblems))
			@error_log(
				'PHP Question2Answer form security violation for '.$action.
				' by '.(qa_is_logged_in() ? ('userid '.qa_get_logged_in_userid()) : 'anonymous').
				' ('.implode(', ', array_merge($reportproblems, $silentproblems)).')'.
				' on '.@$_SERVER['REQUEST_URI'].
				' via '.@$_SERVER['HTTP_REFERER']
			);
		
		return (empty($silentproblems) && empty($reportproblems));
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/