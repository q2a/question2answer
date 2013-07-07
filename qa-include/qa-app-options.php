<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-options.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Getting and setting admin options (application level)


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

	require_once QA_INCLUDE_DIR.'qa-db-options.php';

	define('QA_PERMIT_ALL', 150);
	define('QA_PERMIT_USERS', 120);
	define('QA_PERMIT_CONFIRMED', 110);
	define('QA_PERMIT_POINTS', 106);
	define('QA_PERMIT_POINTS_CONFIRMED', 104);
	define('QA_PERMIT_APPROVED', 103);
	define('QA_PERMIT_APPROVED_POINTS', 102);
	define('QA_PERMIT_EXPERTS', 100);
	define('QA_PERMIT_EDITORS', 70);
	define('QA_PERMIT_MODERATORS', 40);
	define('QA_PERMIT_ADMINS', 20);
	define('QA_PERMIT_SUPERS', 0);

	
	function qa_get_options($names)
/*
	Return an array [name] => [value] of settings for each option in $names.
	If any options are missing from the database, set them to their defaults
*/
	{
		global $qa_options_cache, $qa_options_loaded;
		
	//	If any options not cached, retrieve them from database via standard pending mechanism

		if (!$qa_options_loaded)
			qa_preload_options();
			
		if (!$qa_options_loaded) {
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			
			qa_load_options_results(array(
				qa_db_get_pending_result('options'),
				qa_db_get_pending_result('time'),
			));
		}
		
	//	Pull out the options specifically requested here, and assign defaults

		$options=array();
		foreach ($names as $name) {
			if (!isset($qa_options_cache[$name])) {
				$todatabase=true;
				
				switch ($name) { // don't write default to database if option was deprecated, or depends on site language (which could be changed)
					case 'custom_sidebar':
					case 'site_title':
					case 'email_privacy':
					case 'answer_needs_login':
					case 'ask_needs_login':
					case 'comment_needs_login':
					case 'db_time':
						$todatabase=false;
						break;
				}
				
				qa_set_option($name, qa_default_option($name), $todatabase);
			}
			
			$options[$name]=$qa_options_cache[$name];
		}
		
		return $options;
	}

	
	function qa_opt_if_loaded($name)
/*
	Return the value of option $name if it has already been loaded, otherwise return null
	(used to prevent a database query if it's not essential for us to know the option value)
*/
	{
		global $qa_options_cache;
		
		return @$qa_options_cache[$name];
	}
	
	
	function qa_options_set_pending($names)
/*
	This is deprecated since Q2A 1.3 now that all options are retrieved together.
	Function kept for backwards compatibility with modified Q2A code bases.
*/
	{}

	
	function qa_preload_options()
/*
	Load all of the Q2A options from the database, unless QA_OPTIMIZE_DISTANT_DB is set in qa-config.php,
	in which case queue the options for later retrieval
*/
	{
		global $qa_options_loaded;
		
		if (!@$qa_options_loaded) {
			$selectspecs=array(
				'options' => array(
					'columns' => array('title', 'content'),
					'source' => '^options',
					'arraykey' => 'title',
					'arrayvalue' => 'content',
				),
				
				'time' => array(
					'columns' => array('title' => "'db_time'", 'content' => 'UNIX_TIMESTAMP(NOW())'),
					'arraykey' => 'title',
					'arrayvalue' => 'content',
				),
			);
			
			if (QA_OPTIMIZE_DISTANT_DB) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				
				foreach ($selectspecs as $pendingid => $selectspec)
					qa_db_queue_pending_select($pendingid, $selectspec);
				
			} else
				qa_load_options_results(qa_db_multi_select($selectspecs));
		}
	}
	
	
	function qa_load_options_results($results)
