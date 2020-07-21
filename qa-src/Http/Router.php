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

use Q2A\Http\Exceptions\MethodNotAllowedException;

class Router
{
	/** @var Route[] */
	protected $routes = [];

	/** @var array */
	private $paramsConverter = [
		'{str}' => '([^/]+)',
		'{int}' => '([0-9]+)',
	];

	/** @var string */
	private $httpMethod = '';

	public function __construct()
	{
		// implicity support HEAD requests (PHP takes care of removing the response body for us)
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		$this->httpMethod = ($method === 'HEAD' ? 'GET' : $method);
	}

	/**
	 * Add a new URI handler to the router.
	 * @param string $httpMethod
	 * @param string $routePath
	 * @param string $class The controller to use.
	 * @param string $func The class method to call.
	 * @param array $options Extra parameters e.g. Q2A template the page should use.
	 */
	public function addRoute($httpMethod, $routePath, $class, $func, array $options = [])
	{
		$this->routes[] = new Route($httpMethod, $routePath, $class, $func, $options);
	}

	/**
	 * Return the route definition that matches the given request. If none is found then null is
	 * returned.
	 * @throws MethodNotAllowedException
	 * @param string $request Request that will be looked for a match
	 * @return Route|null
	 */
	public function match($request)
	{
		$matchedRoute = false;

		foreach ($this->routes as $route) {
			$pathRegex = $this->buildPathRegex($route->getRoutePath());
			if (preg_match($pathRegex, $request, $matches)) {
				$matchedRoute = true;
				if ($route->getHttpMethod() === $this->httpMethod) {
					$route->setParameters(array_slice($matches, 1));
					return $route;
				}
			}
		}

		// we matched a route but not the HTTP method
		if ($matchedRoute) {
			throw new MethodNotAllowedException;
		}

		return null;
	}

	/**
	 * Build the final regular expression to match the request. This method replaces all the
	 * parameters' placeholders with the given regular expression.
	 * @param string $routePath Route that might have placeholders
	 * @return string
	 */
	private function buildPathRegex($routePath)
	{
		return '#^' . strtr($routePath, $this->paramsConverter) . '$#';
	}

	/**
	 * Return the HTTP method of the current request.
	 * @return string
	 */
	public function getHttpMethod()
	{
		return $this->httpMethod;
	}
}
