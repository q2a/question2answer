<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Email notifications

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

namespace Q2A\Notifications;

class Status
{
	protected static $notifications_suspended = 0;

	/**
	 * Suspend the sending of all notifications if $suspend is true, otherwise
	 * reinstate it. A counter is kept to allow multiple calls.
	 * @param bool $suspend
	 */
	public static function suspend($suspend = true)
	{
		self::$notifications_suspended += ($suspend ? 1 : -1);
	}

	public static function isSuspended()
	{
		return self::$notifications_suspended > 0;
	}
}
