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

abstract class BaseController
{
	// TODO: constructor taking Database class parameter

	/**
	 * Execute the given action with the given parameters on this controller.
	 *
	 * @param string $action Action to execute
	 * @param array $parameters Parameters to send to the action
	 *
	 * @return mixed
	 */
	public function executeAction($action, $parameters)
	{
		return call_user_func_array(array($this, $action), $parameters);
	}
}
