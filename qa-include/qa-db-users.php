<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-users.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Database-level access to user management tables (if not using single sign-on)


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


	function qa_db_calc_passcheck($password, $salt)
/*
	Return the expected value for the passcheck column given the $password and password $salt
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return sha1(substr($salt, 0, 8).$password.substr($salt, 8));
	}
	

	function qa_db_user_create($email, $password, $handle, $level, $ip)
/*
	Create a new user in the database with $email, $password, $handle, privilege $level, and $ip address
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$salt=isset($password) ? qa_random_alphanum(16) : null;
		
		qa_db_query_sub(
			'INSERT INTO ^users (created, createip, email, passsalt, passcheck, level, handle, loggedin, loginip) '.
			'VALUES (NOW(), COALESCE(INET_ATON($), 0), $, $, UNHEX($), #, $, NOW(), COALESCE(INET_ATON($), 0))',
			$ip, $email, $salt, isset($password) ? qa_db_calc_passcheck($password, $salt) : null, (int)$level, $handle, $ip
		);
		
		return qa_db_last_insert_id();
	}

		
	function qa_db_user_delete($userid)
/*
	Delete user $userid from the database, along with everything they have ever done (to the extent that it's possible)
*/
	{
		qa_db_query_sub('UPDATE ^posts SET lastuserid=NULL WHERE lastuserid=$', $userid);
		qa_db_query_sub('DELETE FROM ^userpoints WHERE userid=$', $userid);
		qa_db_query_sub('DELETE FROM ^blobs WHERE blobid=(SELECT avatarblobid FROM ^users WHERE userid=$)', $userid);
		qa_db_query_sub('DELETE FROM ^users WHERE userid=$', $userid);
		
		// All the queries below should be superfluous due to foreign key constraints, but just in case the user switched to MyISAM.
		// Note also that private messages to/from that user are kept since we don't have all the keys we need to delete efficiently.

		qa_db_query_sub('UPDATE ^posts SET userid=NULL WHERE userid=$', $userid);
		qa_db_query_sub('DELETE FROM ^userlogins WHERE userid=$', $userid);
		qa_db_query_sub('DELETE FROM ^userprofile WHERE userid=$', $userid);
		qa_db_query_sub('DELETE FROM ^userfavorites WHERE userid=$', $userid);
		qa_db_query_sub('DELETE FROM ^userevents WHERE userid=$', $userid);
		qa_db_query_sub('DELETE FROM ^uservotes WHERE userid=$', $userid);
		qa_db_query_sub('DELETE FROM ^userlimits WHERE userid=$', $userid);
	}

	
	function qa_db_user_find_by_email($email)
/*
	Return the ids of all users in the database which match $email (should be one or none)
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT userid FROM ^users WHERE email=$',
			$email
		));
	}


	function qa_db_user_find_by_handle($handle)
/*
	Return the ids of all users in the database which match $handle (=username), should be one or none
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT userid FROM ^users WHERE handle=$',
			$handle
		));
	}
	
	
	function qa_db_user_get_userid_handles($userids)
/*
	Return an array mapping mapping each userid in $userids that can be found to that user's handle
*/
	{
		if (count($userids))
			return qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT userid, handle FROM ^users WHERE userid IN (#)',
				$userids
			), 'userid', 'handle');
		else
			return array();
	}
	

	function qa_db_user_get_handle_userids($handles)
/*
	Return an array mapping mapping each handle in $handle that can be found to that user's userid
*/
	{
		if (count($handles))
			return qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT handle, userid FROM ^users WHERE handle IN ($)',
				$handles
			), 'handle', 'userid');
		else
			return array();
	}
	

	function qa_db_user_set($userid, $field, $value)
/*
	Set $field of $userid to $value in the database users table
*/
	{
		qa_db_query_sub(
			'UPDATE ^users SET '.qa_db_escape_string($field).'=$ WHERE userid=$',
			$value, $userid
		);
	}


	function qa_db_user_set_password($userid, $password)
/*
	Set the password of $userid to $password, and reset their salt at the same time
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$salt=qa_random_alphanum(16);

		qa_db_query_sub(
			'UPDATE ^users SET passsalt=$, passcheck=UNHEX($) WHERE userid=$',
			$salt, qa_db_calc_passcheck($password, $salt), $userid
		);
	}
	

	function qa_db_user_set_flag($userid, $flag, $set)
/*
	Switch on the $flag bit of the flags column for $userid if $set is true, or switch off otherwise
*/
	{
		qa_db_query_sub(
			'UPDATE ^users SET flags=flags'.($set ? '|' : '&~').'# WHERE userid=$',
			$flag, $userid
		);
	}

	
	function qa_db_user_rand_emailcode()
