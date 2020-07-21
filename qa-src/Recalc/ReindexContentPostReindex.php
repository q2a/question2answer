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

class ReindexContentPostReindex extends AbstractStep
{
	/**
	 * Perform the recalculation.
	 * @return bool
	 */
	public function doStep()
	{
		$posts = qa_db_posts_get_for_reindexing($this->state->next, 10);

		if (empty($posts)) {
			qa_db_truncate_indexes($this->state->next);
			$this->state->transition('doreindexposts_wordcount');
			return false;
		}

		require_once QA_INCLUDE_DIR . 'app/format.php';

		$lastpostid = max(array_keys($posts));

		qa_db_prepare_for_reindexing($this->state->next, $lastpostid);
		qa_suspend_update_counts();

		foreach ($posts as $postid => $post) {
			qa_post_unindex($postid);
			qa_post_index(
				$postid,
				$post['type'],
				$post['questionid'],
				$post['parentid'],
				$post['title'],
				$post['content'],
				$post['format'],
				qa_viewer_text($post['content'], $post['format']),
				$post['tags'],
				$post['categoryid']
			);
		}

		$this->state->next = 1 + $lastpostid;
		$this->state->done += count($posts);
		return true;
	}

	/**
	 * Get the current progress.
	 * @return string
	 */
	public function getMessage()
	{
		return $this->progressLang('admin/reindex_posts_reindexed', $this->state->done, $this->state->length);
	}
}
