<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Initialization for page requests


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

require_once QA_INCLUDE_DIR . 'app/page.php';


// Below are the steps that actually execute for this file - all the above are function definitions

global $qa_usage;

qa_report_process_stage('init_page');
qa_db_connect('qa_page_db_fail_handler');
qa_initialize_postdb_plugins();

qa_page_queue_pending();
qa_load_state();
qa_check_login_modules();

if (QA_DEBUG_PERFORMANCE)
	$qa_usage->mark('setup');

qa_check_page_clicks();

$qa_content = qa_get_request_content();

if (is_array($qa_content)) {
	if (QA_DEBUG_PERFORMANCE)
		$qa_usage->mark('view');

	qa_output_content($qa_content);

	if (QA_DEBUG_PERFORMANCE)
		$qa_usage->mark('theme');

	if (qa_do_content_stats($qa_content) && QA_DEBUG_PERFORMANCE)
		$qa_usage->mark('stats');

	if (QA_DEBUG_PERFORMANCE)
		$qa_usage->output();
}

qa_db_disconnect();
