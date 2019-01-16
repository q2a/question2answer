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

namespace Q2A\Http;

class Route
{
	/** @var string */
	private $httpMethod;

	/** @var string */
	private $routePath;

	/** @var string */
	private $controller;

	/** @var string */
	private $action;

	/** @var array */
	private $options;

	/** @var array */
	private $parameters = [];

	public function __construct($httpMethod, $routePath, $controller = '', $action = '', array $options = [])
	{
		$this->httpMethod = strtoupper($httpMethod);
		$this->routePath = $routePath;
		$this->controller = $controller;
		$this->action = $action;
		$this->options = $options;
	}

	/**
	 * @return string
	 */
	public function getHttpMethod()
	{
		return $this->httpMethod;
	}

	/**
	 * @return string
	 */
	public function getRoutePath()
	{
		return $this->routePath;
	}

	/**
	 * @return string
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @return string
	 */
	public function getOption($name)
	{
		return isset($this->options[$name]) ? $this->options[$name] : null;
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Bind actual request parameters to the route, replacing any existing ones.
	 *
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
}
