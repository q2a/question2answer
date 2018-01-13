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

use Q2A\Http\Router;

class Application
{
	/** @var Container */
	private $container;

	/** @var static */
	protected static $instance;

	public function __construct()
	{
		$this->container = new Container();

		$this->registerCoreBindings();
	}

	/**
	 * Instantiate and fetch the application as a singleton.
	 * @return static
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Register the bindings used by the core.
	 */
	private function registerCoreBindings()
	{
		$this->container->bind('router', new Router());
	}

	/**
	 * Return the container instance.
	 * @return Container
	 */
	public function getContainer()
	{
		return $this->container;
	}
}
