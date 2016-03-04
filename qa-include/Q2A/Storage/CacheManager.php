<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Storage/CacheManager.php
	Description: Handler for caching system.


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

/**
 * Caches data (typically from database queries) to the filesystem.
 */
class Q2A_Storage_CacheManager
{
	private $enabled = false;
	private $error = '';
	private $dir;

	/**
	 * Creates a new CacheManager instance and checks it's set up properly.
	 */
	public function __construct()
	{
		$optEnabled = qa_opt('caching_enabled') == 1;

		if (defined('QA_CACHE_DIRECTORY')) {
			// expand symlinks so we compare true paths
			$this->dir = realpath(QA_CACHE_DIRECTORY);
			$baseDir = realpath(QA_BASE_DIR);

			if (!is_writable($this->dir)) {
				$this->error = qa_lang_html_sub('admin/caching_dir_error', QA_CACHE_DIRECTORY);
			} elseif (strpos($this->dir, $baseDir) === 0) {
				$this->error = qa_lang_html_sub('admin/caching_dir_public', QA_CACHE_DIRECTORY);
			}

			$this->enabled = empty($this->error) && $optEnabled;
		} elseif ($optEnabled) {
			$this->error = qa_lang_html('admin/caching_dir_missing');
		}
	}

	/**
	 * Whether caching is available.
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * Get the last error.
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}
}
