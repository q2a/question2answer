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

class Q2A_Recalc_RecalcPointsUserCount extends Q2A_Recalc_AbstractStep
{
	public function doStep()
	{
		qa_db_userpointscount_update(); // for progress update - not necessarily accurate
		qa_db_uapprovecount_update(); // needs to be somewhere and this is the most appropriate place
		$this->state->transition('dorecalcpoints_recalc');
		return false;
	}

}
