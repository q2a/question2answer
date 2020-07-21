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

class CacheClearProcess extends AbstractStep
{
	/**
	 * Perform the recalculation.
	 * @return bool
	 */
	public function doStep()
	{
		$cacheDriver = \Q2A\Storage\CacheFactory::getCacheDriver();
		$cacheStats = $cacheDriver->getStats();
		$limit = min($cacheStats['files'], 20);

		if (!($cacheStats['files'] > 0 && $this->state->next <= $this->state->length)) {
			$this->state->transition('docacheclear_complete');
			return false;
		}

		$deleted = $cacheDriver->clear($limit, $this->state->next, ($this->state->operation === 'docachetrim_process'));
		$this->state->done += $deleted;
		$this->state->next += $limit - $deleted; // skip files that weren't deleted on next iteration
		return true;
	}

	/**
	 * Get the current progress.
	 * @return string
	 */
	public function getMessage()
	{
		return $this->progressLang('admin/caching_delete_progress', $this->state->done, $this->state->length);
	}
}