/*
	Load the options from the $results of the database selectspecs defined in qa_preload_options()
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_options_cache, $qa_options_loaded;
	
		foreach ($results as $result)
			foreach ($result as $name => $value)
				$qa_options_cache[$name]=$value;
			
		$qa_options_loaded=true;
	}

	
	function qa_set_option($name, $value, $todatabase=true)
/*
	Set an option $name to $value (application level) in both cache and database, unless
	$todatabase=false, in which case set it in the cache only
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_options_cache;
		
		if ($todatabase && isset($value))
			qa_db_set_option($name, $value);

		$qa_options_cache[$name]=$value;
	}

	
	function qa_reset_options($names)
/*
	Reset the options in $names to their defaults
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		foreach ($names as $name)
			qa_set_option($name, qa_default_option($name));
	}

	
	function qa_default_option($name)
/*
	Return the default value for option $name
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$fixed_defaults=array(
			'allow_change_usernames' => 1,
			'allow_close_questions' => 1,
			'allow_multi_answers' => 1,
			'allow_private_messages' => 1,
			'allow_user_walls' => 1,
			'allow_self_answer' => 1,
			'allow_view_q_bots' => 1,
			'avatar_allow_gravatar' => 1,
			'avatar_allow_upload' => 1,
			'avatar_message_list_size' => 20,
			'avatar_profile_size' => 200,
			'avatar_q_list_size' => 0,
			'avatar_q_page_a_size' => 40,
			'avatar_q_page_c_size' => 20,
			'avatar_q_page_q_size' => 50,
			'avatar_store_size' => 400,
			'avatar_users_size' => 30,
			'captcha_on_anon_post' => 1,
			'captcha_on_feedback' => 1,
			'captcha_on_register' => 1,
			'captcha_on_reset_password' => 1,
			'captcha_on_unconfirmed' => 0,
			'columns_tags' => 3,
			'columns_users' => 2,
			'comment_on_as' => 1,
			'comment_on_qs' => 0,
			'confirm_user_emails' => 1,
			'do_ask_check_qs' => 0,
			'do_complete_tags' => 1,
			'do_count_q_views' => 1,
			'do_example_tags' => 1,
			'feed_for_activity' => 1,
			'feed_for_qa' => 1,
			'feed_for_questions' => 1,
			'feed_for_unanswered' => 1,
			'feed_full_text' => 1,
			'feed_number_items' => 50,
			'feed_per_category' => 1,
			'feedback_enabled' => 1,
			'flagging_hide_after' => 5,
			'flagging_notify_every' => 2,
			'flagging_notify_first' => 1,
			'flagging_of_posts' => 1,
			'follow_on_as' => 1,
			'hot_weight_a_age' => 100,
			'hot_weight_answers' => 100,
			'hot_weight_q_age' => 100,
			'hot_weight_views' => 100,
			'hot_weight_votes' => 100,
			'mailing_per_minute' => 500,
			'match_ask_check_qs' => 3,
			'match_example_tags' => 3,
			'match_related_qs' => 3,
			'max_copy_user_updates' => 10,
			'max_len_q_title' => 120,
			'max_num_q_tags' => 5,
			'max_rate_ip_as' => 50,
			'max_rate_ip_cs' => 40,
			'max_rate_ip_flags' => 10,
			'max_rate_ip_logins' => 20,
			'max_rate_ip_messages' => 10,
			'max_rate_ip_qs' => 20,
			'max_rate_ip_registers' => 5,
			'max_rate_ip_uploads' => 20,
			'max_rate_ip_votes' => 600,
			'max_rate_user_as' => 25,
			'max_rate_user_cs' => 20,
			'max_rate_user_flags' => 5,
			'max_rate_user_messages' => 5,
			'max_rate_user_qs' => 10,
			'max_rate_user_uploads' => 10,
			'max_rate_user_votes' => 300,
			'max_store_user_updates' => 50,
			'min_len_a_content' => 12,
			'min_len_c_content' => 12,
			'min_len_q_content' => 0,
			'min_len_q_title' => 12,
			'min_num_q_tags' => 0,
			'moderate_notify_admin' => 1,
			'moderate_points_limit' => 150,
			'moderate_update_time' => 1,
			'nav_ask' => 1,
			'nav_qa_not_home' => 1,
			'nav_questions' => 1,
			'nav_tags' => 1,
			'nav_unanswered' => 1,
			'nav_users' => 1,
			'neat_urls' => QA_URL_FORMAT_SAFEST,
			'notify_users_default' => 1,
			'page_size_activity' => 20,
			'page_size_ask_check_qs' => 5,
			'page_size_ask_tags' => 5,
			'page_size_home' => 20,
			'page_size_hot_qs' => 20,
			'page_size_q_as' => 10,
			'page_size_qs' => 20,
			'page_size_related_qs' => 5,
			'page_size_search' => 10,
			'page_size_tag_qs' => 20,
			'page_size_tags' => 30,
			'page_size_una_qs' => 20,
			'page_size_users' => 20,
			'page_size_wall' => 10,
			'pages_prev_next' => 3,
			'permit_anon_view_ips' => QA_PERMIT_EDITORS,
			'permit_close_q' => QA_PERMIT_EDITORS,
			'permit_delete_hidden' => QA_PERMIT_MODERATORS,
			'permit_edit_a' => QA_PERMIT_EXPERTS,
			'permit_edit_c' => QA_PERMIT_EDITORS,
			'permit_edit_q' => QA_PERMIT_EDITORS,
			'permit_edit_silent' => QA_PERMIT_MODERATORS,
			'permit_flag' => QA_PERMIT_CONFIRMED,
			'permit_hide_show' => QA_PERMIT_EDITORS,
			'permit_moderate' => QA_PERMIT_EXPERTS,
			'permit_post_wall' => QA_PERMIT_CONFIRMED,
			'permit_select_a' => QA_PERMIT_EXPERTS,
			'permit_view_q_page' => QA_PERMIT_ALL,
			'permit_view_voters_flaggers' => QA_PERMIT_ADMINS,
			'permit_vote_a' => QA_PERMIT_USERS,
			'permit_vote_down' => QA_PERMIT_USERS,
			'permit_vote_q' => QA_PERMIT_USERS,
			'points_a_selected' => 30,
			'points_a_voted_max_gain' => 20,
			'points_a_voted_max_loss' => 5,
			'points_base' => 100,
			'points_multiple' => 10,
			'points_post_a' => 4,
			'points_post_q' => 2,
			'points_q_voted_max_gain' => 10,
			'points_q_voted_max_loss' => 3,
			'points_select_a' => 3,
			'q_urls_title_length' => 50,
			'show_a_c_links' => 1,
			'show_a_form_immediate' => 'if_no_as',
			'show_c_reply_buttons' => 1,
			'show_custom_welcome' => 1,
			'show_fewer_cs_count' => 5,
			'show_fewer_cs_from' => 10,
			'show_full_date_days' => 7,
			'show_message_history' => 1,
			'show_selected_first' => 1,
			'show_url_links' => 1,
			'show_user_points' => 1,
			'show_user_titles' => 1,
			'show_when_created' => 1,
			'site_theme' => 'Snow',
			'smtp_port' => 25,
			'sort_answers_by' => 'created',
			'tags_or_categories' => 'tc',
			'voting_on_as' => 1,
			'voting_on_qs' => 1,
		);
		
		if (isset($fixed_defaults[$name]))
			$value=$fixed_defaults[$name];
			
		else
			switch ($name) {
				case 'site_url':
					$value='http://'.@$_SERVER['HTTP_HOST'].strtr(dirname($_SERVER['SCRIPT_NAME']), '\\', '/').'/';
					break;
					
				case 'site_title':
					$value=qa_default_site_title();
					break;
					
				case 'site_theme_mobile':
					$value=qa_opt('site_theme');
					break;
					
				case 'from_email': // heuristic to remove short prefix (e.g. www. or qa.)
					$parts=explode('.', @$_SERVER['HTTP_HOST']);
					
					if ( (count($parts)>2) && (strlen($parts[0])<5) && !is_numeric($parts[0]) )
						unset($parts[0]);
						
					$value='no-reply@'.((count($parts)>1) ? implode('.', $parts) : 'example.com');
					break;
					
				case 'email_privacy':
					$value=qa_lang_html('options/default_privacy');
					break;
				
				case 'show_custom_sidebar':
					$value=strlen(qa_opt('custom_sidebar')) ? true : false;
					break;
					
				case 'show_custom_header':
					$value=strlen(qa_opt('custom_header')) ? true : false;
					break;
					
				case 'show_custom_footer':
					$value=strlen(qa_opt('custom_footer')) ? true : false;
					break;
					
				case 'show_custom_in_head':
					$value=strlen(qa_opt('custom_in_head')) ? true : false;
					break;
					
				case 'custom_sidebar':
					$value=qa_lang_html_sub('options/default_sidebar', qa_html(qa_opt('site_title')));
					break;
					
				case 'editor_for_qs':
				case 'editor_for_as':
					require_once QA_INCLUDE_DIR.'qa-app-format.php';
					
					$value='-'; // to match none by default, i.e. choose based on who is best at editing HTML
					qa_load_editor('', 'html', $value);
					break;
				
				case 'permit_post_q': // convert from deprecated option if available
					$value=qa_opt('ask_needs_login') ? QA_PERMIT_USERS : QA_PERMIT_ALL;
					break;
					
				case 'permit_post_a': // convert from deprecated option if available
					$value=qa_opt('answer_needs_login') ? QA_PERMIT_USERS : QA_PERMIT_ALL;
					break;
				
				case 'permit_post_c': // convert from deprecated option if available
					$value=qa_opt('comment_needs_login') ? QA_PERMIT_USERS : QA_PERMIT_ALL;
					break;
					
				case 'permit_retag_cat': // convert from previous option that used to contain it too
					$value=qa_opt('permit_edit_q');
					break;
					
				case 'points_vote_up_q':
				case 'points_vote_down_q':
					$oldvalue=qa_opt('points_vote_on_q');
					$value=is_numeric($oldvalue) ? $oldvalue : 1;
					break;
					
				case 'points_vote_up_a':
				case 'points_vote_down_a':
					$oldvalue=qa_opt('points_vote_on_a');
					$value=is_numeric($oldvalue) ? $oldvalue : 1;
					break;
					
				case 'points_per_q_voted_up':
				case 'points_per_q_voted_down':
					$oldvalue=qa_opt('points_per_q_voted');
					$value=is_numeric($oldvalue) ? $oldvalue : 1;
					break;
					
				case 'points_per_a_voted_up':
				case 'points_per_a_voted_down':
					$oldvalue=qa_opt('points_per_a_voted');
					$value=is_numeric($oldvalue) ? $oldvalue : 2;
					break;
					
				case 'captcha_module':
					$captchamodules=qa_list_modules('captcha');
					if (count($captchamodules))
						$value=reset($captchamodules);
					else
						$value='';
					break;
				
				case 'mailing_from_name':
					$value=qa_opt('site_title');
					break;
					
				case 'mailing_from_email':
					$value=qa_opt('from_email');
					break;
				
				case 'mailing_subject':
					$value=qa_lang_sub('options/default_subject', qa_opt('site_title'));
					break;
				
				case 'mailing_body':
					$value="\n\n\n--\n".qa_opt('site_title')."\n".qa_opt('site_url');
					break;
					
				case 'form_security_salt':
					require_once QA_INCLUDE_DIR.'qa-util-string.php';
					$value=qa_random_alphanum(32);
					break;
				
				default: // call option_default method in any registered modules
					$moduletypes=qa_list_module_types();
					
					foreach ($moduletypes as $moduletype) {
						$modules=qa_load_modules_with($moduletype, 'option_default');
						
						foreach ($modules as $module) {
							$value=$module->option_default($name);
							if (strlen($value))
								return $value;
						}
					}

					$value='';
					break;
			}
		
		return $value;
	}

	
	function qa_default_site_title()
/*
	Return a heuristic guess at the name of the site from the HTTP HOST
*/
	{
		$parts=explode('.', @$_SERVER['HTTP_HOST']);

		$longestpart='';
		foreach ($parts as $part)
			if (strlen($part)>strlen($longestpart))
				$longestpart=$part;
			
		return ((strlen($longestpart)>3) ? (ucfirst($longestpart).' ') : '').qa_lang('options/default_suffix');
	}

	
	function qa_post_html_defaults($basetype, $full=false)
