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
	private $paramsConverter;

	/** @var string */
	private $httpMethod;

	public function __construct($routeData = array())
	{
		$this->routes = $routeData;

		$this->paramsConverter = array(
			'{str}' => '([^/]+)',
			'{int}' => '([0-9]+)',
		);

		$this->httpMethod = strtoupper($_SERVER['REQUEST_METHOD']);
	}

	public function addRoute($id, $httpMethod, $routePath, $class, $func)
	{
		$this->routes[] = new Route($id, $httpMethod, $routePath, $class, $func);
	}

	/**
	 * Return the route definition that matches the given request. If none is found then null is
	 * returned.
	 *
	 * @param string $request Request that will be looked for a match
	 *
	 * @return Route|null
	 */
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
	 * Build the final regular expression to match the request. This method replaces all the
	 * parameters' placeholders with the given regular expression.
	 *
	 * @param string $routePath Route that might have placeholders
	 *
	 * @return string
	 */
	private function buildPathRegex($routePath)
	{
		return '#^' . strtr($routePath, $this->paramsConverter) . '$#';
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
