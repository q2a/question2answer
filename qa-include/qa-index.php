<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: The Grand Central of Q2A - most requests come through here


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

// Try our best to set base path here just in case it wasn't set in index.php (pre version 1.0.1)

if (!defined('QA_BASE_DIR')) {
	define('QA_BASE_DIR', dirname(empty($_SERVER['SCRIPT_FILENAME']) ? dirname(__FILE__) : $_SERVER['SCRIPT_FILENAME']) . '/');
}


// If this is an special non-page request, branch off here

if (isset($_POST['qa']) && $_POST['qa'] == 'ajax') {
	require 'qa-ajax.php';
}

elseif (isset($_GET['qa']) && $_GET['qa'] == 'image') {
	require 'qa-image.php';
}

elseif (isset($_GET['qa']) && $_GET['qa'] == 'blob') {
	require 'qa-blob.php';
}

else {
	// Otherwise, load the Q2A base file which sets up a bunch of crucial stuff
	$qa_autoconnect = false;
	require 'qa-base.php';

	/**
	 * Determine the request and root of the installation, and the requested start position used by many pages.
	 *
	 * Apache and Nginx behave slightly differently:
	 *   Apache qa-rewrite unescapes characters, converts `+` to ` `, cuts off at `#` or `&`
	 *   Nginx qa-rewrite unescapes characters, retains `+`, contains true path
	 */
	function qa_index_set_request()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$relativedepth = 0;

		if (isset($_GET['qa-rewrite'])) { // URLs rewritten by .htaccess or Nginx
			$urlformat = QA_URL_FORMAT_NEAT;
			$qa_rewrite = strtr(qa_gpc_to_string($_GET['qa-rewrite']), '+', ' '); // strtr required by Nginx
			$requestparts = explode('/', $qa_rewrite);
			unset($_GET['qa-rewrite']);

			if (!empty($_SERVER['REQUEST_URI'])) { // workaround for the fact that Apache unescapes characters while rewriting
				$origpath = $_SERVER['REQUEST_URI'];
				$_GET = array();

				$questionpos = strpos($origpath, '?');
				if (is_numeric($questionpos)) {
					$params = explode('&', substr($origpath, $questionpos + 1));

					foreach ($params as $param) {
						if (preg_match('/^([^\=]*)(\=(.*))?$/', $param, $matches)) {
							$argument = strtr(urldecode($matches[1]), '.', '_'); // simulate PHP's $_GET behavior
							$_GET[$argument] = qa_string_to_gpc(urldecode(@$matches[3]));
						}
					}

					$origpath = substr($origpath, 0, $questionpos);
				}

				// Generally we assume that $_GET['qa-rewrite'] has the right path depth, but this won't be the case if there's
				// a & or # somewhere in the middle of the path, due to Apache unescaping. So we make a special case for that.
				// If 'REQUEST_URI' and 'qa-rewrite' already match (as on Nginx), we can skip this.
				$normalizedpath = urldecode($origpath);
				if (substr($normalizedpath, -strlen($qa_rewrite)) !== $qa_rewrite) {
					$keepparts = count($requestparts);
					$requestparts = explode('/', urldecode($origpath)); // new request calculated from $_SERVER['REQUEST_URI']

					// loop forwards so we capture all parts
					for ($part = 0, $max = count($requestparts); $part < $max; $part++) {
						if (is_numeric(strpos($requestparts[$part], '&')) || is_numeric(strpos($requestparts[$part], '#'))) {
							$keepparts += count($requestparts) - $part - 1; // this is how many parts remain
							break;
						}
					}

					$requestparts = array_slice($requestparts, -$keepparts); // remove any irrelevant parts from the beginning
				}
			}

			$relativedepth = count($requestparts);
		} elseif (isset($_GET['qa'])) {
			if (strpos($_GET['qa'], '/') === false) {
				$urlformat = (empty($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/index.php') !== false)
					? QA_URL_FORMAT_SAFEST : QA_URL_FORMAT_PARAMS;
				$requestparts = array(qa_gpc_to_string($_GET['qa']));

				for ($part = 1; $part < 10; $part++) {
					if (isset($_GET['qa_' . $part])) {
						$requestparts[] = qa_gpc_to_string($_GET['qa_' . $part]);
						unset($_GET['qa_' . $part]);
					}
				}
			} else {
				$urlformat = QA_URL_FORMAT_PARAM;
				$requestparts = explode('/', qa_gpc_to_string($_GET['qa']));
			}

			unset($_GET['qa']);
		} else {
			$normalizedpath = strtr($_SERVER['PHP_SELF'], '+', ' '); // seems necessary, and plus does not work with this scheme
			$indexpath = '/index.php/';
			$indexpos = strpos($normalizedpath, $indexpath);

			if (!empty($_SERVER['REQUEST_URI'])) { // workaround for the fact that Apache unescapes characters
				$origpath = $_SERVER['REQUEST_URI'];
				$questionpos = strpos($origpath, '?');
				if ($questionpos !== false) {
					$origpath = substr($origpath, 0, $questionpos);
				}

				$normalizedpath = urldecode($origpath);
				$indexpos = strpos($normalizedpath, $indexpath);
			}

			if (is_numeric($indexpos)) {
				$urlformat = QA_URL_FORMAT_INDEX;
				$requestparts = explode('/', substr($normalizedpath, $indexpos + strlen($indexpath)));
				$relativedepth = 1 + count($requestparts);
			} else {
				$urlformat = null; // at home page so can't identify path type
				$requestparts = array();
			}
		}

		foreach ($requestparts as $part => $requestpart) { // remove any blank parts
			if (!strlen($requestpart))
				unset($requestparts[$part]);
		}

		reset($requestparts);
		$key = key($requestparts);

		$requestkey = isset($requestparts[$key]) ? $requestparts[$key] : '';
		$replacement = array_search($requestkey, qa_get_request_map());
		if ($replacement !== false)
			$requestparts[$key] = $replacement;

		qa_set_request(
			implode('/', $requestparts),
			($relativedepth > 1 ? str_repeat('../', $relativedepth - 1) : './'),
			$urlformat
		);
	}

	qa_index_set_request();


	// Branch off to appropriate file for further handling

	$requestlower = strtolower(qa_request());

	if ($requestlower == 'install') {
		require QA_INCLUDE_DIR . 'qa-install.php';
	} elseif ($requestlower == 'url/test/' . QA_URL_TEST_STRING) {
		require QA_INCLUDE_DIR . 'qa-url-test.php';
	} else {
		// enable gzip compression for output (needs to come early)
		qa_initialize_buffering($requestlower);

		if (substr($requestlower, 0, 5) == 'feed/') {
			require QA_INCLUDE_DIR . 'qa-feed.php';
		} else {
			require QA_INCLUDE_DIR . 'qa-page.php';
		}
	}
}

qa_report_process_stage('shutdown');