/*
	Return an array of defaults for the $options parameter passed to qa_post_html_fields() and its ilk for posts of $basetype='Q'/'A'/'C'
	Set $full to true if these posts will be viewed in full, i.e. on a question page rather than a question listing
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		return array(
			'tagsview' => ($basetype=='Q') && qa_using_tags(),
			'categoryview' => ($basetype=='Q') && qa_using_categories(),
			'contentview' => $full,
			'voteview' => qa_get_vote_view($basetype, $full),
			'flagsview' => qa_opt('flagging_of_posts') && $full,
			'favoritedview' => true,
			'answersview' => $basetype=='Q',
			'viewsview' => ($basetype=='Q') && qa_opt('do_count_q_views') && ($full ? qa_opt('show_view_count_q_page') : qa_opt('show_view_counts')),
			'whatview' => true,
			'whatlink' => qa_opt('show_a_c_links'),
			'whenview' => qa_opt('show_when_created'),
			'ipview' => !qa_user_permit_error('permit_anon_view_ips'),
			'whoview' => true,
			'avatarsize' => qa_opt('avatar_q_list_size'),
			'pointsview' => qa_opt('show_user_points'),
			'pointstitle' => qa_opt('show_user_titles') ? qa_get_points_to_titles() : array(),
			'updateview' => true,
			'blockwordspreg' => qa_get_block_words_preg(),
			'showurllinks' => qa_opt('show_url_links'),
			'linksnewwindow' => qa_opt('links_in_new_window'),
			'microformats' => $full,
			'fulldatedays' => qa_opt('show_full_date_days'),
		);
	}
	
	
	function qa_post_html_options($post, $defaults=null, $full=false)
/*
	Return an array of options for post $post to pass in the $options parameter to qa_post_html_fields() and its ilk. Preferably,
	call qa_post_html_defaults() previously and pass its output in $defaults, to save excessive recalculation for each item in a
	list. Set $full to true if these posts will be viewed in full, i.e. on a question page rather than a question listing.	
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if (!isset($defaults))
			$defaults=qa_post_html_defaults($post['basetype'], $full);
			
		$defaults['voteview']=qa_get_vote_view($post, $full);
		$defaults['ipview']=!qa_user_post_permit_error('permit_anon_view_ips', $post);
		
		return $defaults;
	}
	
	
	function qa_message_html_defaults()
/*
	Return an array of defaults for the $options parameter passed to qa_message_html_fields()
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return array(
			'whenview' => qa_opt('show_when_created'),
			'whoview' => true,
			'avatarsize' => qa_opt('avatar_message_list_size'),
			'blockwordspreg' => qa_get_block_words_preg(),
			'showurllinks' => qa_opt('show_url_links'),
			'linksnewwindow' => qa_opt('links_in_new_window'),
			'fulldatedays' => qa_opt('show_full_date_days'),
		);
	}
	

	function qa_get_vote_view($postorbasetype, $full=false, $enabledif=true)
/*
	Return $voteview parameter to pass to qa_post_html_fields() in qa-app-format.php for the post in $postorbasetype
	with buttons enabled if appropriate (based on whether $full post shown) unless $enabledif is false.
	For compatibility $postorbasetype can also just be a basetype, i.e. 'Q', 'A' or 'C'
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		// The 'level' and 'approve' permission errors are taken care of by disabling the voting buttons.
		// Others are reported to the user after they click, in qa_vote_error_html(...)
		
		if (is_array($postorbasetype)) { // deal with dual-use parameter
			$basetype=$postorbasetype['basetype'];
			$post=$postorbasetype;
		
		} else {
			$basetype=$postorbasetype;
			$post=null;
		}
		
		$disabledsuffix='';
		
		if (($basetype=='Q') || ($basetype=='A')) {
			$view=($basetype=='A') ? qa_opt('voting_on_as') : qa_opt('voting_on_qs');
			
			if (!($enabledif && (($basetype=='A') || $full || !qa_opt('voting_on_q_page_only'))))
				$disabledsuffix='-disabled-page';
			
			else {
				if ($basetype=='A')
					$permiterror=isset($post) ? qa_user_post_permit_error('permit_vote_a', $post) : qa_user_permit_error('permit_vote_a');
				else
					$permiterror=isset($post) ? qa_user_post_permit_error('permit_vote_q', $post) : qa_user_permit_error('permit_vote_q');
				
				if ($permiterror=='level')
					$disabledsuffix='-disabled-level';
				elseif ($permiterror=='approve')
					$disabledsuffix='-disabled-approve';

				else {
					$permiterrordown=isset($post) ? qa_user_post_permit_error('permit_vote_down', $post) : qa_user_permit_error('permit_vote_down');
				
					if ($permiterrordown=='level')
						$disabledsuffix='-uponly-level';
					elseif ($permiterrordown=='approve')
						$disabledsuffix='-uponly-approve';
				}
			}

		} else
			$view=false;
		
		return $view ? ( (qa_opt('votes_separated') ? 'updown' : 'net').$disabledsuffix ) : false;
	}
	
	
	function qa_has_custom_home()
/*
	Returns true if the home page has been customized, either due to admin setting, or $QA_CONST_PATH_MAP
*/
	{
		return qa_opt('show_custom_home') || (array_search('', qa_get_request_map())!==false);
	}
	
	
	function qa_using_tags()
