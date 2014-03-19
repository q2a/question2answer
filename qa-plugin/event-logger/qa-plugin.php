<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/


	File: qa-plugin/event-logger/qa-plugin.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Initiates event logger plugin


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
	Plugin Name [name]: Event Logger
	Plugin URI [uri]: 
	Plugin Description [description]: Stores a record of user activity in the database and/or log files
	Plugin Version [version]: 1.1
	Plugin Date [date]: 2011-12-06
	Plugin Author [author]: Question2Answer
	Plugin Author URI [author_uri]: http://www.question2answer.org/
	Plugin License [license]: GPLv2
	Plugin Minimum Question2Answer Version [version]: 1.5
	Plugin Update Check URI [update_uri]: 
	[/metadata]
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	qa_register_plugin_module('event', 'qa-event-logger.php', 'qa_event_logger', 'Event Logger');


/*
	Omit PHP closing tag to help avoid accidental output
*/