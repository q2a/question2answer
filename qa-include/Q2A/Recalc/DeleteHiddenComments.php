<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Recalc/DeleteHiddenComments.php
	Description: Recalc processing class for the delete hidden process.


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

class Q2A_Recalc_DeleteHiddenComments extends Q2A_Recalc_AbstractStep
{
	public function doStep()
	{
		$posts = qa_db_posts_get_for_deleting('C', $this->state->next, 1);

		if (!count($posts)) {
			$this->state->transition('dodeletehidden_answers');
			return false;
		}

		require_once QA_INCLUDE_DIR . 'app/posts.php';

		$postid = $posts[0];
		qa_post_delete($postid);

		$this->state->next = 1 + $postid;
		$this->state->done++;
		return true;
	}

	public function getMessage()
	{
		return $this->progressLang('admin/hidden_commenrs_deleted', $this->state->done, $this->state->length);
	}
}