/*
	Return whether the option is set to classify questions by tags
*/
	{
		return strpos(qa_opt('tags_or_categories'), 't')!==false;
	}
	
	
	function qa_using_categories()
/*
	Return whether the option is set to classify questions by categories
*/
	{
		return strpos(qa_opt('tags_or_categories'), 'c')!==false;
	}
	
	
	function qa_get_block_words_preg()
/*
	Return the regular expression fragment to match the blocked words options set in the database
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_blockwordspreg, $qa_blockwordspreg_set;
		
		if (!@$qa_blockwordspreg_set) {
			$blockwordstring=qa_opt('block_bad_words');
			
			if (strlen($blockwordstring)) {
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				$qa_blockwordspreg=qa_block_words_to_preg($blockwordstring);

			} else
				$qa_blockwordspreg=null;
			
			$qa_blockwordspreg_set=true;
		}
		
		return $qa_blockwordspreg;
	}
	
	
	function qa_get_points_to_titles()
/*
	Return an array of [points] => [user title] from the 'points_to_titles' option, to pass to qa_get_points_title_html()
*/
	{
		global $qa_points_title_cache;
		
		if (!is_array($qa_points_title_cache)) {
			$qa_points_title_cache=array();
	
			$pairs=explode(',', qa_opt('points_to_titles'));
			foreach ($pairs as $pair) {
				$spacepos=strpos($pair, ' ');
				if (is_numeric($spacepos)) {
					$points=trim(substr($pair, 0, $spacepos));
					$title=trim(substr($pair, $spacepos));
	
					if (is_numeric($points) && strlen($title))
						$qa_points_title_cache[(int)$points]=$title;
				}
			}
			
			krsort($qa_points_title_cache, SORT_NUMERIC);
		}
		
		return $qa_points_title_cache;
	}
	
	
	function qa_get_permit_options()
