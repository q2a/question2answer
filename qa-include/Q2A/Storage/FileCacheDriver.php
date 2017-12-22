<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Storage/FileCacheDriver.php
	Description: File-based driver for caching system.


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
class Q2A_Storage_FileCacheDriver implements Q2A_Storage_CacheDriver
{
	private $enabled = false;
	private $keyPrefix = '';
	private $error;
	private $cacheDir;

	private $phpProtect = '<?php header($_SERVER[\'SERVER_PROTOCOL\'].\' 404 Not Found\'); die; ?>';

	/**
	 * Creates a new FileCache instance and checks we can write to the cache directory.
	 * @param array $config Configuration data, including cache storage directory.
	 */
	public function __construct($config)
	{
		if (!$config['enabled']) {
			return;
		}

		if (isset($config['keyprefix'])) {
			$this->keyPrefix = $config['keyprefix'];
		}

		if (isset($config['dir'])) {
			$this->cacheDir = realpath($config['dir']);
			if (!is_writable($this->cacheDir)) {
				$this->error = qa_lang_html_sub('admin/caching_dir_error', $config['dir']);
			}
		} else {
			$this->error = qa_lang_html('admin/caching_dir_missing');
		}

		$this->enabled = empty($this->error);
	}

	/**
	 * Get the cached data for the supplied key.
	 * @param string $key The unique cache identifier.
	 *
	 * @return string The cached data, or null otherwise.
	 */
	public function get($key)
	{
		if (!$this->enabled) {
			return null;
		}

		$fullKey = $this->keyPrefix . $key;
		$file = $this->getFilename($fullKey);

		if (is_readable($file)) {
			$lines = file($file, FILE_IGNORE_NEW_LINES);
			$skipLine = array_shift($lines);
			$actualKey = array_shift($lines);

			// double check this is the correct data
			if ($fullKey === $actualKey) {
				$expiry = array_shift($lines);

				if (is_numeric($expiry) && time() < $expiry) {
					$encData = implode("\n", $lines);
					// decode data, ignoring any notices
					$data = @unserialize($encData);
					if ($data !== false) {
						return $data;
					}
				}
			}
		}

		return null;
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
		$success = false;
		$ttl = (int) $ttl;
		$fullKey = $this->keyPrefix . $key;

		if ($this->enabled && $ttl > 0) {
			$encData = serialize($data);
			$expiry = time() + ($ttl * 60);
			$cache = $this->phpProtect . "\n" . $fullKey . "\n" . $expiry . "\n" . $encData;

			$file = $this->getFilename($fullKey);
			$dir = dirname($file);
			if (is_dir($dir) || mkdir($dir, 0777, true)) {
				$success = @file_put_contents($file, $cache) !== false;
			}
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
		$fullKey = $this->keyPrefix . $key;

		if ($this->enabled) {
			$file = $this->getFilename($fullKey);
			return $this->deleteFile($file);
		}

		return false;
	}

	/**
	 * Delete multiple items from the cache.
	 * @param int $limit Maximum number of items to process. 0 = unlimited
	 * @param int $start Offset from which to start (used for 'batching' deletes).
	 * @param bool $expiredOnly Delete cache only if it has expired.
	 *
	 * @return int Number of files deleted.
	 */
	public function clear($limit = 0, $start = 0, $expiredOnly = false)
	{
		$seek = $processed = $deleted = 0;

		// fetch directories first to lower memory usage
		$cacheDirs = glob($this->cacheDir . '/*/*', GLOB_ONLYDIR);
		foreach ($cacheDirs as $dir) {
			$cacheFiles = glob($dir . '/*');
			foreach ($cacheFiles as $file) {
				if ($seek < $start) {
					$seek++;
					continue;
				}

				$wasDeleted = false;
				if ($expiredOnly) {
					if (is_readable($file)) {
						$fp = fopen($file, 'r');
						$key = fgets($fp);
						$expiry = (int) trim(fgets($fp));
						if (is_numeric($expiry) && time() > $expiry) {
							$wasDeleted = $this->deleteFile($file);
						}
					}
				} else {
					$wasDeleted = $this->deleteFile($file);
				}

				if ($wasDeleted) {
					$deleted++;
				}

				$processed++;
				if ($processed >= $limit) {
					break 2;
				}
			}
		}

		// return how many files were deleted - caller can figure out how many to skip next time
		return $deleted;
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
		if (!$this->enabled) {
			return array('files' => 0, 'size' => 0);
		}

		$totalFiles = 0;
		$totalBytes = 0;
		$dirIter = new RecursiveDirectoryIterator($this->cacheDir);
		foreach (new RecursiveIteratorIterator($dirIter) as $file) {
			if (strpos($file->getFilename(), '.') === 0) {
				// TODO: use FilesystemIterator::SKIP_DOTS once we're on minimum PHP 5.3
				continue;
			}

			$totalFiles++;
			$totalBytes += $file->getSize();
		}

		return array(
			'files' => $totalFiles,
			'size' => $totalBytes,
		);
	}

	/**
	 * Delete a specific file
	 * @param string $file Filename to delete.
	 *
	 * @return bool Whether the file was deleted successfully.
	 */
	private function deleteFile($file)
	{
		if (is_writable($file)) {
			return @unlink($file) === true;
		}

		return false;
	}

	/**
	 * Generates filename for cache key, of the form `1/23/123abc`
	 * @param string $key The unique cache key (including prefix).
	 *
	 * @return string
	 */
	private function getFilename($fullKey)
	{
		$filename = sha1($fullKey);
		return $this->cacheDir . '/' . substr($filename, 0, 1) . '/' . substr($filename, 1, 2) . '/' . $filename . '.php';
	}
}
