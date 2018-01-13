<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Monitoring and rate-limiting user actions (application level)


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


define('QA_LIMIT_QUESTIONS', 'Q');
define('QA_LIMIT_ANSWERS', 'A');
define('QA_LIMIT_COMMENTS', 'C');
define('QA_LIMIT_VOTES', 'V');
define('QA_LIMIT_REGISTRATIONS', 'R');
define('QA_LIMIT_LOGINS', 'L');
define('QA_LIMIT_UPLOADS', 'U');
define('QA_LIMIT_FLAGS', 'F');
define('QA_LIMIT_MESSAGES', 'M'); // i.e. private messages
define('QA_LIMIT_WALL_POSTS', 'W');


/**
 * How many more times the logged in user (and requesting IP address) can perform an action this hour.
 * @param string $action One of the QA_LIMIT_* constants defined above.
 * @return int
 */
function qa_user_limits_remaining($action)
{
	$userlimits = qa_db_get_pending_result('userlimits', qa_db_user_limits_selectspec(qa_get_logged_in_userid()));
	$iplimits = qa_db_get_pending_result('iplimits', qa_db_ip_limits_selectspec(qa_remote_ip_address()));

	return qa_limits_calc_remaining($action, @$userlimits[$action], @$iplimits[$action]);
}

/**
 * Return how many more times user $userid and/or the requesting IP can perform $action (a QA_LIMIT_* constant) this hour.
 * @deprecated Deprecated from 1.6.0; use `qa_user_limits_remaining($action)` instead.
 * @param int $userid
 * @param string $action
 * @return mixed
 */
function qa_limits_remaining($userid, $action)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/limits.php';

	$dblimits = qa_db_limits_get($userid, qa_remote_ip_address(), $action);

	return qa_limits_calc_remaining($action, @$dblimits['user'], @$dblimits['ip']);
}

/**
 * Calculate how many more times an action can be performed this hour by the user/IP.
 * @param string $action One of the QA_LIMIT_* constants defined above.
 * @param array $userlimits Limits for the user.
 * @param array $iplimits Limits for the requesting IP.
 * @return mixed
 */
function qa_limits_calc_remaining($action, $userlimits, $iplimits)
{
	switch ($action) {
		case QA_LIMIT_QUESTIONS:
			$usermax = qa_opt('max_rate_user_qs');
			$ipmax = qa_opt('max_rate_ip_qs');
			break;

		case QA_LIMIT_ANSWERS:
			$usermax = qa_opt('max_rate_user_as');
			$ipmax = qa_opt('max_rate_ip_as');
			break;

		case QA_LIMIT_COMMENTS:
			$usermax = qa_opt('max_rate_user_cs');
			$ipmax = qa_opt('max_rate_ip_cs');
			break;

		case QA_LIMIT_VOTES:
			$usermax = qa_opt('max_rate_user_votes');
			$ipmax = qa_opt('max_rate_ip_votes');
			break;

		case QA_LIMIT_REGISTRATIONS:
			$usermax = 1; // not really relevant
			$ipmax = qa_opt('max_rate_ip_registers');
			break;

		case QA_LIMIT_LOGINS:
			$usermax = 1; // not really relevant
			$ipmax = qa_opt('max_rate_ip_logins');
			break;

		case QA_LIMIT_UPLOADS:
			$usermax = qa_opt('max_rate_user_uploads');
			$ipmax = qa_opt('max_rate_ip_uploads');
			break;

		case QA_LIMIT_FLAGS:
			$usermax = qa_opt('max_rate_user_flags');
			$ipmax = qa_opt('max_rate_ip_flags');
			break;

		case QA_LIMIT_MESSAGES:
		case QA_LIMIT_WALL_POSTS:
			$usermax = qa_opt('max_rate_user_messages');
			$ipmax = qa_opt('max_rate_ip_messages');
			break;

		default:
			qa_fatal_error('Unknown limit code in qa_limits_calc_remaining: ' . $action);
			break;
	}

	$period = (int)(qa_opt('db_time') / 3600);

	return max(0, min(
		$usermax - (@$userlimits['period'] == $period ? $userlimits['count'] : 0),
		$ipmax - (@$iplimits['period'] == $period ? $iplimits['count'] : 0)
	));
}

/**
 * Determine whether the requesting IP address has been blocked from write operations.
 * @return bool
 */
function qa_is_ip_blocked()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	global $qa_curr_ip_blocked;

	// return cached value early
	if (isset($qa_curr_ip_blocked))
		return $qa_curr_ip_blocked;

	$qa_curr_ip_blocked = false;
	$blockipclauses = qa_block_ips_explode(qa_opt('block_ips_write'));
	$ip = qa_remote_ip_address();

	foreach ($blockipclauses as $blockipclause) {
		if (qa_block_ip_match($ip, $blockipclause)) {
			$qa_curr_ip_blocked = true;
			break;
		}
	}

	return $qa_curr_ip_blocked;
}

