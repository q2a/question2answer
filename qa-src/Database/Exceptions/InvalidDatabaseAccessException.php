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

namespace Q2A\Database\Exceptions;

use Q2A\Exceptions\FatalErrorException;

class InvalidDatabaseAccessException extends FatalErrorException
{
	/**
	 * InvalidDatabaseAccessException constructor.
	 *
	 * @param string $message
	 */
	public function __construct($message = 'It appears that a plugin is trying to access the database, but this is not allowed until Q2A initialization is complete')
	{
		parent::__construct($message);
	}
}
