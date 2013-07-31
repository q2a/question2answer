<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-ip.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for page showing recent activity for an IP address


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

	
	$ip=qa_request_part(1); // picked up from qa-page.php
	if (long2ip(ip2long($ip))!==$ip)
		return include QA_INCLUDE_DIR.'qa-page-not-found.php';


//	Find recently (hidden, queued or not) questions, answers, comments and edits for this IP

	$userid=qa_get_logged_in_userid();

	list($qs, $qs_queued, $qs_hidden, $a_qs, $a_queued_qs, $a_hidden_qs, $c_qs, $c_queued_qs, $c_hidden_qs, $edit_qs)=
		qa_db_select_with_pending(
			qa_db_qs_selectspec($userid, 'created', 0, null, $ip, false),
			qa_db_qs_selectspec($userid, 'created', 0, null, $ip, 'Q_QUEUED'),
			qa_db_qs_selectspec($userid, 'created', 0, null, $ip, 'Q_HIDDEN', true),
			qa_db_recent_a_qs_selectspec($userid, 0, null, $ip, false),
			qa_db_recent_a_qs_selectspec($userid, 0, null, $ip, 'A_QUEUED'),
			qa_db_recent_a_qs_selectspec($userid, 0, null, $ip, 'A_HIDDEN', true),
			qa_db_recent_c_qs_selectspec($userid, 0, null, $ip, false),
			qa_db_recent_c_qs_selectspec($userid, 0, null, $ip, 'C_QUEUED'),
			qa_db_recent_c_qs_selectspec($userid, 0, null, $ip, 'C_HIDDEN', true),
			qa_db_recent_edit_qs_selectspec($userid, 0, null, $ip, false)
		);
	
	
//	Check we have permission to view this page, and whether we can block or unblock IPs

	if (qa_user_maximum_permit_error('permit_anon_view_ips')) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}
	
	$blockable=qa_user_level_maximum()>=QA_USER_LEVEL_MODERATOR; // allow moderator in one category to block across all categories
		

//	Perform blocking or unblocking operations as appropriate

	if (qa_clicked('doblock') || qa_clicked('dounblock') || qa_clicked('dohideall')) {
		if (!qa_check_form_security_code('ip-'.$ip, qa_post_text('code')))
			$pageerror=qa_lang_html('misc/form_security_again');

		elseif ($blockable) {
		
			if (qa_clicked('doblock')) {
				$oldblocked=qa_opt('block_ips_write');
				qa_set_option('block_ips_write', (strlen($oldblocked) ? ($oldblocked.' , ') : '').$ip);
				
				qa_report_event('ip_block', $userid, qa_get_logged_in_handle(), qa_cookie_get(), array(
					'ip' => $ip,
				));
				
				qa_redirect(qa_request());
			}
			
			if (qa_clicked('dounblock')) {
				require_once QA_INCLUDE_DIR.'qa-app-limits.php';
				
				$blockipclauses=qa_block_ips_explode(qa_opt('block_ips_write'));
				
				foreach ($blockipclauses as $key => $blockipclause)
					if (qa_block_ip_match($ip, $blockipclause))
						unset($blockipclauses[$key]);
						
				qa_set_option('block_ips_write', implode(' , ', $blockipclauses));
	
				qa_report_event('ip_unblock', $userid, qa_get_logged_in_handle(), qa_cookie_get(), array(
					'ip' => $ip,
				));
	
				qa_redirect(qa_request());
			}
			
			if (qa_clicked('dohideall') && !qa_user_maximum_permit_error('permit_hide_show')) {
				// allow moderator in one category to hide posts across all categories if they are identified via IP page
				
				require_once QA_INCLUDE_DIR.'qa-db-admin.php';
				require_once QA_INCLUDE_DIR.'qa-app-posts.php';
			
				$postids=qa_db_get_ip_visible_postids($ip);
	
				foreach ($postids as $postid)
					qa_post_set_hidden($postid, true, $userid);
					
				qa_redirect(qa_request());
			}
		}
	}
	

