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

	$loginUserId = qa_get_logged_in_userid();


//	Check which box we're showing (inbox/sent), we're not using Q2A's single-sign on integration and that we're logged in

	$showingInbox = qa_request_part(1) !== 'sent';

	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');

	if (!isset($loginUserId)) {
		$qa_content = qa_content_prepare();
		$qa_content['error'] = qa_insert_login_links(qa_lang_html('misc/message_must_login'), qa_request());
		return $qa_content;
	}

	if ( !qa_opt('allow_private_messages') )
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';


//	Find the user profile and questions and answers for this handle

	$pmSpec = $showingInbox
		? qa_db_messages_inbox_selectspec('private', $loginUserId, true)
		: qa_db_messages_outbox_selectspec('private', $loginUserId, true);

	$userMessages = qa_db_select_with_pending($pmSpec);


//	Prepare content for theme

	$qa_content = qa_content_prepare();
	$qa_content['title'] = $showingInbox ? qa_lang_html('misc/pm_inbox_title') : qa_lang_html('misc/pm_outbox_title');

	$qa_content['message_list'] = array(
		'tags' => 'id="privatemessages"',
		'messages' => array(),
	);

	$htmlDefaults = qa_message_html_defaults();
	if (!$showingInbox) {
		$htmlDefaults['towhomview'] = true;
	}

	foreach ($userMessages as $message) {
		$qa_content['message_list']['messages'][] = qa_message_html_fields($message, $htmlDefaults);
	}

	$qa_content['navigation']['sub'] = array(
		'inbox' => array(
			'label' => qa_lang_html('misc/inbox'),
			'url' => qa_path_html('messages'),
			'selected' => $showingInbox,
		),

		'outbox' => array(
			'label' => qa_lang_html('misc/outbox'),
			'url' => qa_path_html('messages/sent'),
			'selected' => !$showingInbox,
		)
	);

	return $qa_content;
