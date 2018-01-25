<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Recalc/RefillEventsQCount.php
	Description: Recalc processing class for the refill events process.


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

class Q2A_Recalc_RefillEventsQCount extends Q2A_Recalc_AbstractStep
{
	public function doStep()
	{
		qa_db_qcount_update();
		$this->state->transition('dorefillevents_refill');
		return false;
	}

	public function getMessage()
	{
		return qa_lang('admin/recalc_posts_count');
	}
}
