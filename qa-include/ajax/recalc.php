<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax admin recalculation requests


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
require_once QA_INCLUDE_DIR . 'app/recalc.php';


if (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN) {
	if (!qa_check_form_security_code('admin/recalc', qa_post_text('code'))) {
		$state = '';
		$message = qa_lang('misc/form_security_reload');

	} else {
		$state = qa_post_text('state');
		$stoptime = time() + 3;

		while (qa_recalc_perform_step($state) && time() < $stoptime) {
			// wait
		}

		$message = qa_recalc_get_message($state);
	}

} else {
	$state = '';
	$message = qa_lang('admin/no_privileges');
}


echo "QA_AJAX_RESPONSE\n1\n" . $state . "\n" . qa_html($message);
