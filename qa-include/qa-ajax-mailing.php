<?php
/**
 * @deprecated This file is deprecated from Q2A 1.7; use the below file instead.
 */

if (!defined('QA_VERSION')) {
	header('Location: ../');
	exit;
}

if (defined('QA_DEBUG_PERFORMANCE') && QA_DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
}

require_once QA_INCLUDE_DIR.'ajax/mailing.php';
