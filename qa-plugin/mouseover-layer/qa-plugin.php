<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/mouseover-layer/qa-plugin.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Initiates mouseover layer plugin


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
	Plugin Name: Mouseover Layer
	Plugin URI: 
	Plugin Description: Shows question content on mouse over in question lists
	Plugin Version: 1.0.1
	Plugin Date: 2011-12-06
	Plugin Author: Question2Answer
	Plugin Author URI: http://www.question2answer.org/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: 
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	qa_register_plugin_layer('qa-mouseover-layer.php', 'Mouseover Layer');
	qa_register_plugin_module('module', 'qa-mouseover-admin-form.php', 'qa_mouseover_admin_form', 'Mouseover Layer');
	

/*
	Omit PHP closing tag to help avoid accidental output
*/