<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-index.php
	Description: The Grand Central of Q2A - most requests come through here


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

//	Try our best to set base path here just in case it wasn't set in index.php (pre version 1.0.1)

	if (!defined('QA_BASE_DIR'))
		define('QA_BASE_DIR', dirname(empty($_SERVER['SCRIPT_FILENAME']) ? dirname(__FILE__) : $_SERVER['SCRIPT_FILENAME']).'/');


//	If this is an special non-page request, branch off here

	if (isset($_POST['qa']) && $_POST['qa'] == 'ajax')
		require 'qa-ajax.php';

	elseif (isset($_GET['qa']) && $_GET['qa'] == 'image')
		require 'qa-image.php';

	elseif (isset($_GET['qa']) && $_GET['qa'] == 'blob')
		require 'qa-blob.php';

	else {

	//	Otherwise, load the Q2A base file which sets up a bunch of crucial stuff

		require 'qa-base.php';

		initialize_core();

	//	Branch off to appropriate file for further handling

		$requestlower = strtolower(qa_request());

		if ($requestlower == 'install')
			require QA_INCLUDE_DIR.'qa-install.php';
		elseif ($requestlower == 'url/test/'.QA_URL_TEST_STRING)
			require QA_INCLUDE_DIR.'qa-url-test.php';
		else {
			// enable gzip compression for output (needs to come early)
			// skip admin pages since some of these contain lengthy processes
			if (QA_HTML_COMPRESSION && substr($requestlower, 0, 6) != 'admin/') {
				if (extension_loaded('zlib') && !headers_sent())
					ob_start('ob_gzhandler');
			}

			if (substr($requestlower, 0, 5) == 'feed/')
				require QA_INCLUDE_DIR.'qa-feed.php';
			else
				require QA_INCLUDE_DIR.'qa-page.php';
		}
	}

	qa_report_process_stage('shutdown', array(''));
