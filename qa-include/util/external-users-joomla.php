<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-external-users-joomla.php
	Description: External user functions for basic Joomla integration


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

require('qa-joomla-helper.php');

function qa_get_mysql_user_column_type()
{
    return "INT";
}

function qa_get_login_links($relative_url_prefix, $redirect_back_to_url)
{
    $jhelper = new qa_joomla_helper();
    $config_urls = $jhelper->trigger_get_urls_event();

    return array(
        'login'     => $config_urls['login'],
        'register'  => $config_urls['reg'],
        'logout'    => $config_urls['logout']
    );
}

function qa_get_logged_in_user()
{
    $jhelper = new qa_joomla_helper();
    $user = $jhelper->get_user();
    $config_urls = $jhelper->trigger_get_urls_event();

    if($user) {
      if($user->guest || $user->block) {
        return null;
      }

      $access = $jhelper->trigger_access_event($user);

      $level = QA_USER_LEVEL_BASIC;
      if($access['post'])  {$level = QA_USER_LEVEL_APPROVED;}
      if($access['edit'])  {$level = QA_USER_LEVEL_EDITOR;}
      if($access['mod'])   {$level = QA_USER_LEVEL_MODERATOR;}
      if($access['admin']) {$level = QA_USER_LEVEL_ADMIN;}
      if($access['super'] || $user->get('isRoot')) {$level = QA_USER_LEVEL_SUPER;}

      $teamGroup = $jhelper->trigger_team_group_event($user);

      return array(
        'userid' => $user->id,
        'publicusername' => $user->username,
        'email' => $user->email,
        'level' => $level,
      );
    }

    return null;
}

function qa_get_user_email($userid)
{
    $jhelper = new qa_joomla_helper();
    $user = $jhelper->get_user($userid);

    if($user) {
      return $user->email;
    }

    return null;
}

function qa_get_userids_from_public($publicusernames)
{
    $output = array();
    if(count($publicusernames)) {
      $jhelper = new qa_joomla_helper();
      foreach($publicusernames as $username) {
        $output[$username] = $jhelper->get_userid($username);
      }
    }
    return $output;
}

function qa_get_public_from_userids($userids)
{
    $output = array();
    if(count($userids)) {
      $jhelper = new qa_joomla_helper();
      foreach($userids as $userID) {
        $user = $jhelper->get_user($userID);
        $teamGroup = $jhelper->trigger_team_group_event($user);
        $output[$userID] = $user->username;
      }
    }
    return $output;
}

function qa_get_logged_in_user_html($logged_in_user, $relative_url_prefix)
{
    $publicusername=$logged_in_user['publicusername'];
    return '<a href="'.qa_path_html('user/'.$publicusername).'" class="qa-user-link">'.htmlspecialchars($publicusername).'</a>';
}

function qa_get_users_html($userids, $should_include_link, $relative_url_prefix)
{
    $useridtopublic = qa_get_public_from_userids($userids);
    $usershtml = array();

    foreach ($userids as $userid) {
        $publicusername = $useridtopublic[$userid];
        $usershtml[$userid] = htmlspecialchars($publicusername);

        if ($should_include_link) {
            $usershtml[$userid]='<a href="'.qa_path_html('user/'.$publicusername).'" class="qa-user-link">'.$usershtml[$userid].'</a>';
        }
    }

    return $usershtml;
}

function qa_avatar_html_from_userid($userid, $size, $padding)
{
    $jhelper = new qa_joomla_helper();
    $avatarURL = $jhelper->trigger_get_avatar_event($userid, $size);

    $avatarHTML = $avatarURL ? "<img src='{$avatarURL}' class='qa-avatar-image' alt=''/>" : '';
    if ($padding) {
        //If $padding is true, the HTML you return should render to a square of $size x $size pixels, even if the avatar is not square.
        $avatarHTML = "<span style='display:inline-block; width:{$size}px; height:{$size}px; overflow:hidden;'>{$avatarHTML}</span>";
    }
    return $avatarHTML;
}

function qa_user_report_action($userid, $action)
{
    $jhelper = new qa_joomla_helper();
    $jhelper->trigger_log_event($userid, $action);
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
