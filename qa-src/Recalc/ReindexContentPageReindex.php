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

class ReindexContentPageReindex extends AbstractStep
{
	/**
	 * Perform the recalculation.
	 * @return bool
	 */
	public function doStep()
	{
		$pages = qa_db_pages_get_for_reindexing($this->state->next, 10);

		if (empty($pages)) {
			$this->state->transition('doreindexcontent_postcount');
			return false;
		}

		require_once QA_INCLUDE_DIR . 'app/format.php';

		$lastpageid = max(array_keys($pages));

		foreach ($pages as $pageid => $page) {
			if (!($page['flags'] & QA_PAGE_FLAGS_EXTERNAL)) {
				$searchmodules_u = qa_load_modules_with('search', 'unindex_page');
				foreach ($searchmodules_u as $searchmodule) {
					$searchmodule->unindex_page($pageid);
				}

				$searchmodules_i = qa_load_modules_with('search', 'index_page');
				if (count($searchmodules_i)) {
					$indextext = qa_viewer_text($page['content'], 'html');

					foreach ($searchmodules_i as $searchmodule) {
						$searchmodule->index_page($pageid, $page['tags'], $page['heading'], $page['content'], 'html', $indextext);
					}
				}
			}
		}

		$this->state->next = 1 + $lastpageid;
		$this->state->done += count($pages);
		return true;
	}

	/**
	 * Get the current progress.
	 * @return string
	 */
	public function getMessage()
	{
		return $this->progressLang('admin/reindex_pages_reindexed', $this->state->done, $this->state->length);
	}
}
