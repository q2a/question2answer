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

class Router
{
	/** @var Route[] */
	protected $routes;

	/** @var array */
	private $paramsConverterKeys;

	/** @var array */
	private $paramsConverterValues;

	/** @var string */
	private $httpMethod;

	public function __construct($routeData = array())
	{
		$this->routes = $routeData;

		$paramsConverter = array(
			'{str}' => '([^/]+)',
			'{int}' => '([0-9]+)',
		);
		$this->paramsConverterKeys = array_keys($paramsConverter);
		$this->paramsConverterValues = array_values($paramsConverter);

		$this->httpMethod = strtoupper($_SERVER['REQUEST_METHOD']);
	}

	public function addRoute($id, $httpMethod, $routePath, $class, $func)
	{
		$this->routes[] = new Route($id, $httpMethod, $routePath, $class, $func);
	}

	public function match($request)
	{
		foreach ($this->routes as $route) {
			if ($route->getHttpMethod() !== $this->httpMethod) {
				continue;
			}

			$pathRegex = $this->buildPathRegex($route->getRoutePath());
			if (preg_match($pathRegex, $request, $matches)) {
				$route->bindParameters(array_slice($matches, 1));

				return $route;
			}
		}

		return null;
	}

	/**
	 * @param $routePath
	 *
	 * @return string
	 */
	private function buildPathRegex($routePath)
	{
		return '#^' . str_replace($this->paramsConverterKeys, $this->paramsConverterValues, $routePath) . '$#';
	}

	/**
	 * Return the HTTP method of the current request.
	 *
	 * @return string
	 */
	public function getHttpMethod()
	{
		return $this->httpMethod;
	}
}
