<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax single clicks on private messages


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

require_once QA_INCLUDE_DIR . 'app/messages.php';
require_once QA_INCLUDE_DIR . 'app/users.php';
require_once QA_INCLUDE_DIR . 'app/cookies.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';


$loginUserId = qa_get_logged_in_userid();
$loginUserHandle = qa_get_logged_in_handle();

$fromhandle = qa_post_text('handle');
$start = (int)qa_post_text('start');
$box = qa_post_text('box');
$pagesize = qa_opt('page_size_pms');

if (!isset($loginUserId) || $loginUserHandle !== $fromhandle || !in_array($box, array('inbox', 'outbox'))) {
	echo "QA_AJAX_RESPONSE\n0\n";
	return;
}


$func = 'qa_db_messages_' . $box . '_selectspec';
$pmSpec = $func('private', $loginUserId, true, $start, $pagesize);
$userMessages = qa_db_select_with_pending($pmSpec);

foreach ($userMessages as $message) {
	if (qa_clicked('m' . $message['messageid'] . '_dodelete')) {
		if (qa_check_form_security_code('pm-' . $fromhandle, qa_post_text('code'))) {
			qa_pm_delete($loginUserId, qa_get_logged_in_handle(), qa_cookie_get(), $message, $box);
			echo "QA_AJAX_RESPONSE\n1\n";
			return;
		}
	}
}

echo "QA_AJAX_RESPONSE\n0\n";