/*
	Return an array of relevant permissions settings, based on other options
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$permits=array('permit_view_q_page', 'permit_post_q', 'permit_post_a');
		
		if (qa_opt('comment_on_qs') || qa_opt('comment_on_as'))
			$permits[]='permit_post_c';
		
		if (qa_opt('voting_on_qs'))
			$permits[]='permit_vote_q';
			
		if (qa_opt('voting_on_as'))
			$permits[]='permit_vote_a';
			
		if (qa_opt('voting_on_qs') || qa_opt('voting_on_as'))
			$permits[]='permit_vote_down';
			
		if (qa_using_tags() || qa_using_categories())
			$permits[]='permit_retag_cat';
		
		array_push($permits, 'permit_edit_q', 'permit_edit_a');
		
		if (qa_opt('comment_on_qs') || qa_opt('comment_on_as'))
			$permits[]='permit_edit_c';
			
		$permits[]='permit_edit_silent';
		
		if (qa_opt('allow_close_questions'))
			$permits[]='permit_close_q';
		
		array_push($permits, 'permit_select_a', 'permit_anon_view_ips');
		
		if (qa_opt('voting_on_qs') || qa_opt('voting_on_as') || qa_opt('flagging_of_posts'))
			$permits[]='permit_view_voters_flaggers';
		
		if (qa_opt('flagging_of_posts'))
			$permits[]='permit_flag';
		
		$permits[]='permit_moderate';

		array_push($permits, 'permit_hide_show', 'permit_delete_hidden');
		
		if (qa_opt('allow_user_walls'))
			$permits[]='permit_post_wall';
		
		return $permits;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/