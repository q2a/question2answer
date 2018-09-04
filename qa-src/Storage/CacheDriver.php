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
 * Interface for caching drivers.
 */
interface CacheDriver
{
	/**
	 * Get the cached data for the supplied key. Data can be any format but is usually an array.
	 * @param string $key The unique cache identifier.
	 *
	 * @return mixed The cached data, or null otherwise.
	 */
	public function get($key);

	/**
	 * Store something in the cache along with the key and expiry time. Data gets 'serialized' to a string before storing.
	 * @param string $key The unique cache identifier.
	 * @param mixed $data The data to cache (in core Q2A this is usually an array).
	 * @param int $ttl Number of minutes for which to cache the data.
	 *
	 * @return bool Whether the file was successfully cached.
	 */
	public function set($key, $data, $ttl);

	/**
	 * Delete an item from the cache.
	 * @param string $key The unique cache identifier.
	 *
	 * @return bool Whether the operation succeeded.
	 */
	public function delete($key);

	/**
	 * Delete multiple items from the cache.
	 * @param int $limit Maximum number of items to process. 0 = unlimited
	 * @param int $start Offset from which to start (used for 'batching' deletes).
	 * @param bool $expiredOnly Delete cache only if it has expired.
	 *
	 * @return int Number of files deleted.
	 */
	public function clear($limit = 0, $start = 0, $expiredOnly = false);

	/**
	 * Whether caching is available.
	 *
	 * @return bool
	 */
	public function isEnabled();

	/**
	 * Get the last error.
	 *
	 * @return string
	 */
	public function getError();

	/**
	 * Get the prefix used for all cache keys.
	 *
	 * @return string
	 */
	public function getKeyPrefix();

	/**
	 * Get current statistics for the cache.
	 *
	 * @return array Array of stats: 'files' => number of files, 'size' => total file size in bytes.
	 */
	public function getStats();
}
