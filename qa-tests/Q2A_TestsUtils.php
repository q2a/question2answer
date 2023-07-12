<?php

class Q2A_TestsUtils
{
	/**
	 * Set an option in the option cache without accessing the database.
	 *
	 * @param string $option
	 * @param mixed $value
	 */
	public static function setCachedOption($option, $value)
	{
		global $qa_options_cache;
		$qa_options_cache[$option] = $value;
	}

	/**
	 * Remove all cached options.
	 */
	public static function removeAllCachedOptions()
	{
		global $qa_options_cache, $qa_options_loaded;
		$qa_options_cache = null;
		$qa_options_loaded = null;
	}
}
