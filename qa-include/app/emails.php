<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Wrapper functions for sending email notifications to users


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
	header('Location: ../../');
	exit;
}

use Q2A\Exceptions\FatalErrorException;
use Q2A\Notifications\Email;
use Q2A\Notifications\Mailer;
use Q2A\Notifications\Status as NotifyStatus;

/**
 * Suspend the sending of all email notifications via qa_send_notification(...) if $suspend is true, otherwise
 * reinstate it. A counter is kept to allow multiple calls.
 * @param bool $suspend
 * @deprecated: Use \Q2A\Notifications\Status::suspend() instead.
 */
function qa_suspend_notifications($suspend = true)
{
	NotifyStatus::suspend($suspend);
}


/**
 * Send email to person with $userid and/or $email and/or $handle (null/invalid values are ignored or retrieved from
 * user database as appropriate). Email uses $subject and $body, after substituting each key in $subs with its
 * corresponding value, plus applying some standard substitutions such as ^site_title, ^site_url, ^handle and ^email.
 * If notifications are suspended, then a null value is returned.
 * @param mixed $userid
 * @param string $email
 * @param string $handle
 * @param string $subject
 * @param string $body
 * @param array $subs
 * @param bool|false $html
 * @return bool|null
 */
function qa_send_notification($userid, $email, $handle, $subject, $body, $subs, $html = false)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	if (NotifyStatus::isSuspended()) {
		return null;
	}

	if ($userid) {
		$sender = Email::createByUserID($userid, $email, $handle);
	} else {
		$sender = Email::createByEmailAddress($email, $handle);
	}

	return $sender->sendMessage($subject, $body, $subs, $html);
}


/**
 * Send the email based on the $params array - the following keys are required (some can be empty): fromemail,
 * fromname, toemail, toname, subject, body, html
 * @param array $params
 * @return bool
 * @throws phpmailerException
 */
function qa_send_email($params)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$mailer = new Mailer($params);

	$send_status = $mailer->send();
	if (!$send_status) {
		@error_log('PHP Question2Answer email send error: ' . $mailer->ErrorInfo);
	}
	return $send_status;
}
