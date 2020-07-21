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

class RecountPostsPostCount extends AbstractStep
{
	/**
	 * Perform the recalculation.
	 * @return bool
	 */
	public function doStep()
	{
		qa_db_qcount_update();
		qa_db_acount_update();
		qa_db_ccount_update();
		qa_db_unaqcount_update();
		qa_db_unselqcount_update();

		$this->state->transition('dorecountposts_votecount');
		return false;
	}

	/**
	 * Get the current progress.
	 * @return string
	 */
	public function getMessage()
	{
		return qa_lang('admin/recalc_posts_count');
	}
}
