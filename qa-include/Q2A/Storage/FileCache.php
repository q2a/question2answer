<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Storage/FileCache.php
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
class Q2A_Storage_FileCache
{
	private $enabled = false;
	private $error;
	private $cacheDir;

	/**
	 * Creates a new FileCache instance and checks we can write to the cache directory.
	 * @param array $config Configuration data, including cache storage directory.
	 */
	public function __construct($config)
	{
		if (isset($config['dir'])) {
			$this->cacheDir = realpath($config['dir']);

			if (!is_writable($this->cacheDir)) {
				$this->error = qa_lang_html_sub('admin/caching_dir_error', $config['dir']);
			} elseif (strpos($this->cacheDir, realpath($_SERVER['DOCUMENT_ROOT'])) === 0 || strpos($this->cacheDir, realpath(QA_BASE_DIR)) === 0) {
				// check the folder is outside the public root - checks against server root and Q2A root, in order to handle symbolic links
				$this->error = qa_lang_html_sub('admin/caching_dir_public', $config['dir']);
			}
		} else {
			$this->error = qa_lang_html('admin/caching_dir_missing');
		}

		$this->enabled = empty($this->error);
	}

	/**
	 * Get the cached data for the supplied key.
	 * @param string $key The unique cache identifier.
	 * @return string The cached data, or null otherwise.
	 */
	public function get($key)
	{
		$file = $this->getFilename($key);

		if (is_readable($file)) {
			$lines = file($file, FILE_IGNORE_NEW_LINES);
			$actualKey = array_shift($lines);

			// double check this is the correct data
			if ($key === $actualKey) {
				$expiry = array_shift($lines);

				if (is_numeric($expiry) && time() < $expiry) {
					return implode("\n", $lines);
				}
			}
		}

		return null;
	}

	/**
	 * Store a string (usually serialized data) in the cache along with the key and expiry time.
	 * @param string $key The unique cache identifier.
	 * @param string $str The data to cache (usually a serialized string).
	 * @param int $ttl Number of minutes for which to cache the data.
	 * @return bool Whether the file was successfully cached.
	 */
	public function set($key, $str, $ttl)
	{
		$success = false;
		$ttl = (int) $ttl;

		if ($this->enabled && $ttl > 0) {
			$file = $this->getFilename($key);
			$dir = dirname($file);
			$expiry = time() + ($ttl * 60);
			$cache = $key . "\n" . $expiry . "\n" . $str;

			if (is_dir($dir) || mkdir($dir, 0777, true)) {
				$success = file_put_contents($file, $cache) !== false;
			}
		}

		return $success;
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

	/**
	 * Generates filename for cache key, of the form `1/23/123abc`
	 * @param string $key The unique cache key.
	 * @return string
	 */
	private function getFilename($key)
	{
		$filename = sha1($key);
		return $this->cacheDir . '/' . substr($filename, 0, 1) . '/' . substr($filename, 1, 2) . '/' . $filename;
	}
}
