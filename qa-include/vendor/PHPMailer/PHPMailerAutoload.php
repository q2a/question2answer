<?php
/**
 * @deprecated This file is deprecated; please use Q2A built-in functions for sending emails.
 */

if (defined('QA_DEBUG_PERFORMANCE') && QA_DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
}

require_once QA_INCLUDE_DIR . 'vendor/PHPMailer/class.phpmailer.php';
require_once QA_INCLUDE_DIR . 'vendor/PHPMailer/class.smtp.php';
