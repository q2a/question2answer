<?php
/**
 * Custom PHPMailer autoloader.
 * @param string $classname The name of the class to load
 */
function PHPMailerAutoload($classname)
{
	if (strpos($classname, 'PHPMailer\\PHPMailer\\') !== 0)
		return;

	$filename = __DIR__ . '/' . str_replace('PHPMailer\\PHPMailer\\', '', $classname) . '.php';
	if (is_readable($filename)) {
		require $filename;
	}
}

spl_autoload_register('PHPMailerAutoload', true, true);
