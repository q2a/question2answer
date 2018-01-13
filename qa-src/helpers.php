<?php

use Q2A\App\Application;

if (!function_exists('app')) {
	/**
	 * Helper function to access the application object.
	 * If the $key parameter is null the application instance is returned.
	 * If the $key parameter is set and the $object parameter is null the container is called to resolve the $key.
	 * If the $key and the $object parameters are null the container is called to bind the $object to the $key.
	 * @param mixed $key
	 * @param mixed $object
	 * @return mixed
	 */
	function app($key = null, $object = null)
	{
		if (is_null($key)) {
			return Application::getInstance();
		}

		if (is_null($object)) {
			return Application::getInstance()->getContainer()->resolve($key);
		}

		Application::getInstance()->getContainer()->bind($key, $object);
	}
}
