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

class RecalcCategoriesPostUpdate extends AbstractStep
{
	/**
	 * Perform the recalculation.
	 * @return bool
	 */
	public function doStep()
	{
		$postids = qa_db_posts_get_for_recategorizing($this->state->next, 100);

		if (empty($postids)) {
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

	/**
	 * Get the current progress.
	 * @return string
	 */
	public function getMessage()
	{
		return $this->progressLang('admin/recalc_categories_updated', $this->state->done, $this->state->length);
	}
}
