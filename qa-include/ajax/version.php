<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-ajax-version.php
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

	require_once QA_INCLUDE_DIR.'app/admin.php';
	require_once QA_INCLUDE_DIR.'app/users.php';

	if (qa_get_logged_in_level() < QA_USER_LEVEL_ADMIN) {
		echo "QA_AJAX_RESPONSE\n0\n" . qa_lang_html('admin/no_privileges');
		return;
	}

	$uri = qa_post_text('uri');
	$version = qa_post_text('version');

	$metadataUtil = new Q2A_Util_Metadata();
	$metadata = $metadataUtil->fetchFromUrl($uri);

	if (strlen(@$metadata['version'])) {
		if (strcmp($metadata['version'], $version)) {
			if (qa_qa_version_below(@$metadata['min_q2a'])) {
				$response = strtr(qa_lang_html('admin/version_requires_q2a'), array(
					'^1' => qa_html('v'.$metadata['version']),
					'^2' => qa_html($metadata['min_q2a']),
				));
			}

			elseif (qa_php_version_below(@$metadata['min_php'])) {
				$response = strtr(qa_lang_html('admin/version_requires_php'), array(
					'^1' => qa_html('v'.$metadata['version']),
					'^2' => qa_html($metadata['min_php']),
				));
			}

			else {
				$response = qa_lang_html_sub('admin/version_get_x', qa_html('v'.$metadata['version']));

				if (strlen(@$metadata['uri']))
					$response = '<a href="'.qa_html($metadata['uri']).'" style="color:#d00;">'.$response.'</a>';
			}

		}
		else
			$response = qa_lang_html('admin/version_latest');

	}
	else
		$response = qa_lang_html('admin/version_latest_unknown');


	echo "QA_AJAX_RESPONSE\n1\n".$response;



/*
	Omit PHP closing tag to help avoid accidental output
*/