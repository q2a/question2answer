<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/


	File: qa-include/qa-page-message.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for private messaging page


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-limits.php';

	$loginuserid = qa_get_logged_in_userid();


//	Check we have a handle, we're not using Q2A's single-sign on integration and that we're logged in

	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');

	if (!isset($loginuserid)) {
		$qa_content = qa_content_prepare();
		$qa_content['error'] = qa_insert_login_links(qa_lang_html('misc/message_must_login'), qa_request());
		return $qa_content;
	}


//	Find the user profile and questions and answers for this handle

	list($toaccount, $usermessages) = qa_db_select_with_pending(
		qa_db_user_account_selectspec(qa_get_logged_in_handle(), false),
		// qa_db_recent_messages_selectspec($loginuserid, true, null, null)

		qa_db_messages_inbox_selectspec('private', $loginuserid, true)
		// qa_db_messages_outbox_selectspec('private', $loginuserid, true)
	);


//	Check the user exists and work out what can and can't be set (if not using single sign-on)

	if ( !qa_opt('allow_private_messages') )
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';







//	Prepare content for theme

	$qa_content = qa_content_prepare();
	$qa_content['title'] = qa_lang_html('misc/private_messages_title');
	$qa_content['error'] = @$pageerror;
	$qa_content['raw']['account'] = $toaccount; // for plugin layers to access

	$qa_content['message_list'] = array(
		// 'title' => '<a name="pms">'.qa_lang_html('misc/private_messages_title').'</a>',
		'tags' => 'id="privatemessages"',
		'messages' => array(),
	);

	$options = qa_message_html_defaults();
	foreach ($usermessages as $message) {
		$qa_content['message_list']['messages'][] = qa_message_html_fields($message, $options);
	}

	return $qa_content;
