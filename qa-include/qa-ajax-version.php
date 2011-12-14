<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-version.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Server-side response to Ajax version check requests


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

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';

	
	$uri=qa_post_text('uri');
	$versionkey=qa_post_text('versionkey');
	$urikey=qa_post_text('urikey');
	$version=qa_post_text('version');
	
	$metadata=qa_admin_addon_metadata(qa_retrieve_url($uri), array(
		'version' => $versionkey,
		'uri' => $urikey,
	));
	
	if (strlen(@$metadata['version'])) {
		if (strcmp($metadata['version'], $version)) {
			$response=qa_lang_html_sub('admin/version_get_x', qa_html('v'.$metadata['version']));
			
			if (strlen(@$metadata['uri']))
				$response='<A HREF="'.qa_html($metadata['uri']).'" STYLE="color:#d00;">'.$response.'</A>';
				
		} else
			$response=qa_lang_html('admin/version_latest');
	
	} else
		$response=qa_lang_html('admin/version_latest_unknown');
	

	echo "QA_AJAX_RESPONSE\n1\n".$response;
	


/*
	Omit PHP closing tag to help avoid accidental output
*/