//	Combine sets of questions and get information for users

	$questions=qa_any_sort_by_date(array_merge($qs, $qs_queued, $qs_hidden, $a_qs, $a_queued_qs, $a_hidden_qs, $c_qs, $c_queued_qs, $c_hidden_qs, $edit_qs));
	
	$usershtml=qa_userids_handles_html(qa_any_get_userids_handles($questions));

	$hostname=gethostbyaddr($ip);
	

//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html_sub('main/ip_address_x', qa_html($ip));
	$qa_content['error']=@$pageerror;

	$qa_content['form']=array(
		'tags' => 'method="post" action="'.qa_self_html().'"',
		
		'style' => 'wide',
		
		'fields' => array(
			'host' => array(
				'type' => 'static',
				'label' => qa_lang_html('misc/host_name'),
				'value' => qa_html($hostname),
			),
		),
		
		'hidden' => array(
			'code' => qa_get_form_security_code('ip-'.$ip),
		),
	);
	

	if ($blockable) {
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		$blockipclauses=qa_block_ips_explode(qa_opt('block_ips_write'));
		$matchclauses=array();
		
		foreach ($blockipclauses as $blockipclause)
			if (qa_block_ip_match($ip, $blockipclause))
				$matchclauses[]=$blockipclause;
		
		if (count($matchclauses)) {
			$qa_content['form']['fields']['status']=array(
				'type' => 'static',
				'label' => qa_lang_html('misc/matches_blocked_ips'),
				'value' => qa_html(implode("\n", $matchclauses), true),
			);
			
			$qa_content['form']['buttons']['unblock']=array(
				'tags' => 'name="dounblock"',
				'label' => qa_lang_html('misc/unblock_ip_button'),
			);
			
			if (count($questions) && !qa_user_maximum_permit_error('permit_hide_show'))
				$qa_content['form']['buttons']['hideall']=array(
					'tags' => 'name="dohideall" onclick="qa_show_waiting_after(this, false);"',
					'label' => qa_lang_html('misc/hide_all_ip_button'),
				);

		} else
			$qa_content['form']['buttons']['block']=array(
				'tags' => 'name="doblock"',
				'label' => qa_lang_html('misc/block_ip_button'),
			);
	}

	
	$qa_content['q_list']['qs']=array();
	
	if (count($questions)) {
		$qa_content['q_list']['title']=qa_lang_html_sub('misc/recent_activity_from_x', qa_html($ip));
	
		foreach ($questions as $question) {
			$htmloptions=qa_post_html_options($question);
			$htmloptions['tagsview']=false;
			$htmloptions['voteview']=false;
			$htmloptions['ipview']=false;
			$htmloptions['answersview']=false;
			$htmloptions['viewsview']=false;
			$htmloptions['updateview']=false;
			
			$htmlfields=qa_any_to_q_html_fields($question, $userid, qa_cookie_get(), $usershtml, null, $htmloptions);
			
			if (isset($htmlfields['what_url'])) // link directly to relevant content
				$htmlfields['url']=$htmlfields['what_url'];
			
			$hasother=isset($question['opostid']);
			
			if ($question[$hasother ? 'ohidden' : 'hidden'] && !isset($question[$hasother ? 'oupdatetype' : 'updatetype'])) {
				$htmlfields['what_2']=qa_lang_html('main/hidden');

				if (@$htmloptions['whenview']) {
					$updated=@$question[$hasother ? 'oupdated' : 'updated'];
					if (isset($updated))
						$htmlfields['when_2']=qa_when_to_html($updated, @$htmloptions['fulldatedays']);
				}
			}

			$qa_content['q_list']['qs'][]=$htmlfields;
		}

	} else
		$qa_content['q_list']['title']=qa_lang_html_sub('misc/no_activity_from_x', qa_html($ip));
	
	
	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/