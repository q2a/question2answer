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

namespace Q2A\Controllers;

use Q2A\Database\DbConnection;
use Q2A\Middleware\BaseMiddleware;

abstract class BaseController
{
	/** @var BaseMiddleware[string] */
	protected $middleware = [];
	protected $db;

	public function __construct(DbConnection $db)
	{
		$this->db = $db;
	}

	/**
	 * Attach a middleware to one action or an array of actions. Use '*' to match all actions.
	 *
	 * @param BaseMiddleware $middleware
	 * @param array|string $actions
	 */
	public function addMiddleware(BaseMiddleware $middleware, $actions = '*')
	{
		if (is_array($actions)) {
			foreach ($actions as $action) {
				$this->addMiddlewareToAction($middleware, $action);
			}
		} else { // If it is a string
			$this->addMiddlewareToAction($middleware, $actions);
		}
	}

	/**
	 * @param BaseMiddleware $middleware
	 * @param string $action
	 */
	private function addMiddlewareToAction(BaseMiddleware $middleware, $action)
	{
		if (!isset($this->middleware[$action])) {
			$this->middleware[$action] = array();
		}

		$this->middleware[$action][] = $middleware;
	}

	/**
	 * Execute the given action with the given parameters on this controller. after running all
	 * middleware for the action. This method is expected to return a qa_content array or throw an
	 * exception.
	 *
	 * @param string $action Action to execute
	 * @param array $parameters Parameters to send to the action
	 *
	 * @return mixed
	 */
	public function executeAction($action, $parameters)
	{
		$this->executeMiddlewareForAction('*');
		$this->executeMiddlewareForAction($action);

		return call_user_func_array(array($this, $action), $parameters);
	}

	/**
	 * @param string $action
	 */
	private function executeMiddlewareForAction($action)
	{
		if (!isset($this->middleware[$action])) {
			return;
		}

		foreach ($this->middleware[$action] as $middleware) {
			$middleware->handle();
		}
	}
}