/*
	Return a random string to be used for a user's emailcode column
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		return qa_random_alphanum(8);
	}
	

	function qa_db_user_rand_sessioncode()
/*
	Return a random string to be used for a user's sessioncode column (for browser session cookies)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		return qa_random_alphanum(8);
	}

	
	function qa_db_user_profile_set($userid, $field, $value)
/*
	Set a row in the database user profile table to store $value for $field for $userid
*/
	{
		qa_db_query_sub(
			'REPLACE ^userprofile (title, content, userid) VALUES ($, $, $)',
			$field, $value, $userid
		);
	}

	
	function qa_db_user_logged_in($userid, $ip)
/*
	Note in the database that $userid just logged in from $ip address
*/
	{
		qa_db_query_sub(
			'UPDATE ^users SET loggedin=NOW(), loginip=COALESCE(INET_ATON($), 0) WHERE userid=$',
			$ip, $userid
		);
	}
	

	function qa_db_user_written($userid, $ip)
/*
	Note in the database that $userid just performed a write operation from $ip address
*/
	{
		qa_db_query_sub(
			'UPDATE ^users SET written=NOW(), writeip=COALESCE(INET_ATON($), 0) WHERE userid=$',
			$ip, $userid
		);
	}
	
	
	function qa_db_user_login_add($userid, $source, $identifier)
/*
	Add an external login in the database for $source and $identifier for user $userid
*/
	{
		qa_db_query_sub(
			'INSERT INTO ^userlogins (userid, source, identifier, identifiermd5) '.
			'VALUES ($, $, $, UNHEX($))',
			$userid, $source, $identifier, md5($identifier)
		);
	}
	

	function qa_db_user_login_find($source, $identifier)
/*
	Return some information about the user with external login $source and $identifier in the database, if a match is found
*/
	{
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT ^userlogins.userid, handle, email FROM ^userlogins LEFT JOIN ^users ON ^userlogins.userid=^users.userid '.
			'WHERE source=$ AND identifiermd5=UNHEX($) AND identifier=$',
			$source, md5($identifier), $identifier
		));
	}
	
	
	function qa_db_user_login_sync($sync)
/*
	Lock all tables if $sync is true, otherwise unlock them. Used to synchronize creation of external login mappings.
*/
	{
		if ($sync) { // need to lock all tables since any could be used by a plugin's event module
			$tables=qa_db_list_tables();

			$locks=array();
			foreach ($tables as $table)
				$locks[]=$table.' WRITE';
					
			qa_db_query_sub('LOCK TABLES '.implode(', ', $locks));

		} else
			qa_db_query_sub('UNLOCK TABLES');
	}
	
	
	function qa_db_user_levels_set($userid, $userlevels)
/*
	Reset the full set of context-specific (currently, per category) user levels for user $userid to $userlevels, where
	$userlevels is an array of arrays, the inner arrays containing items 'entitytype', 'entityid' and 'level'.
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^userlevels WHERE userid=$',
			$userid
		);
		
		foreach ($userlevels as $userlevel)
			qa_db_query_sub(
				'REPLACE ^userlevels (userid, entitytype, entityid, level) VALUES ($, $, #, #)',
				$userid, $userlevel['entitytype'], $userlevel['entityid'], $userlevel['level']
			);
	}
	
	
	function qa_db_users_get_mailing_next($lastuserid, $count)
/*
	Get the information required for sending a mailing to the next $count users with userids greater than $lastuserid
*/
	{
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT userid, email, handle, emailcode, flags FROM ^users WHERE userid># ORDER BY userid LIMIT #',
			$lastuserid, $count
		));
	}
	
	
	function qa_db_uapprovecount_update()
/*
	Update the cached count of the number of users who are awaiting approval after registration
*/
	{
		if ( qa_should_update_counts() && !QA_FINAL_EXTERNAL_USERS )
			qa_db_query_sub(
				"REPLACE ^options (title, content) SELECT 'cache_uapprovecount', COUNT(*) FROM ^users WHERE level<# AND NOT (flags&#)",
				QA_USER_LEVEL_APPROVED, QA_USER_FLAGS_USER_BLOCKED
			);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/