<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/example-page/qa-example-page.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Page module class for example page plugin


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

	class qa_example_page {
		
		var $directory;
		var $urltoroot;
		

		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}

		
		function suggest_requests() // for display in admin interface
		{	
			return array(
				array(
					'title' => 'Example',
					'request' => 'example-plugin-page',
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		
		function match_request($request)
		{
			if ($request=='example-plugin-page')
				return true;

			return false;
		}

		
		function process_request($request)
		{
			$qa_content=qa_content_prepare();

			$qa_content['title']=qa_lang_html('example_page/page_title');
			$qa_content['error']='An example error';
			$qa_content['custom']='Some <b>custom html</b>';

			$qa_content['form']=array(
				'tags' => 'method="post" action="'.qa_self_html().'"',
				
				'style' => 'wide',
				
				'ok' => qa_post_text('okthen') ? 'You clicked OK then!' : null,
				
				'title' => 'Form title',
				
				'fields' => array(
					'request' => array(
						'label' => 'The request',
						'tags' => 'name="request"',
						'value' => qa_html($request),
						'error' => qa_html('Another error'),
					),
					
				),
				
				'buttons' => array(
					'ok' => array(
						'tags' => 'name="okthen"',
						'label' => 'OK then',
						'value' => '1',
					),
				),
				
				'hidden' => array(
					'hiddenfield' => '1',
				),
			);

			$qa_content['custom_2']='<p><br>More <i>custom html</i></p>';
			
			return $qa_content;
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/