/**
 * Return an array of the clauses within $blockipstring, each of which can contain hyphens or asterisks
 * @param $blockipstring
 * @return array
 */
function qa_block_ips_explode($blockipstring)
{
	$blockipstring = preg_replace('/\s*\-\s*/', '-', $blockipstring); // special case for 'x.x.x.x - x.x.x.x'

	return preg_split('/[^0-9A-Fa-f\.:\-\*]/', $blockipstring, -1, PREG_SPLIT_NO_EMPTY);
}

/**
 * Checks if the IP address is matched by the individual block clause, which can contain a hyphen or asterisk
 * @param string $ip The IP address
 * @param string $blockipclause The IP/clause to check against, e.g. 127.0.0.*
 * @return bool
 */
function qa_block_ip_match($ip, $blockipclause)
{
	$ipv4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
	$blockipv4 = filter_var($blockipclause, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;

	// allow faster return if IP and blocked IP are plain IPv4 strings (IPv6 requires expanding)
	if ($ipv4 && $blockipv4) {
		return $ip === $blockipclause;
	}

	if (filter_var($ip, FILTER_VALIDATE_IP)) {
		if (preg_match('/^(.*)\-(.*)$/', $blockipclause, $matches)) {
			// match IP range
			if (filter_var($matches[1], FILTER_VALIDATE_IP) && filter_var($matches[2], FILTER_VALIDATE_IP)) {
				return qa_ip_between($ip, $matches[1], $matches[2]);
			}
		} elseif (strlen($blockipclause)) {
			// normalize IPv6 addresses
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$ip = qa_ipv6_expand($ip);
				$blockipclause = qa_ipv6_expand($blockipclause);
			}

			// expand wildcards; preg_quote misses hyphens but that is OK here
			return preg_match('/^' . str_replace('\\*', '([0-9A-Fa-f]+)', preg_quote($blockipclause, '/')) . '$/', $ip) > 0;
		}
	}

	return false;
}

/**
 * Check if IP falls between two others.
 * @param $ip
 * @param $startip
 * @param $endip
 * @return bool
 */
function qa_ip_between($ip, $startip, $endip)
{
	$uip = unpack('C*', @inet_pton($ip));
	$ustartip = unpack('C*', @inet_pton($startip));
	$uendip = unpack('C*', @inet_pton($endip));

	if (count($uip) != count($ustartip) || count($uip) != count($uendip))
		return false;

	foreach ($uip as $i => $byte) {
		if ($byte < $ustartip[$i] || $byte > $uendip[$i]) {
			return false;
		}
	}

	return true;
}

/**
 * Expands an IPv6 address (possibly containing wildcards), e.g. ::ffff:1 to 0000:0000:0000:0000:0000:0000:ffff:0001.
 * Based on http://stackoverflow.com/a/12095836/753676
 * @param string $ip The IP address to expand.
 * @return string
 */
function qa_ipv6_expand($ip)
{
	$ipv6_wildcard = false;
	$wildcards = '';
	$wildcards_matched = array();
	if (strpos($ip, "*") !== false) {
		$ipv6_wildcard = true;
	}
	if ($ipv6_wildcard) {
		$wildcards = explode(":", $ip);
		foreach ($wildcards as $index => $value) {
			if ($value == "*") {
				$wildcards_matched[] = count($wildcards) - 1 - $index;
				$wildcards[$index] = "0";
			}
		}
		$ip = implode($wildcards, ":");
	}

	$hex = unpack("H*hex", @inet_pton($ip));
	$ip = substr(preg_replace("/([0-9A-Fa-f]{4})/", "$1:", $hex['hex']), 0, -1);

	if ($ipv6_wildcard) {
		$wildcards = explode(":", $ip);
		foreach ($wildcards_matched as $value) {
			$i = count($wildcards) - 1 - $value;
			$wildcards[$i] = "*";
		}
		$ip = implode($wildcards, ":");
	}

	return $ip;
}

/**
 * Called after a database write $action performed by a user identified by $userid and/or $cookieid.
 * @param int $userid
 * @param string $cookieid
 * @param string $action
 * @param int $questionid
 * @param int $answerid
 * @param int $commentid
 */
function qa_report_write_action($userid, $cookieid, $action, $questionid, $answerid, $commentid)
{
}

/**
 * Take note for rate limits that a user and/or the requesting IP just performed an action.
 * @param int $userid User performing the action.
 * @param string $action One of the QA_LIMIT_* constants defined above.
 * @return mixed
 */
function qa_limits_increment($userid, $action)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/limits.php';

	$period = (int)(qa_opt('db_time') / 3600);

	if (isset($userid))
		qa_db_limits_user_add($userid, $action, $period, 1);

	qa_db_limits_ip_add(qa_remote_ip_address(), $action, $period, 1);
}
