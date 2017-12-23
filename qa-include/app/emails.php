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

require_once QA_INCLUDE_DIR . 'app/options.php';


/**
 * Suspend the sending of all email notifications via qa_send_notification(...) if $suspend is true, otherwise
 * reinstate it. A counter is kept to allow multiple calls.
 * @param bool $suspend
 */
function qa_suspend_notifications($suspend = true)
{
	global $qa_notifications_suspended;

	$qa_notifications_suspended += ($suspend ? 1 : -1);
}


/**
 * Send email to person with $userid and/or $email and/or $handle (null/invalid values are ignored or retrieved from
 * user database as appropriate). Email uses $subject and $body, after substituting each key in $subs with its
 * corresponding value, plus applying some standard substitutions such as ^site_title, ^handle and ^email.
 * @param $userid
 * @param $email
 * @param $handle
 * @param $subject
 * @param $body
 * @param $subs
 * @param bool $html
 * @return bool
 */
function qa_send_notification($userid, $email, $handle, $subject, $body, $subs, $html = false)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	global $qa_notifications_suspended;

	if ($qa_notifications_suspended > 0)
		return false;

	require_once QA_INCLUDE_DIR . 'db/selects.php';
	require_once QA_INCLUDE_DIR . 'util/string.php';

	if (isset($userid)) {
		$needemail = !qa_email_validate(@$email); // take from user if invalid, e.g. @ used in practice
		$needhandle = empty($handle);

		if ($needemail || $needhandle) {
			if (QA_FINAL_EXTERNAL_USERS) {
				if ($needhandle) {
					$handles = qa_get_public_from_userids(array($userid));
					$handle = @$handles[$userid];
				}

				if ($needemail)
					$email = qa_get_user_email($userid);

			} else {
				$useraccount = qa_db_select_with_pending(
					array(
						'columns' => array('email', 'handle'),
						'source' => '^users WHERE userid = #',
						'arguments' => array($userid),
						'single' => true,
					)
				);

				if ($needhandle)
					$handle = @$useraccount['handle'];

				if ($needemail)
					$email = @$useraccount['email'];
			}
		}
	}

	if (isset($email) && qa_email_validate($email)) {
		$subs['^site_title'] = qa_opt('site_title');
		$subs['^handle'] = $handle;
		$subs['^email'] = $email;
		$subs['^open'] = "\n";
		$subs['^close'] = "\n";

		return qa_send_email(array(
			'fromemail' => qa_opt('from_email'),
			'fromname' => qa_opt('site_title'),
			'toemail' => $email,
			'toname' => $handle,
			'subject' => strtr($subject, $subs),
			'body' => (empty($handle) ? '' : qa_lang_sub('emails/to_handle_prefix', $handle)) . strtr($body, $subs),
			'html' => $html,
		));
	}

	return false;
}


/**
 * Send the email based on the $params array - the following keys are required (some can be empty): fromemail,
 * fromname, toemail, toname, subject, body, html
 * @param $params
 * @return bool
 */
function qa_send_email($params)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	// @error_log(print_r($params, true));

	require_once QA_INCLUDE_DIR . 'vendor/PHPMailer/class.phpmailer.php';
	require_once QA_INCLUDE_DIR . 'vendor/PHPMailer/class.smtp.php';

	$mailer = new PHPMailer();
	$mailer->CharSet = 'utf-8';

	$mailer->From = $params['fromemail'];
	$mailer->Sender = $params['fromemail'];
	$mailer->FromName = $params['fromname'];
	$mailer->addAddress($params['toemail'], $params['toname']);
	if (!empty($params['replytoemail'])) {
		$mailer->addReplyTo($params['replytoemail'], $params['replytoname']);
	}
	$mailer->Subject = $params['subject'];
	$mailer->Body = $params['body'];

	if ($params['html'])
		$mailer->isHTML(true);

	if (qa_opt('smtp_active')) {
		$mailer->isSMTP();
		$mailer->Host = qa_opt('smtp_address');
		$mailer->Port = qa_opt('smtp_port');

		if (qa_opt('smtp_secure')) {
			$mailer->SMTPSecure = qa_opt('smtp_secure');
		} else {
			$mailer->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				),
			);
		}

		if (qa_opt('smtp_authenticate')) {
			$mailer->SMTPAuth = true;
			$mailer->Username = qa_opt('smtp_username');
			$mailer->Password = qa_opt('smtp_password');
		}
	}

	$send_status = $mailer->send();
	if (!$send_status) {
		@error_log('PHP Question2Answer email send error: ' . $mailer->ErrorInfo);
	}
	return $send_status;
}
