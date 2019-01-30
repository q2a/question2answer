<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database-level access to tables which monitor rate limits


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


/**
 * Get rate limit information for $action from the database for user $userid and/or IP address $ip, if they're set.
 * Return as an array with the limit type in the key, and a labelled array of the period and count.
 * @param mixed $userid
 * @param string|null $ip
 * @param string $action
 * @return array
 */
function qa_db_limits_get($userid, $ip, $action)
{
	$selects = array();
	$arguments = array();

	if (isset($userid)) {
		$selects[] = "(SELECT 'user' AS limitkey, period, count FROM ^userlimits WHERE userid=$ AND action=$)";
		$arguments[] = $userid;
		$arguments[] = $action;
	}

	if (isset($ip)) {
		$selects[] = "(SELECT 'ip' AS limitkey, period, count FROM ^iplimits WHERE ip=UNHEX($) AND action=$)";
		$arguments[] = bin2hex(@inet_pton($ip));
		$arguments[] = $action;
	}

	if (!empty($selects)) {
		$query = qa_db_apply_sub(implode(' UNION ALL ', $selects), $arguments);
		return qa_db_read_all_assoc(qa_db_query_raw($query), 'limitkey');

	} else
		return array();
}


/**
 * Increment the database rate limit count for user $userid and $action by $count within $period
 * @param mixed $userid
 * @param string $action
 * @param int $period
 * @param int $count
 */
function qa_db_limits_user_add($userid, $action, $period, $count)
{
	qa_db_query_sub(
		'INSERT INTO ^userlimits (userid, action, period, count) VALUES ($, $, #, #) ' .
		'ON DUPLICATE KEY UPDATE count=IF(period=#, count+#, #), period=#',
		$userid, $action, $period, $count, $period, $count, $count, $period
	);
}


/**
 * Increment the database rate limit count for IP address $ip and $action by $count within $period
 * @param string $ip
 * @param string $action
 * @param int $period
 * @param int $count
 */
function qa_db_limits_ip_add($ip, $action, $period, $count)
{
	qa_db_query_sub(
		'INSERT INTO ^iplimits (ip, action, period, count) VALUES (UNHEX($), $, #, #) ' .
		'ON DUPLICATE KEY UPDATE count=IF(period=#, count+#, #), period=#',
		bin2hex(@inet_pton($ip)), $action, $period, $count, $period, $count, $count, $period
	);
}
