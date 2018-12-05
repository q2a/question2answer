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

namespace Q2A\Storage;

/**
 * Caches data (typically from database queries) in memory using Memcached.
 */
class MemcachedDriver implements CacheDriver
{
	private $memcached;
	private $enabled = false;
	private $keyPrefix = '';
	private $error;
	private $flushed = false;

	const HOST = '127.0.0.1';
	const PORT = 11211;

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

		if (extension_loaded('memcached')) {
			$this->memcached = new \Memcached;
			$this->memcached->addServer(self::HOST, self::PORT);
			if ($this->memcached->set($this->keyPrefix . 'test', 'TEST')) {
				$this->enabled = true;
			} else {
				$this->setMemcachedError();
			}
		} else {
			$this->error = qa_lang_html('admin/no_memcached');
		}
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

		if ($result === false) {
			$this->setMemcachedError();
			return null;
		}

		return $result;
	}

	/**
	 * Store something in the cache along with the key and expiry time. Data gets 'serialized' to a string before storing.
	 * @param string $key The unique cache identifier.
	 * @param mixed $data The data to cache (in core Q2A this is usually an array).
	 * @param int $ttl Number of minutes for which to cache the data.
	 *
	 * @return bool Whether the file was successfully cached.
	 */
	public function set($key, $data, $ttl)
	{
		if (!$this->enabled) {
			return false;
		}

		$ttl = (int) $ttl;
		$expiry = time() + ($ttl * 60);
		$success = $this->memcached->set($this->keyPrefix . $key, $data, $expiry);

		if (!$success) {
			$this->setMemcachedError();
		}

		return $success;
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

		$success = $this->memcached->delete($this->keyPrefix . $key);

		if (!$success) {
			$this->setMemcachedError();
		}

		return $success;
	}

	/**
	 * Delete multiple items from the cache.
	 * @param int $limit Maximum number of items to process. 0 = unlimited
	 * @param int $start Offset from which to start (used for 'batching' deletes).
	 * @param bool $expiredOnly This parameter is ignored because Memcached automatically clears expired items.
	 *
	 * @return int Number of files deleted. For Memcached we return 0
	 */
	public function clear($limit = 0, $start = 0, $expiredOnly = false)
	{
		if ($this->enabled && !$expiredOnly && !$this->flushed) {
			$success = $this->memcached->flush();
			// avoid multiple calls to flush()
			$this->flushed = true;

			if (!$success) {
				$this->setMemcachedError();
			}
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
	 * Get the last error.
	 *
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
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
	 * @return array Array of stats: 'files' => number of files, 'size' => total file size in bytes.
	 */
	public function getStats()
	{
		$totalFiles = 0;
		$totalBytes = 0;

		if ($this->enabled) {
			$stats = $this->memcached->getStats();
			$key = self::HOST . ':' . self::PORT;

			$totalFiles = isset($stats[$key]['curr_items']) ? $stats[$key]['curr_items'] : 0;
			$totalBytes = isset($stats[$key]['bytes']) ? $stats[$key]['bytes'] : 0;
		}

		return array(
			'files' => $totalFiles,
			'size' => $totalBytes,
		);
	}

	/**
	 * Set current error to Memcached result message
	 *
	 * @return void
	 */
	private function setMemcachedError()
	{
		$this->error = qa_lang_html_sub('admin/memcached_error', $this->memcached->getResultMessage());
	}
}
