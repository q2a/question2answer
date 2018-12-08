<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax single clicks on posts in admin section


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

require_once QA_INCLUDE_DIR . 'app/admin.php';
require_once QA_INCLUDE_DIR . 'app/users.php';
require_once QA_INCLUDE_DIR . 'app/cookies.php';


$entityid = qa_post_text('entityid');
$action = qa_post_text('action');

if (!qa_check_form_security_code('admin/click', qa_post_text('code'))) {
	$response = array(
		'result' => 'error',
		'error' => array(
			'message' => qa_lang('misc/form_security_reload'),
		),
	);
} else {
	$response = qa_admin_single_click_array($entityid, $action);
}

echo json_encode($response);
