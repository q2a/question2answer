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
	private static $instance;
	private $enabled = false;
	private $cacheDriver;

	/**
	 * Creates a new CacheManager instance and sets up the cache driver.
	 */
	private function __construct()
	{
		$optEnabled = qa_opt('caching_enabled') == 1;

		$config = array(
			'dir' => defined('QA_CACHE_DIRECTORY') ? QA_CACHE_DIRECTORY : null,
		);

		$this->cacheDriver = new Q2A_Storage_FileCache($config);
		$this->enabled = $optEnabled && $this->cacheDriver->isEnabled();
	}

	/**
	 * Initializes the class and returns the singleton.
	 * @return Q2A_Storage_CacheManager
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the cached data for the supplied key.
	 * @param string $key The unique cache identifier
	 * @return mixed The cached data, or null otherwise.
	 */
	public function get($key)
	{
		if ($this->enabled) {
			$encData = $this->cacheDriver->get($key);

			// retrieve data, ignoring any notices
			$data = @unserialize($encData);
			if ($data !== false) {
				return $data;
			}
		}

		return null;
	}

	/**
	 * Serialize some data and store it in the cache.
	 * @param string $key The unique cache identifier
	 * @param mixed $data The data to cache - must be scalar values (i.e. string, int, array).
	 * @param int $ttl Number of minutes for which to cache the data.
	 * @return bool Whether the data was successfully cached.
	 */
	public function set($key, $data, $ttl)
	{
		if ($this->enabled) {
			$encData = serialize($data);
			return $this->cacheDriver->set($key, $encData, $ttl);
		}

		return false;
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
		return isset($this->cacheDriver) ? $this->cacheDriver->getError() : '';
	}
}
