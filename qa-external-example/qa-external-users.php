<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-external-example/qa-external-users.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Example of how to integrate with your own user database


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


/*
	=========================================================================
	THIS FILE ALLOWS YOU TO INTEGRATE WITH AN EXISTING USER MANAGEMENT SYSTEM
	=========================================================================

	It is used if QA_EXTERNAL_USERS is set to true in qa-config.php.
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	function qa_get_mysql_user_column_type()
	{
/*
	==========================================================================
	     YOU MUST MODIFY THIS FUNCTION *BEFORE* Q2A CREATES ITS DATABASE
	==========================================================================

	You should return the appropriate MySQL column type to use for the userid,
	for smooth integration with your existing users. Allowed options are:
	
	SMALLINT, SMALLINT UNSIGNED, MEDIUMINT, MEDIUMINT UNSIGNED, INT, INT UNSIGNED,
	BIGINT, BIGINT UNSIGNED or VARCHAR(x) where x is the maximum length.
*/

	//	Set this before anything else

		return null;

	/*
		Example 1 - suitable if:
		
		* You use textual user identifiers with a maximum length of 32

		return 'VARCHAR(32)';
	*/
	
	/*
		Example 2 - suitable if:
		
		* You use unsigned numerical user identifiers in an INT UNSIGNED column
		
		return 'INT UNSIGNED';
	*/
	}


	function qa_get_login_links($relative_url_prefix, $redirect_back_to_url)
/*
	===========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER Q2A CREATES ITS DATABASE
	===========================================================================

	You should return an array containing URLs for the login, register and logout pages on
	your site. These URLs will be used as appropriate within the Q2A site.
	
	You may return absolute or relative URLs for each page. If you do not want one of the links
	to show, omit it from the array, or use null or an empty string.
	
	If you use absolute URLs, then return an array with the URLs in full (see example 1 below).

	If you use relative URLs, the URLs should start with $relative_url_prefix, followed by the
	relative path from the root of the Q2A site to your login page. Like in example 2 below, if
	the Q2A site is in a subdirectory, $relative_url_prefix.'../' refers to your site root.
	
	Now, about $redirect_back_to_url. Let's say a user is viewing a page on the Q2A site, and
	clicks a link to the login URL that you returned from this function. After they log in using
	the form on your main site, they want to automatically go back to the page on the Q2A site
	where they came from. This can be done with an HTTP redirect, but how does your login page
	know where to redirect the user to? The solution is $redirect_back_to_url, which is the URL
	of the page on the Q2A site where you should send the user once they've successfully logged
	in. To implement this, you can add $redirect_back_to_url as a parameter to the login URL
	that you return from this function. Your login page can then read it in from this parameter,
	and redirect the user back to the page after they've logged in. The same applies for your
	register and logout pages. Note that the URL you are given in $redirect_back_to_url is
	relative to the root of the Q2A site, so you may need to add something.
*/
	{

	//	Until you edit this function, don't show login, register or logout links

		return array(
			'login' => null,
			'register' => null,
			'logout' => null
		);

	/*
		Example 1 - using absolute URLs, suitable if:
		
		* Your Q2A site:       http://qa.mysite.com/
		* Your login page:     http://www.mysite.com/login
		* Your register page:  http://www.mysite.com/register
		* Your logout page:    http://www.mysite.com/logout
		
		return array(
			'login' => 'http://www.mysite.com/login',
			'register' => 'http://www.mysite.com/register',
			'logout' => 'http://www.mysite.com/logout',
		);
		
	*/
	
	/*
		Example 2 - using relative URLs, suitable if:
		
		* Your Q2A site:       http://www.mysite.com/qa/
		* Your login page:     http://www.mysite.com/login.php
		* Your register page:  http://www.mysite.com/register.php
		* Your logout page:    http://www.mysite.com/logout.php
	
		return array(
			'login' => $relative_url_prefix.'../login.php',
			'register' => $relative_url_prefix.'../register.php',
			'logout' => $relative_url_prefix.'../logout.php',
		);
	*/
		
	/*
		Example 3 - using relative URLs, and implementing $redirect_back_to_url
		
		In this example, your pages login.php, register.php and logout.php should read in the
		parameter $_GET['redirect'], and redirect the user to the page specified by that
		parameter once they have successfully logged in, registered or logged out.
		
		return array(
			'login' => $relative_url_prefix.'../login.php?redirect='.urlencode('qa/'.$redirect_back_to_url),
			'register' => $relative_url_prefix.'../register.php?redirect='.urlencode('qa/'.$redirect_back_to_url),
			'logout' => $relative_url_prefix.'../logout.php?redirect='.urlencode('qa/'.$redirect_back_to_url),
		);
	*/

	}
	

	function qa_get_logged_in_user()
