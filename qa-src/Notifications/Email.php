<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Wrapper class for sending email notifications to users

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

namespace Q2A\Notifications;

use PHPMailer;
use Q2A\Exceptions\FatalErrorException;
use Q2A\Notifications\Mailer;

class Email
{
	private $email;
	private $handle;

	private function __construct($email, $handle)
	{
		require_once QA_INCLUDE_DIR . 'db/selects.php';		//required for qa_db_select_with_pending()
		require_once QA_INCLUDE_DIR . 'app/options.php';	//required for qa_opt()

		$this->email = $email;
		$this->handle = $handle;
	}

	/**
	 * Factory method.
	 * @param type $userid
	 * @param type $email
	 * @param type $handle
	 * @return \self
	 * @throws FatalErrorException
	 */
	public static function createByUserID($userid, $email = '', $handle = '')
	{
		if (!(int)$userid) {
			throw new FatalErrorException('User ID not specified in Notifications/Email/CreateByUserID.');
		}
		$needemail = !PHPMailer::validateAddress($email); // take from user if invalid
		$needhandle = empty($handle);

		if (QA_FINAL_EXTERNAL_USERS) {
			if ($needhandle) {
				$handles = qa_get_public_from_userids(array($userid));
				$handle = isset($handles[$userid]) ? $handles[$userid] : '';
			}

			if ($needemail) {
				$email = qa_get_user_email($userid);
			}
		} elseif ($needemail || $needhandle) {
			$useraccount = qa_db_select_with_pending(
				array(
					'columns' => array('email', 'handle'),
					'source' => '^users WHERE userid = #',
					'arguments' => array($userid),
					'single' => true,
				)
			);

			if ($needhandle) {
				$handle = $useraccount['handle'];
			}

			if ($needemail) {
				$email = $useraccount['email'];
			}
		}

		return new self($email, $handle);
	}

	/**
	 * Factory method.
	 * @param type $email
	 * @param type $handle
	 * @return \self
	 */
	public static function createByEmailAddress($email, $handle = '')
	{
		return new self($email, $handle);
	}

	public function sendMessage($subject, $body, $subs, $html = false)
	{
		$fullsubs = $this->buildSubs($subs);
		$bodyPrefix = (empty($this->handle) ? '' : qa_lang_sub('emails/to_handle_prefix', $this->handle));

		if (PHPMailer::validateAddress($this->email)) {
			$mailer = new Mailer(array(
				'fromemail' => qa_opt('from_email'),
				'fromname' => qa_opt('site_title'),
				'toemail' => $this->email,
				'toname' => $this->handle,
				'subject' => strtr($subject, $fullsubs),
				'body' => $bodyPrefix . strtr($body, $fullsubs),
				'html' => $html,
			));

			$send_status = $mailer->send();
			if (!$send_status) {
				error_log('PHP Question2Answer email send error: ' . $mailer->ErrorInfo);
			}
			return $send_status;
		}
		return false;
	}

	protected function buildSubs($subs)
	{
		$addsubs = array(
			'^site_title' => qa_opt('site_title'),
			'^site_url' => qa_opt('site_url'),
			'^handle' => $this->handle,
			'^email' => $this->email,
			'^open' => "\n",
			'^close' => "\n"
		);
		return array_merge($subs, $addsubs);
	}
}
