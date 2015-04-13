<?php

/*
  Question2Answer by Gideon Greenspan and contributors
  http://www.question2answer.org/

  File: qa-include/Q2A/Util/Set.php
  Description: Simple and efficient set implementation


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

class Q2A_Util_Set {

	private $data = array();

	/**
	 * Return a value from the set using a default value, if provided
	 * @param mixed $key The key that will be used to search the set for
	 * @param mixed $defaultValue A default value returned if the requested key does not exist in
	 * the set
	 * @return mixed The value corresponding to the requested key or $defaultValue if the key
	 * does not exist. If the key does not exist and the default value is not set then a null
	 * value is returned
	 */
	public function get($key, $defaultValue = null) {
		if (isset($this->data[$key]))
			return $this->data[$key];
		else
			return isset($defaultValue) ? $defaultValue : null;
	}

	/**
	 * Sets a key or value to the set. If the value already exists, it is replaced
	 * @param mixed $key The key that will be used to search the sety for
	 * @param mixed $value The value for the key
	 */
	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	/**
	 * Checks if a key is present in the set
	 * @param mixed $key The key that will be used to search the sety for
	 * @return bool True if the key exists or false if the key does not exist in the set
	 */
	public function contains($key) {
		return isset($this->data[$key]);
	}

}
