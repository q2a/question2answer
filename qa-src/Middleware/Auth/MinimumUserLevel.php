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

namespace Q2A\Middleware\Auth;

use Q2A\Auth\NoPermissionException;
use Q2A\Middleware\BaseMiddleware;

class MinimumUserLevel extends BaseMiddleware
{
	private $minimumUserLevel;

	/**
	 * MinimumUserLevel constructor.
	 *
	 * @param int $minimumUserLevel Minimum user level allowed to perform the action
	 */
	public function __construct($minimumUserLevel)
	{
		$this->minimumUserLevel = $minimumUserLevel;
	}

	/**
	 * Throw an exception if the current configuration is set to external users.
	 *
	 * @throws NoPermissionException
	 */
	public function handle()
	{
		if (qa_get_logged_in_level() < $this->minimumUserLevel) {
			throw new NoPermissionException();
		}
	}
}
