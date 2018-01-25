<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Util/Usage.php
	Description: Debugging stuff, currently used for tracking resource usage


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


if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


abstract class Q2A_Recalc_AbstractStep
{
	protected $state;

	public function __construct(Q2A_Recalc_State $state)
	{
		$this->state = $state;
	}

	abstract public function doStep();

	static public function factory(Q2A_Recalc_State $state)
	{
		$class = $state->getOperationClass();
		if (class_exists($class)) {
			return new $class($state);
		}
		return null;
	}
}
