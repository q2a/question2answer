<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Recalc/RecalcPointsRecalc.php
	Description: Recalc processing class for the recalc points process.


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

class Q2A_Recalc_RecalcPointsRecalc extends Q2A_Recalc_AbstractStep
{
	public function doStep()
	{
		$default_recalccount = 10;
		$userids = qa_db_users_get_for_recalc_points($this->state->next, $default_recalccount + 1); // get one extra so we know where to start from next
		$gotcount = count($userids);
		$recalccount = min($default_recalccount, $gotcount); // can't recalc more than we got

		if ($recalccount > 0) {
			$lastuserid = $userids[$recalccount - 1];
			qa_db_users_recalc_points($this->state->next, $lastuserid);
			$this->state->done += $recalccount;

		} else {
			$lastuserid = $this->state->next; // for truncation
		}

		if ($gotcount > $recalccount) { // more left to do
			$this->state->next = $userids[$recalccount]; // start next round at first one not recalculated
			return true;
		} else {
			qa_db_truncate_userpoints($lastuserid);
			qa_db_userpointscount_update(); // quick so just do it here
			$this->state->transition('dorecalcpoints_complete');
			return false;
		}
	}

	public function getMessage()
	{
		return $this->progressLang('admin/recalc_points_recalced', $this->state->done, $this->state->length);
	}
}
