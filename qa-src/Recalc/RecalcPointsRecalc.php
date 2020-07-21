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

namespace Q2A\Recalc;

class RecalcPointsRecalc extends AbstractStep
{
	/**
	 * Perform the recalculation.
	 * @return bool
	 */
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

	/**
	 * Get the current progress.
	 * @return string
	 */
	public function getMessage()
	{
		return $this->progressLang('admin/recalc_points_recalced', $this->state->done, $this->state->length);
	}
}