/*
	===========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER Q2A CREATES ITS DATABASE
	===========================================================================

	qa_get_logged_in_user()
	
	You should check (using $_COOKIE, $_SESSION or whatever is appropriate) whether a user is
	currently logged in. If not, return null. If so, return an array with the following elements:

	* userid: a user id appropriate for your response to qa_get_mysql_user_column_type()
	* publicusername: a user description you are willing to show publicly, e.g. the username
	* email: the logged in user's email address
	* passsalt: (optional) password salt specific to this user, used for form security codes
	* level: one of the QA_USER_LEVEL_* values below to denote the user's privileges:
	
	QA_USER_LEVEL_BASIC, QA_USER_LEVEL_EDITOR, QA_USER_LEVEL_ADMIN, QA_USER_LEVEL_SUPER
	
	To indicate that the user is blocked you can also add an element 'blocked' with the value true.
	Blocked users are not allowed to perform any write actions such as voting or posting.
	
	The result of this function will be passed to your other function qa_get_logged_in_user_html()
	so you may add any other elements to the returned array if they will be useful to you.

	Call qa_db_connection() to get the connection to the Q2A database. If your database is shared with
	Q2A, you can use this with PHP's MySQL functions such as mysql_query() to run queries.
	
	In order to access the admin interface of your Q2A site, ensure that the array element 'level'
	contains QA_USER_LEVEL_ADMIN or QA_USER_LEVEL_SUPER when you are logged in.
*/
	{
	
	//	Until you edit this function, nobody is ever logged in
	
		return null;
		
	/*
		Example 1 - suitable if:
		
		* You store the login state and user in a PHP session
		* You use textual user identifiers that also serve as public usernames
		* Your database is shared with the Q2A site
		* Your database has a users table that contains emails
		* The administrator has the user identifier 'admin'
		
		session_start();

		if ($_SESSION['is_logged_in']) {
			$userid=$_SESSION['logged_in_userid'];

			$qa_db_connection=qa_db_connection();
			
			$result=mysql_fetch_assoc(
				mysql_query(
					"SELECT email FROM users WHERE userid='".mysql_real_escape_string($userid, $qa_db_connection)."'",
					$qa_db_connection
				)
			);
			
			if (is_array($result))
				return array(
					'userid' => $userid,
					'publicusername' => $userid,
					'email' => $result['email'],
					'level' => ($userid=='admin') ? QA_USER_LEVEL_ADMIN : QA_USER_LEVEL_BASIC
				);
		}
		
		return null;
	*/
	
	/*
		Example 2 - suitable if:
		
		* You store a session ID inside a cookie
		* You use numerical user identifiers
		* Your database is shared with the Q2A site
		* Your database has a sessions table that maps session IDs to users
		* Your database has a users table that contains usernames, emails and a flag for admin privileges
		
		if ($_COOKIE['sessionid']) {
			$qa_db_connection=qa_db_connection();
			
			$result=mysql_fetch_assoc(
				mysql_query(
					"SELECT userid, username, email, admin_flag FROM users WHERE userid=".
					"(SELECT userid FROM sessions WHERE sessionid='".mysql_real_escape_string($_COOKIE['session_id'], $qa_db_connection)."')",
					$qa_db_connection
				)
			);
			
			if (is_array($result))
				return array(
					'userid' => $result['userid'],
					'publicusername' => $result['username'],
					'email' => $result['email'],
					'level' => $result['admin_flag'] ? QA_USER_LEVEL_ADMIN : QA_USER_LEVEL_BASIC
				);
		}
		
		return null;
	*/
		
	}

	
	function qa_get_user_email($userid)
