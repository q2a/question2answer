<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/


	File: qa-plugin/facebook-login/qa-plugin.php
	Version: See define()s at top of qa-include/qa-base.php
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
    [metadata]
	Plugin Name [name]: Facebook Login
	Plugin URI [uri]: 
	Plugin Description [description]: Allows users to log in via Facebook
	Plugin Version [version]: 1.1.5
	Plugin Date [date]: 2012-09-13
	Plugin Author [author]: Question2Answer
	Plugin Author URI [author_uri]: http://www.question2answer.org/
	Plugin License [license]: GPLv2
	Plugin Minimum Question2Answer Version [q2a_version]: 1.5
	Plugin Minimum PHP Version [php_version]: 5
	Plugin Update Check URI [update_uri]:
    [/metadata]
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	if (!QA_FINAL_EXTERNAL_USERS) { // login modules don't work with external user integration
		qa_register_plugin_module('login', 'qa-facebook-login.php', 'qa_facebook_login', 'Facebook Login');
		qa_register_plugin_module('page', 'qa-facebook-login-page.php', 'qa_facebook_login_page', 'Facebook Login Page');
		qa_register_plugin_layer('qa-facebook-layer.php', 'Facebook Login Layer');
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/