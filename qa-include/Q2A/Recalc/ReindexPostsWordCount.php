<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Recalc/ReindexContentWordCount.php
	Description: Recalc processing class for the reindex content process.


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

class Q2A_Recalc_ReindexPostsWordCount extends Q2A_Recalc_AbstractStep
{
	public function doStep()
	{
		$wordids = qa_db_words_prepare_for_recounting($this->state->next, 1000);

		if (!count($wordids)) {
			qa_db_tagcount_update(); // this is quick so just do it here
			$this->state->transition('doreindexposts_complete');
			return false;
		}

		$lastwordid = max($wordids);

		qa_db_words_recount($this->state->next, $lastwordid);

		$this->state->next = 1 + $lastwordid;
		$this->state->done += count($wordids);
		return true;
	}

	public function getMessage()
	{
		return $this->progressLang('admin/reindex_posts_wordcounted', $this->state->done, $this->state->length);
	}
}
