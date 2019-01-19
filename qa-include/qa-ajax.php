<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Front line of response to Ajax requests, routing as appropriate


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

// Output this header as early as possible

header('Content-Type: text/plain; charset=utf-8');


// Ensure no PHP errors are shown in the Ajax response

@ini_set('display_errors', 0);


// Load the Q2A base file which sets up a bunch of crucial functions

$qa_autoconnect = false;
require 'qa-base.php';

qa_report_process_stage('init_ajax');


// Get general Ajax parameters from the POST payload, and clear $_GET

qa_set_request(qa_post_text('qa_request'), qa_post_text('qa_root'));

$_GET = array(); // for qa_self_html()


// Database failure handler

function qa_ajax_db_fail_handler()
{
	echo "QA_AJAX_RESPONSE\n0\nA database error occurred.";
	qa_exit('error');
}


// Perform the appropriate Ajax operation

$routing = array(
	'notice' => 'notice.php',
	'favorite' => 'favorite.php',
	'vote' => 'vote.php',
	'recalc' => 'recalc.php',
	'mailing' => 'mailing.php',
	'version' => 'version.php',
	'category' => 'category.php',
	'asktitle' => 'asktitle.php',
	'answer' => 'answer.php',
	'comment' => 'comment.php',
	'click_a' => 'click-answer.php',
	'click_c' => 'click-comment.php',
	'click_admin' => 'click-admin.php',
	'show_cs' => 'show-comments.php',
	'wallpost' => 'wallpost.php',
	'click_wall' => 'click-wall.php',
	'click_pm' => 'click-pm.php',
);

$operation = qa_post_text('qa_operation');

if (isset($routing[$operation])) {
	$qa_db->connect('qa_ajax_db_fail_handler');
	qa_initialize_postdb_plugins();

	qa_initialize_buffering();
	require QA_INCLUDE_DIR . 'ajax/' . $routing[$operation];

	$qa_db->disconnect();
}
