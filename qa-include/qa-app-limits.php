<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-limits.php
	Version: See define()s at top of qa-include/qa-base.php
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
		header('Location: ../');
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
	define('QA_LIMIT_MESSAGES', 'M');

	
	function qa_limits_remaining($userid, $action)
/*
	Return how many more times user $userid and/or the requesting IP can perform $action this hour,
	where $action is one of the QA_LIMIT_* constants defined above.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-db-limits.php';

		$period=(int)(qa_opt('db_time')/3600);
		$dblimits=qa_db_limits_get($userid, qa_remote_ip_address(), $action);
		
		switch ($action) {
			case QA_LIMIT_QUESTIONS:
				$userlimit=qa_opt('max_rate_user_qs');
				$iplimit=qa_opt('max_rate_ip_qs');
				break;
				
			case QA_LIMIT_ANSWERS:
				$userlimit=qa_opt('max_rate_user_as');
				$iplimit=qa_opt('max_rate_ip_as');
				break;
				
			case QA_LIMIT_COMMENTS:
				$userlimit=qa_opt('max_rate_user_cs');
				$iplimit=qa_opt('max_rate_ip_cs');
				break;

			case QA_LIMIT_VOTES:
				$userlimit=qa_opt('max_rate_user_votes');
				$iplimit=qa_opt('max_rate_ip_votes');
				break;
				
			case QA_LIMIT_REGISTRATIONS:
				$userlimit=1; // not really relevant
				$iplimit=qa_opt('max_rate_ip_registers');
				break;

			case QA_LIMIT_LOGINS:
				$userlimit=1; // not really relevant
				$iplimit=qa_opt('max_rate_ip_logins');
				break;
				
			case QA_LIMIT_UPLOADS:
				$userlimit=qa_opt('max_rate_user_uploads');
				$iplimit=qa_opt('max_rate_ip_uploads');
				break;
				
			case QA_LIMIT_FLAGS:
				$userlimit=qa_opt('max_rate_user_flags');
				$iplimit=qa_opt('max_rate_ip_flags');
				break;
				
			case QA_LIMIT_MESSAGES:
				$userlimit=qa_opt('max_rate_user_messages');
				$iplimit=qa_opt('max_rate_ip_messages');
				break;
			
			default:
				qa_fatal_error('Unknown limit code in qa_limits_remaining: '.$action);
				break;
		}
		
		return max(0, min(
			$userlimit-((@$dblimits['user']['period']==$period) ? $dblimits['user']['count'] : 0),
			$iplimit-((@$dblimits['ip']['period']==$period) ? $dblimits['ip']['count'] : 0)
		));
	}
	
	
	function qa_is_ip_blocked()
/*
	Return whether the requesting IP address has been blocked from write operations
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$blockipclauses=qa_block_ips_explode(qa_opt('block_ips_write'));
		
		foreach ($blockipclauses as $blockipclause)
			if (qa_block_ip_match(qa_remote_ip_address(), $blockipclause))
				return true;
				
		return false;
	}

	
	function qa_block_ips_explode($blockipstring)
/*
	Return an array of the clauses within $blockipstring, each of which can contain hyphens or asterisks
*/
	{
		$blockipstring=preg_replace('/\s*\-\s*/', '-', $blockipstring); // special case for 'x.x.x.x - x.x.x.x'
	
		return preg_split('/[^0-9\.\-\*]/', $blockipstring, -1, PREG_SPLIT_NO_EMPTY);
	}

	
	function qa_block_ip_match($ip, $blockipclause)
/*
	Returns whether the ip address $ip is matched by the clause $blockipclause, which can contain a hyphen or asterisk
*/
	{
		if (long2ip(ip2long($ip))==$ip) {
			if (preg_match('/^(.*)\-(.*)$/', $blockipclause, $matches)) {
				if ( (long2ip(ip2long($matches[1]))==$matches[1]) && (long2ip(ip2long($matches[2]))==$matches[2]) ) {
					$iplong=sprintf('%u', ip2long($ip));
					$end1long=sprintf('%u', ip2long($matches[1]));
					$end2long=sprintf('%u', ip2long($matches[2]));
					
					return (($iplong>=$end1long) && ($iplong<=$end2long)) || (($iplong>=$end2long) && ($iplong<=$end1long));
				}
	
			} elseif (strlen($blockipclause))
				return preg_match('/^'.str_replace('\\*', '[0-9]+', preg_quote($blockipclause, '/')).'$/', $ip) > 0;
					// preg_quote misses hyphens but that is OK here
		}
			
		return false;
	}
	
	
	function qa_report_write_action($userid, $cookieid, $action, $questionid, $answerid, $commentid)
/*
	Called after a database write $action performed by a user identified by $userid and/or $cookieid.
*/
	{}

	
	function qa_limits_increment($userid, $action)
/*
	Take note for rate limits that user $userid and/or the requesting IP just performed $action,
	where $action is one of the QA_LIMIT_* constants defined above.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-db-limits.php';

		$period=(int)(qa_opt('db_time')/3600);
		
		if (isset($userid))
			qa_db_limits_user_add($userid, $action, $period, 1);
		
		qa_db_limits_ip_add(qa_remote_ip_address(), $action, $period, 1);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/