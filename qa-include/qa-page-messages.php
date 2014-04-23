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
	$loginUserHandle = qa_get_logged_in_handle();


//	Check which box we're showing (inbox/sent), we're not using Q2A's single-sign on integration and that we're logged in

	$req = qa_request_part(1);
	if ($req === null)
		$showBox = 'inbox';
	else if ($req === 'sent')
		$showBox = 'outbox';
	else
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';

	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');

	if (!isset($loginUserId)) {
		$qa_content = qa_content_prepare();
		$qa_content['error'] = qa_insert_login_links(qa_lang_html('misc/message_must_login'), qa_request());
		return $qa_content;
	}

	if (!qa_opt('allow_private_messages') || !qa_opt('show_message_history'))
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';


//	Find the messages for this user

	$start = qa_get_start();
	$pagesize = qa_opt('page_size_wall');

	// get number of messages then actual messages for this page
	$func = 'qa_db_messages_'.$showBox.'_selectspec';
	$pmSpecCount = qa_db_messages_count_selectspec( $func('private', $loginUserId, true) );
	$pmSpec = $func('private', $loginUserId, true, $pagesize, $start);

	list($numMessages, $userMessages) = qa_db_select_with_pending($pmSpecCount, $pmSpec);
	$count = $numMessages['count'];


//	Prepare content for theme

	$qa_content = qa_content_prepare();
	$qa_content['title'] = qa_lang_html('misc/pm_'.$showBox.'_title');

	$qa_content['message_list'] = array(
		'tags' => 'id="privatemessages"',
		'messages' => array(),
	);

	$htmlDefaults = qa_message_html_defaults();
	if ($showBox === 'outbox')
		$htmlDefaults['towhomview'] = true;

	foreach ($userMessages as $message) {
		$msgFormat = qa_message_html_fields($message, $htmlDefaults);
		$replyHandle = $showBox == 'inbox' ? $message['fromhandle'] : $message['tohandle'];

		$msgFormat['form'] = array(
			'style' => 'light',
			'buttons' => array(
				'reply' => array(
					'tags' => 'onclick="window.location.href=\''.qa_path_html('message/'.$replyHandle).'\'"',
					'label' => qa_lang_html('question/reply_button'),
				),
			),
		);

		$qa_content['message_list']['messages'][] = $msgFormat;
	}

	$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

	$qa_content['navigation']['sub'] = qa_messages_sub_navigation($showBox);

	return $qa_content;
