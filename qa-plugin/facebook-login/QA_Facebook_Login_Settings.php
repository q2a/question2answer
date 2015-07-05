<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/facebook-login/qa-facebook-login.php
	Description: Login module class for Facebook login plugin


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

class QA_Facebook_Login_Settings extends Q2A_Plugin_Module_Settings
{
	public function getInternalId()
	{
		return 'QA_Facebook_Login_Settings';
	}

	public function getForm(&$qa_content)
	{
		$saved=false;

		if (qa_clicked('facebook_save_button')) {
			qa_opt('facebook_app_id', qa_post_text('facebook_app_id_field'));
			qa_opt('facebook_app_secret', qa_post_text('facebook_app_secret_field'));
			$saved=true;
		}

		$ready=strlen(qa_opt('facebook_app_id')) && strlen(qa_opt('facebook_app_secret'));

		return array(
			'ok' => $saved ? 'Facebook application details saved' : null,

			'fields' => array(
				array(
					'label' => 'Facebook App ID:',
					'value' => qa_html(qa_opt('facebook_app_id')),
					'tags' => 'name="facebook_app_id_field"',
				),

				array(
					'label' => 'Facebook App Secret:',
					'value' => qa_html(qa_opt('facebook_app_secret')),
					'tags' => 'name="facebook_app_secret_field"',
					'error' => $ready ? null : 'To use Facebook Login, please <a href="http://developers.facebook.com/setup/" target="_blank">set up a Facebook application</a>.',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="facebook_save_button"',
				),
			),
		);
	}
}
