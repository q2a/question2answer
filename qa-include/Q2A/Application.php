<?php

/*
  Question2Answer by Gideon Greenspan and contributors
  http://www.question2answer.org/

  File: qa-include/Q2A/Application.php
  Description: Contains configuration data of Q2A


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

class Q2A_Application {

	private $config;
	private static $instance;

	/**
	 * Return the singleton instance of the Q2A application
	 * @return The Q2A application instance
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Creates a new instance of this class setting some default values
	 */
	private function __construct() {
		$this->config = new Q2A_Util_Set();

		$this->config->set('safe_mode', false);
	}

	/**
	 * Return a value from the configuration set using a default value, if provided
	 * @param mixed $key The key that will be used to search the configuration set for
	 * @param mixed $defaultValue A default value returned if the requested key does not exist in
	 * the configuration set
	 * @return mixed The value corresponding to the requested key or $defaultValue if the key
	 * does not exist. If the key does not exist and the default value is not set then a null
	 * value is returned
	 */
	public function getConfig($key, $defaultValue = null) {
		return $this->config->get($key, $defaultValue);
	}

	/**
	 * Sets a key or value to the configuration set. If the value already exists, it is replaced
	 * @param mixed $key The key that will be used to search the configuration set for
	 * @param mixed $key The value for the key
	 */
	public function setConfig($key, $value) {
		$this->config->set($key, $value);
	}

}
