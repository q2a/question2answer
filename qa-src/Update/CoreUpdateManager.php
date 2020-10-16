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

namespace Q2A\Update;

/**
 * Keeps track of the core version.
 */
class CoreUpdateManager
{
	const OPT_CORE_UPDATE_CACHE = 'core_update_cache';
	const NUMBER_OF_DAYS_TO_CHECK_FOR_UPDATE = 28;

	const OPT_FIELD_TIME = 'time';
	const OPT_FIELD_VERSION = 'version';

	/**
	 * @return bool
	 */
	public function shouldCheckForUpdate()
	{
		$option = qa_opt(self::OPT_CORE_UPDATE_CACHE);
		$option = json_decode($option, true);
		$date = isset($option[self::OPT_FIELD_TIME])
			? (int)$option[self::OPT_FIELD_TIME]
			: 0;

		$currentTime = (int)qa_opt('db_time');

		return $currentTime - $date > self::NUMBER_OF_DAYS_TO_CHECK_FOR_UPDATE * 24 * 60 * 60;
	}

	/**
	 * @param string $version
	 * @param int|null $time
	 */
	public function setCachedVersion($version, $time = null)
	{
		if ($time === null) {
			$time = (int)qa_opt('db_time');
		}

		$option = [
			self::OPT_FIELD_TIME => $time,
			self::OPT_FIELD_VERSION => $version,
		];

		qa_opt(self::OPT_CORE_UPDATE_CACHE, json_encode($option));
	}

	/**
	 * @return array
	 */
	public function getCachedVersion()
	{
		$option = qa_opt(self::OPT_CORE_UPDATE_CACHE);
		$option = json_decode($option, true);

		return isset($option[self::OPT_FIELD_VERSION])
			? $option[self::OPT_FIELD_VERSION]
			: null;
	}
}