/*
	===========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER Q2A CREATES ITS DATABASE
	===========================================================================

	qa_get_user_email($userid)
	
	Return the email address for user $userid, or null if you don't know it.
	
	Call qa_db_connection() to get the connection to the Q2A database. If your database is shared with
	Q2A, you can use this with PHP's MySQL functions such as mysql_query() to run queries.
*/
	{

	//	Until you edit this function, always return null

		return null;

	/*
		Example 1 - suitable if:
		
		* Your database is shared with the Q2A site
		* Your database has a users table that contains emails
		
		$qa_db_connection=qa_db_connection();
		
		$result=mysql_fetch_assoc(
			mysql_query(
				"SELECT email FROM users WHERE userid='".mysql_real_escape_string($userid, $qa_db_connection)."'",
				$qa_db_connection
			)
		);
		
		if (is_array($result))
			return $result['email'];
		
		return null;
	*/

	}
	

	function qa_get_userids_from_public($publicusernames)
/*
	===========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER Q2A CREATES ITS DATABASE
	===========================================================================

	qa_get_userids_from_public($publicusernames)
	
	You should take the array of public usernames in $publicusernames, and return an array which
	maps valid usernames to internal user ids. For each element of this array, the username should be
	in the key, with the corresponding user id in the value. If your usernames are case- or accent-
	insensitive, keys should contain the usernames as stored, not necessarily as in $publicusernames.
	
	Call qa_db_connection() to get the connection to the Q2A database. If your database is shared with
	Q2A, you can use this with PHP's MySQL functions such as mysql_query() to run queries. If you
	access this database or any other, try to use a single query instead of one per user.
*/
	{

	//	Until you edit this function, always return null

		return null;

	/*
		Example 1 - suitable if:
		
		* You use textual user identifiers that are also shown publicly

		$publictouserid=array();
		
		foreach ($publicusernames as $publicusername)
			$publictouserid[$publicusername]=$publicusername;
		
		return $publictouserid;
	*/

	/*
		Example 2 - suitable if:
		
		* You use numerical user identifiers
		* Your database is shared with the Q2A site
		* Your database has a users table that contains usernames
		
		$publictouserid=array();
			
		if (count($publicusernames)) {
			$qa_db_connection=qa_db_connection();
			
			$escapedusernames=array();
			foreach ($publicusernames as $publicusername)
				$escapedusernames[]="'".mysql_real_escape_string($publicusername, $qa_db_connection)."'";
			
			$results=mysql_query(
				'SELECT username, userid FROM users WHERE username IN ('.implode(',', $escapedusernames).')',
				$qa_db_connection
			);
	
			while ($result=mysql_fetch_assoc($results))
				$publictouserid[$result['username']]=$result['userid'];
		}
		
		return $publictouserid;
	*/

	}


	function qa_get_public_from_userids($userids)
/*
	===========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER Q2A CREATES ITS DATABASE
	===========================================================================

	qa_get_public_from_userids($userids)
	
	This is exactly like qa_get_userids_from_public(), but works in the other direction.
	
	You should take the array of user identifiers in $userids, and return an array which maps valid
	userids to public usernames. For each element of this array, the userid you were given should
	be in the key, with the corresponding username in the value.
	
	Call qa_db_connection() to get the connection to the Q2A database. If your database is shared with
	Q2A, you can use this with PHP's MySQL functions such as mysql_query() to run queries. If you
	access this database or any other, try to use a single query instead of one per user.
*/
	{

	//	Until you edit this function, always return null

		return null;

	/*
		Example 1 - suitable if:
		
		* You use textual user identifiers that are also shown publicly

		$useridtopublic=array();
		
		foreach ($userids as $userid)
			$useridtopublic[$userid]=$userid;
		
		return $useridtopublic;
	*/

	/*
		Example 2 - suitable if:
		
		* You use numerical user identifiers
		* Your database is shared with the Q2A site
		* Your database has a users table that contains usernames
		
		$useridtopublic=array();
		
		if (count($userids)) {
			$qa_db_connection=qa_db_connection();
			
			$escapeduserids=array();
			foreach ($userids as $userid)
				$escapeduserids[]="'".mysql_real_escape_string($userid, $qa_db_connection)."'";
			
			$results=mysql_query(
				'SELECT username, userid FROM users WHERE userid IN ('.implode(',', $escapeduserids).')',
				$qa_db_connection
			);
	
			while ($result=mysql_fetch_assoc($results))
				$useridtopublic[$result['userid']]=$result['username'];
		}
		
		return $useridtopublic;
	*/

	}


	function qa_get_logged_in_user_html($logged_in_user, $relative_url_prefix)
