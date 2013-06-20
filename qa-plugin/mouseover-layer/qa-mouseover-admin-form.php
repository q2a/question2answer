<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/mouseover-layer/qa-mouseover-admin-form.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Generic module class for mouseover layer plugin to provide admin form and default option


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

	class qa_mouseover_admin_form {
		
		function option_default($option)
		{
			if ($option=='mouseover_content_max_len')
				return 480;
		}
	
	
		function admin_form(&$qa_content)
		{
			$saved=false;
			
			if (qa_clicked('mouseover_save_button')) {
				qa_opt('mouseover_content_on', (int)qa_post_text('mouseover_content_on_field'));
				qa_opt('mouseover_content_max_len', (int)qa_post_text('mouseover_content_max_len_field'));
				$saved=true;
			}
			
			qa_set_display_rules($qa_content, array(
				'mouseover_content_max_len_display' => 'mouseover_content_on_field',
			));
			
			return array(
				'ok' => $saved ? 'Mouseover settings saved' : null,
				
				'fields' => array(
					array(
						'label' => 'Show content preview on mouseover in question lists',
						'type' => 'checkbox',
						'value' => qa_opt('mouseover_content_on'),
						'tags' => 'name="mouseover_content_on_field" id="mouseover_content_on_field"',
					),
					
					array(
						'id' => 'mouseover_content_max_len_display',
						'label' => 'Maximum length of preview:',
						'suffix' => 'characters',
						'type' => 'number',
						'value' => (int)qa_opt('mouseover_content_max_len'),
						'tags' => 'name="mouseover_content_max_len_field"',
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'name="mouseover_save_button"',
					),
				),
			);
		}
		
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/