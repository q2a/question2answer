<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

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

namespace Q2A\Util;

/**
 * Simple object store.
 */
class Set
{
	/** @var array */
	protected $items = [];

	/**
	 * Bind an object to a key.
	 * @param string $key The key to bind the object to
	 * @param mixed $object The object to bind to the key
	 */
	public function set($key, $object)
	{
		$this->items[$key] = $object;
	}

	/**
	 * Return an object assigned to the given key, otherwise null.
	 * @param string $key The key to look for
	 * @return mixed
	 */
	public function get($key)
	{
		if (isset($this->items[$key])) {
			return $this->items[$key];
		}

		return null;
	}
}
