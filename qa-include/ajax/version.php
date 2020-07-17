<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

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

require_once QA_INCLUDE_DIR . 'app/admin.php';
require_once QA_INCLUDE_DIR . 'app/users.php';

if (qa_get_logged_in_level() < QA_USER_LEVEL_ADMIN) {
	$response = array(
		'result' => 'error',
		'error' => array(
			'message' => qa_lang_html('admin/no_privileges'),
		),
	);

	echo json_encode($response);
	return;
}

$uri = qa_post_text('uri');
$currentVersion = qa_post_text('version');
$isCore = qa_post_text('isCore') === "true";

if ($isCore) {
	$contents = qa_retrieve_url($uri);

	if (strlen($contents) > 0) {
		if (qa_qa_version_below($contents)) {
			$versionResponse =
				'<a href="https://github.com/q2a/question2answer/releases" style="color:#d00;">' .
				qa_lang_html_sub('admin/version_get_x', qa_html('v' . $contents)) .
				'</a>';
		} else {
			$versionResponse = qa_html($contents); // Output the current version number
		}
	} else {
		$versionResponse = qa_lang_html('admin/version_latest_unknown');
	}
} else {
	$metadataUtil = new \Q2A\Util\Metadata();
	$metadata = $metadataUtil->fetchFromUrl($uri);

	if (strlen(@$metadata['version']) > 0) {
		if (version_compare($currentVersion, $metadata['version']) < 0) {
			if (qa_qa_version_below(@$metadata['min_q2a'])) {
				$versionResponse = strtr(qa_lang_html('admin/version_requires_q2a'), array(
					'^1' => qa_html('v' . $metadata['version']),
					'^2' => qa_html($metadata['min_q2a']),
				));
			} elseif (qa_php_version_below(@$metadata['min_php'])) {
				$versionResponse = strtr(qa_lang_html('admin/version_requires_php'), array(
					'^1' => qa_html('v' . $metadata['version']),
					'^2' => qa_html($metadata['min_php']),
				));
			} else {
				$versionResponse = qa_lang_html_sub('admin/version_get_x', qa_html('v' . $metadata['version']));

				if (strlen(@$metadata['uri'])) {
					$versionResponse = '<a href="' . qa_html($metadata['uri']) . '" style="color:#d00;">' . $versionResponse . '</a>';
				}
			}
		} else {
			$versionResponse = qa_lang_html('admin/version_latest');
		}
	} else {
		$versionResponse = qa_lang_html('admin/version_latest_unknown');
	}
}

$response = array(
	'result' => 'success',
	'html' => $versionResponse,
);

echo json_encode($response);
