<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Recalc/RecalcCategoriesPostUpdate.php
	Description: Recalc processing class for the recalc categories process.


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

class Q2A_Recalc_RecalcCategoriesPostUpdate extends Q2A_Recalc_AbstractStep
{
	public function doStep()
	{
		$postids = qa_db_posts_get_for_recategorizing($this->state->next, 100);

		if (!count($postids)) {
			$this->state->transition('dorecalccategories_recount');
			return false;
		}

		$lastpostid = max($postids);

		qa_db_posts_recalc_categoryid($this->state->next, $lastpostid);
		qa_db_posts_calc_category_path($this->state->next, $lastpostid);

		$this->state->next = 1 + $lastpostid;
		$this->state->done += count($postids);
		return true;
	}

	public function getMessage()
	{
		return $this->progressLang('admin/recalc_categories_updated', $this->state->done, $this->state->length);
	}
}
