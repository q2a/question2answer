<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-not-found.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for page not found (error 404)


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
	
	require_once QA_INCLUDE_DIR.'qa-app-format.php';


	header('HTTP/1.0 404 Not Found');

	qa_set_template('not-found');

	$qa_content=qa_content_prepare();
	$qa_content['error']=qa_lang_html('main/page_not_found');
	$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags());
	
	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/