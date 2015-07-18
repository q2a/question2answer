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

class QA_Basic_Adsense_Settings extends Q2A_Plugin_Module_Settings
{
	public function getInternalId()
	{
		return 'QA_Basic_Adsense_Settings';
	}

	public function getForm(&$qa_content)
	{
		$saved=false;

		if (qa_clicked('adsense_save_button')) {
			$trimchars="=;\"\' \t\r\n"; // prevent common errors by copying and pasting from Javascript
			qa_opt('adsense_publisher_id', trim(qa_post_text('adsense_publisher_id_field'), $trimchars));
			$saved=true;
		}

		return array(
			'ok' => $saved ? 'AdSense settings saved' : null,

			'fields' => array(
				array(
					'label' => 'AdSense Publisher ID:',
					'value' => qa_html(qa_opt('adsense_publisher_id')),
					'tags' => 'name="adsense_publisher_id_field"',
					'note' => 'Example: <i>pub-1234567890123456</i>',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="adsense_save_button"',
				),
			),
		);
	}
}
