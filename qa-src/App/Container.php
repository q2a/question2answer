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

namespace Q2A\App;

use Q2A\Exceptions\FatalErrorException;

class Container
{
	/** @var array */
	protected $bindings = array();

	/**
	 * Bind an object to a key.
	 * @param mixed $key The key to bind the object to
	 * @param mixed $object The object to bind to the key
	 */
	public function bind($key, $object)
	{
		$this->bindings[$key] = $object;
	}

	/**
	 * Return an object bound to the given key. If the key is not found an exception is thrown.
	 * @param mixed $key The key to look for
	 * @return mixed
	 */
	public function resolve($key)
	{
		if (isset($this->bindings[$key])) {
			return $this->bindings[$key];
		}

		throw new FatalErrorException(sprintf('Key "%s" not bound in container', $key));
	}
}
