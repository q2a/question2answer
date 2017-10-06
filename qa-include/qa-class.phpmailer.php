<?php
/**
 * @deprecated This file is deprecated; please use Q2A built-in functions for sending emails.
 */

if (!defined('QA_VERSION')) {
	header('Location: ../');
	exit;
}

if (defined('QA_DEBUG_PERFORMANCE') && QA_DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
}

require_once 'vendor/PHPMailer/PHPMailerAutoload.php';
