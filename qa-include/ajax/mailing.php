<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax mailing loop requests


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

require_once QA_INCLUDE_DIR . 'app/users.php';
require_once QA_INCLUDE_DIR . 'app/mailing.php';


$continue = false;

if (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN) {
	$starttime = time();

	qa_mailing_perform_step();

	if ($starttime == time()) {
		sleep(1); // make sure at least one second has passed
	}

	$message = qa_mailing_progress_message();

	if (isset($message)) {
		$continue = true;
	} else {
		$message = qa_lang('admin/mailing_complete');
	}
} else {
	$message = qa_lang('admin/no_privileges');
}

$response = array(
	'continue' => $continue,
	'message' => qa_html($message),
);

echo json_encode($response);
