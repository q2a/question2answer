<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Storage/MemcachedDriver.php
	Description: Memcached-based driver for caching system.


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
 * Caches data (typically from database queries) in memory using Memcached.
 */
class Q2A_Storage_MemcachedDriver implements Q2A_Storage_CacheDriver
{
	private $memcached;
	private $enabled = false;
	private $keyPrefix = '';
	private $flushed = false;

	private $host = '127.0.0.1';
	private $port = 11211;

	/**
	 * Creates a new Memcached instance and checks we can cache items.
	 * @param array $config Configuration data, including cache storage directory.
	 *
	 * @return void
	 */
	public function __construct($config)
	{
		if (!$config['enabled']) {
			return;
		}

		if (isset($config['keyprefix'])) {
			$this->keyPrefix = $config['keyprefix'];
		}

		if (!extension_loaded('memcached')) {
			return;
		}

		if (defined('QA_MEMCACHED_HOST')) {
			$this->host = QA_MEMCACHED_HOST;
		}
		if (defined('QA_MEMCACHED_PORT')) {
			$this->port = QA_MEMCACHED_PORT;
		}

		$this->memcached = new Memcached;
		$this->memcached->addServer($this->host, $this->port);

		$this->enabled = true;
	}

	/**
	 * Get the cached data for the supplied key. Data can be any format but is usually an array.
	 * @param string $key The unique cache identifier.
	 *
	 * @return mixed The cached data, or null otherwise.
	 */
	public function get($key)
	{
		if (!$this->enabled) {
			return null;
		}

		$result = $this->memcached->get($this->keyPrefix . $key);

		return $this->memcached->getResultCode() === Memcached::RES_SUCCESS ? $result : null;
	}

	/**
	 * Store something in the cache along with the key and expiry time. Data gets 'serialized' to a string before storing.
	 * @param string $key The unique cache identifier.
	 * @param mixed $data The data to cache (in core Q2A this is usually an array).
	 * @param int $ttl Number of minutes for which to cache the data.
	 *
	 * @return bool Whether the item was successfully cached.
	 */
	public function set($key, $data, $ttl)
	{
		if (!$this->enabled) {
			return false;
		}

		$expiry = time() + ((int)$ttl * 60);

		return $this->memcached->set($this->keyPrefix . $key, $data, $expiry);
	}

	/**
	 * Delete an item from the cache.
	 * @param string $key The unique cache identifier.
	 *
	 * @return bool Whether the operation succeeded.
	 */
	public function delete($key)
	{
		if (!$this->enabled) {
			return false;
		}

		return $this->memcached->delete($this->keyPrefix . $key);
	}

	/**
	 * Delete multiple items from the cache.
	 * @param int $limit Maximum number of items to process. 0 = unlimited
	 * @param int $start Offset from which to start (used for 'batching' deletes).
	 * @param bool $expiredOnly This parameter is ignored because Memcached automatically clears expired items.
	 *
	 * @return int Number of elements deleted. For Memcached we return 0
	 */
	public function clear($limit = 0, $start = 0, $expiredOnly = false)
	{
		if ($this->enabled && !$expiredOnly && !$this->flushed) {
			// avoid multiple calls to flush()
			$this->flushed = true;
			$this->memcached->flush();
		}

		return 0;
	}

	/**
	 * Whether caching is available.
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * Get the prefix used for all cache keys.
	 *
	 * @return string
	 */
	public function getKeyPrefix()
	{
		return $this->keyPrefix;
	}

	/**
	 * Get current statistics for the cache.
	 *
	 * @return array Array of stats: 'items' => number of files, 'size' => total item size in bytes.
	 */
	public function getStats()
	{
		$totalFiles = 0;
		$totalBytes = 0;

		if ($this->enabled) {
			$stats = $this->memcached->getStats();
			$key = $this->host . ':' . $this->port;

			$totalFiles = isset($stats[$key]['curr_items']) ? $stats[$key]['curr_items'] : 0;
			$totalBytes = isset($stats[$key]['bytes']) ? $stats[$key]['bytes'] : 0;
		}

		return array(
			'items' => $totalFiles,
			'size' => $totalBytes,
		);
	}

	/**
	 * Return result code from Memcached instance
	 */
	public function getResultCode()
	{
		return isset($this->memcached) ? $this->memcached->getResultCode() : null;
	}

	/**
	 * Return result message from Memcached instance
	 */
	public function getResultMessage()
	{
		return isset($this->memcached) ? $this->memcached->getResultMessage() : null;
	}

	/**
	 * Perform test operations and return an error string or null if no error was found
	 *
	 * @return string|null
	 */
	public function test()
	{
		if (!extension_loaded('memcached')) {
			return qa_lang_html('admin/no_memcached');
		}

		if (!$this->enabled) {
			return null;
		}

		if (!$this->memcached->set($this->keyPrefix . 'test', 'TEST')) {
			return qa_lang_html_sub('admin/memcached_error', $this->memcached->getResultMessage());
		}

		return null;
	}
}
