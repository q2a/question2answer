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

use Q2A\Database\DbConnection;
use Q2A\Database\DbSelect;
use Q2A\Exceptions\FatalErrorException;
use Q2A\Http\Router;
use Q2A\Util\Set;

class Application
{
	/** @var Set */
	protected $services;

	/** @var Set */
	protected $dataStore;

	/** @var static */
	protected static $instance;

	protected function __construct()
	{
		$this->services = new Set();
		$this->dataStore = new Set();
		$this->registerCoreServices();
	}

	/**
	 * Instantiate and fetch the application as a singleton.
	 * @return static
	 */
	public static function getInstance()
	{
		if (static::$instance === null) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Return the specified service.
	 * @throws FatalErrorException
	 * @param string $key The key to look for
	 * @return mixed
	 */
	public function getService($key)
	{
		$obj = $this->services->get($key);
		if ($obj === null) {
			throw new FatalErrorException(sprintf('Key "%s" not found in container', $key));
		}

		return $obj;
	}

	/**
	 * Adds a new service to the container.
	 * @param string $key The key to look for
	 * @param mixed $value The object to add
	 * @return void
	 */
	public function registerService($key, $value)
	{
		$this->services->set($key, $value);
	}

	/**
	 * Retrieve data from the global storage.
	 */
	public function getData($key)
	{
		return $this->dataStore->get($key);
	}

	/**
	 * Store some data in the global storage.
	 */
	public function setData($key, $value)
	{
		return $this->dataStore->set($key, $value);
	}

	/**
	 * Register the services used by the core.
	 */
	private function registerCoreServices()
	{
		$db = new DbConnection();
		$this->services->set('router', new Router());
		$this->services->set('database', $db);
		$this->services->set('dbselect', new DbSelect($db));
	}
}