/*
	==========================================================================
	     YOU MAY MODIFY THIS FUNCTION, BUT THE DEFAULT BELOW WILL WORK OK
	==========================================================================

	qa_get_logged_in_user_html($logged_in_user, $relative_url_prefix)

	You should return HTML code which identifies the logged in user, to be displayed next to the
	logout link on the Q2A pages. This HTML will only be shown to the logged in user themselves.

	$logged_in_user is the array that you returned from qa_get_logged_in_user(). Hopefully this
	contains enough information to generate the HTML without another database query, but if not,
	call qa_db_connection() to get the connection to the Q2A database.

	$relative_url_prefix is a relative URL to the root of the Q2A site, which may be useful if
	you want to include a link that uses relative URLs. If the Q2A site is in a subdirectory of
	your site, $relative_url_prefix.'../' refers to your site root (see example 1).

	If you don't know what to display for a user, you can leave the default below. This will
	show the public username, linked to the Q2A profile page for the user.
*/
	{
	
	//	By default, show the public username linked to the Q2A profile page for the user

		$publicusername=$logged_in_user['publicusername'];
		
		return '<a href="'.qa_path_html('user/'.$publicusername).'" class="qa-user-link">'.htmlspecialchars($publicusername).'</a>';

	/*
		Example 1 - suitable if:
		
		* Your Q2A site:       http://www.mysite.com/qa/
		* Your user pages:     http://www.mysite.com/user/[username]
	
		$publicusername=$logged_in_user['publicusername'];
		
		return '<a href="'.htmlspecialchars($relative_url_prefix.'../user/'.urlencode($publicusername)).
			'" class="qa-user-link">'.htmlspecialchars($publicusername).'</a>';
	*/

	/*
		Example 2 - suitable if:
		
		* Your Q2A site:       http://qa.mysite.com/
		* Your user pages:     http://www.mysite.com/[username]/
		* 16x16 user photos:   http://www.mysite.com/[username]/photo-small.jpeg
	
		$publicusername=$logged_in_user['publicusername'];
		
		return '<a href="http://www.mysite.com/'.htmlspecialchars(urlencode($publicusername)).'/" class="qa-user-link">'.
			'<img src="http://www.mysite.com/'.htmlspecialchars(urlencode($publicusername)).'/photo-small.jpeg" '.
			'style="width:16px; height:16px; border:none; margin-right:4px;">'.htmlspecialchars($publicusername).'</a>';
	*/

	}


	function qa_get_users_html($userids, $should_include_link, $relative_url_prefix)
/*
	==========================================================================
	     YOU MAY MODIFY THIS FUNCTION, BUT THE DEFAULT BELOW WILL WORK OK
	==========================================================================

	qa_get_users_html($userids, $should_include_link, $relative_url_prefix)

	You should return an array of HTML to display for each user in $userids. For each element of
	this array, the userid should be in the key, with the corresponding HTML in the value.
	
	Call qa_db_connection() to get the connection to the Q2A database. If your database is shared with
	Q2A, you can use this with PHP's MySQL functions such as mysql_query() to run queries. If you
	access this database or any other, try to use a single query instead of one per user.
	
	If $should_include_link is true, the HTML may include links to user profile pages.
	If $should_include_link is false, links should not be included in the HTML.
	
	$relative_url_prefix is a relative URL to the root of the Q2A site, which may be useful if
	you want to include links that uses relative URLs. If the Q2A site is in a subdirectory of
	your site, $relative_url_prefix.'../' refers to your site root (see example 1).
	
	If you don't know what to display for a user, you can leave the default below. This will
	show the public username, linked to the Q2A profile page for each user.
*/
	{

	//	By default, show the public username linked to the Q2A profile page for each user

		$useridtopublic=qa_get_public_from_userids($userids);
		
		$usershtml=array();

		foreach ($userids as $userid) {
			$publicusername=$useridtopublic[$userid];
			
			$usershtml[$userid]=htmlspecialchars($publicusername);
			
			if ($should_include_link)
				$usershtml[$userid]='<a href="'.qa_path_html('user/'.$publicusername).'" class="qa-user-link">'.$usershtml[$userid].'</a>';
		}
			
		return $usershtml;

	/*
		Example 1 - suitable if:
		
		* Your Q2A site:       http://www.mysite.com/qa/
		* Your user pages:     http://www.mysite.com/user/[username]
	
		$useridtopublic=qa_get_public_from_userids($userids);
		
		foreach ($userids as $userid) {
			$publicusername=$useridtopublic[$userid];
			
			$usershtml[$userid]=htmlspecialchars($publicusername);
			
			if ($should_include_link)
				$usershtml[$userid]='<a href="'.htmlspecialchars($relative_url_prefix.'../user/'.urlencode($publicusername)).
					'" class="qa-user-link">'.$usershtml[$userid].'</a>';
		}
			
		return $usershtml;
	*/

	/*
		Example 2 - suitable if:
		
		* Your Q2A site:       http://qa.mysite.com/
		* Your user pages:     http://www.mysite.com/[username]/
		* User photos (16x16): http://www.mysite.com/[username]/photo-small.jpeg
	
		$useridtopublic=qa_get_public_from_userids($userids);
		
		foreach ($userids as $userid) {
			$publicusername=$useridtopublic[$userid];
			
			$usershtml[$userid]='<img src="http://www.mysite.com/'.htmlspecialchars(urlencode($publicusername)).'/photo-small.jpeg" '.
				'style="width:16px; height:16px; border:0; margin-right:4px;">'.htmlspecialchars($publicusername);
			
			if ($should_include_link)
				$usershtml[$userid]='<a href="http://www.mysite.com/'.htmlspecialchars(urlencode($publicusername)).
					'/" class="qa-user-link">'.$usershtml[$userid].'</a>';
		}
			
		return $usershtml;
	*/

	}


	function qa_avatar_html_from_userid($userid, $size, $padding)
