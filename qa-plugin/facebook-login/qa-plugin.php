<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/facebook-login/qa-plugin.php
	Description: Initiates Facebook login plugin


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
	Plugin Name: Facebook Login
	Plugin URI:
	Plugin Description: Allows users to log in via Facebook
	Plugin Version: 1.1.5
	Plugin Date: 2012-09-13
	Plugin Author: Question2Answer
	Plugin Author URI: http://www.question2answer.org/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Minimum PHP Version: 5
	Plugin Update Check URI:
*/


if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


// login modules don't work with external user integration
if (!QA_FINAL_EXTERNAL_USERS) {
	qa_register_plugin_module('login', 'qa-facebook-login.php', 'qa_facebook_login', 'Facebook Login');
	qa_register_plugin_module('page', 'qa-facebook-login-page.php', 'qa_facebook_login_page', 'Facebook Login Page');
	qa_register_plugin_layer('qa-facebook-layer.php', 'Facebook Login Layer');
	qa_register_plugin_phrases('qa-facebook-login-lang-*.php', 'facebook_login');
}
