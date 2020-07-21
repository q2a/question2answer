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

class RecalcCategoriesRecount extends AbstractStep
{
	/**
	 * Perform the recalculation.
	 * @return bool
	 */
	public function doStep()
	{
		$categoryids = qa_db_categories_get_for_recalcs($this->state->next, 10);

		if (empty($categoryids)) {
			$this->state->transition('dorecalccategories_backpaths');
			return false;
		}
		$lastcategoryid = max($categoryids);

		foreach ($categoryids as $categoryid) {
			qa_db_ifcategory_qcount_update($categoryid);
		}

		$this->state->next = 1 + $lastcategoryid;
		$this->state->done += count($categoryids);
		return true;
	}

	/**
	 * Get the current progress.
	 * @return string
	 */
	public function getMessage()
	{
		return $this->progressLang('admin/recalc_categories_recounting', $this->state->done, $this->state->length);
	}
}
