<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/basic-adsense/qa-basic-adsense.php
	Description: Widget module class for AdSense widget plugin


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

class QA_Recaptcha_Settings extends Q2A_Plugin_Module_Settings
{
	public function getInternalId()
	{
		return 'QA_Recaptcha_Settings';
	}

	public function getForm(&$qa_content)
	{
		$saved = false;

		if (qa_clicked('recaptcha_save_button')) {
			qa_opt('recaptcha_public_key', qa_post_text('recaptcha_public_key_field'));
			qa_opt('recaptcha_private_key', qa_post_text('recaptcha_private_key_field'));

			$saved = true;
		}

		$pub = trim(qa_opt('recaptcha_public_key'));
		$pri = trim(qa_opt('recaptcha_private_key'));

		$error = null;
		if (!strlen($pub) || !strlen($pri)) {
			require_once $this->getPluginDirectory() .'recaptchalib.php';
			$error = 'To use reCAPTCHA, you must <a href="'.qa_html(ReCaptcha::getSignupUrl()).'" target="_blank">sign up</a> to get these keys.';
		}

		$form = array(
			'ok' => $saved ? 'reCAPTCHA settings saved' : null,

			'fields' => array(
				'public' => array(
					'label' => 'reCAPTCHA public key:',
					'value' => $pub,
					'tags' => 'name="recaptcha_public_key_field"',
				),

				'private' => array(
					'label' => 'reCAPTCHA private key:',
					'value' => $pri,
					'tags' => 'name="recaptcha_private_key_field"',
					'error' => $error,
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="recaptcha_save_button"',
				),
			),
		);

		return $form;
	}
}