/*
	==========================================================================
	     YOU MAY MODIFY THIS FUNCTION, BUT THE DEFAULT BELOW WILL WORK OK
	==========================================================================
	
	qa_avatar_html_from_userid($userid, $size, $padding)
	
	You should return some HTML for displaying the avatar of $userid on the page.
	If you do not wish to show an avatar for this user, return null.
	
	$size contains the maximum width and height of the avatar to be displayed, in pixels.

	If $padding is true, the HTML you return should render to a square of $size x $size pixels,
	even if the avatar is not square. This can be achieved using CSS padding - see function
	qa_get_avatar_blob_html(...) in qa-app-format.php for an example. If $padding is false,
	the HTML can render to anything which would fit inside a square of $size x $size pixels.
	
	Note that this function may be called many times to render an individual page, so it is not
	a good idea to perform a database query each time it is called. Instead, you can use the fact
	that before qa_avatar_html_from_userid(...) is called, qa_get_users_html(...) will have been
	called with all the relevant users in the array $userids. So you can pull out the information
	you need in qa_get_users_html(...) and cache it in a global variable, for use in this function.
*/
	{
		return null; // show no avatars by default

	/*
		Example 1 - suitable if:
		
		* All your avatars are square
		* Your Q2A site:       http://www.mysite.com/qa/
		* Your avatar images:  http://www.mysite.com/avatar/[userid]-[size]x[size].jpg
		
		$htmlsize=(int)$size;
		
		return '<img src="http://www.mysite.com/avatar/'.htmlspecialchars($userid).'-'.$htmlsize.'x'.$htmlsize.'.jpg" '.
			'width="'.$htmlsize.'" height="'.$htmlsize.'" class="qa-avatar-image" alt=""/>';
	*/

	}
	
	
	function qa_user_report_action($userid, $action)
/*
	==========================================================================
	     YOU MAY MODIFY THIS FUNCTION, BUT THE DEFAULT BELOW WILL WORK OK
	==========================================================================

	qa_user_report_action($userid, $action)

	Informs you about an action by user $userid that modified the database, such as posting,
	voting, etc... If you wish, you may use this to log user activity or monitor for abuse.
	
	Call qa_db_connection() to get the connection to the Q2A database. If your database is shared with
	Q2A, you can use this with PHP's MySQL functions such as mysql_query() to run queries.
	
	$action will be a string (such as 'q_edit') describing the action. These strings will match the
	first $event parameter passed to the process_event(...) function in event modules. In fact, you might
	be better off just using a plugin with an event module instead, since you'll get more information.
	
	FYI, you can get the IP address of the user from qa_remote_ip_address().
*/
	{
		// do nothing by default
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/