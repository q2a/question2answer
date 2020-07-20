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

class Mailer extends PHPMailer
{
	public function __construct($params = array())
	{
		parent::__construct();

		self::$validator = 'php';
		$this->CharSet = 'utf-8';

		$this->From = $params['fromemail'];
		$this->Sender = $params['fromemail'];
		$this->FromName = $params['fromname'];
		$this->addAddress($params['toemail'], $params['toname']);
		if (!empty($params['replytoemail'])) {
			$this->addReplyTo($params['replytoemail'], $params['replytoname']);
		}
		$this->Subject = $params['subject'];
		$this->Body = $params['body'];

		if ($params['html']) {
			$this->isHTML(true);
		}

		if (qa_opt('smtp_active')) {
			$this->isSMTP();
			$this->Host = qa_opt('smtp_address');
			$this->Port = qa_opt('smtp_port');

			if (qa_opt('smtp_secure')) {
				$this->SMTPSecure = qa_opt('smtp_secure');
			} else {
				$this->SMTPOptions = array(
					'ssl' => array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true,
					),
				);
			}

			if (qa_opt('smtp_authenticate')) {
				$this->SMTPAuth = true;
				$this->Username = qa_opt('smtp_username');
				$this->Password = qa_opt('smtp_password');
			}
		}
	}
}
