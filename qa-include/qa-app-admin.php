<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-admin.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Functions used in the admin center pages


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

	
	function qa_admin_check_privileges(&$qa_content)
/*
	Return true if user is logged in with admin privileges. If not, return false
	and set up $qa_content with the appropriate title and error message
*/
	{
		if (!qa_is_logged_in()) {
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
			$qa_content=qa_content_prepare();

			$qa_content['title']=qa_lang_html('admin/admin_title');
			$qa_content['error']=qa_insert_login_links(qa_lang_html('admin/not_logged_in'), qa_request());
			
			return false;

		} elseif (qa_get_logged_in_level()<QA_USER_LEVEL_ADMIN) {
			$qa_content=qa_content_prepare();
			
			$qa_content['title']=qa_lang_html('admin/admin_title');
			$qa_content['error']=qa_lang_html('admin/no_privileges');
			
			return false;
		}
		
		return true;
	}
	
	
	function qa_admin_language_options()
/*
	Return a sorted array of available languages, [short code] => [long name]
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$codetolanguage=array( // 2-letter language codes as per ISO 639-1
			'ar' => 'Arabic - العربية',
			'bg' => 'Bulgarian - Български',
			'bn' => 'Bengali - বাংলা',
			'ca' => 'Catalan - Català',
			'cs' => 'Czech - Čeština',
			'cy' => 'Welsh - Cymraeg',
			'da' => 'Danish - Dansk',
			'de' => 'German - Deutsch',
			'el' => 'Greek - Ελληνικά',
			'en-GB' => 'English (UK)',
			'es' => 'Spanish - Español',
			'et' => 'Estonian - Eesti',
			'fa' => 'Persian - فارسی',
			'fi' => 'Finnish - Suomi',
			'fr' => 'French - Français',
			'he' => 'Hebrew - עברית',
			'hr' => 'Croatian - Hrvatski',
			'hu' => 'Hungarian - Magyar',
			'id' => 'Indonesian - Bahasa Indonesia',
			'is' => 'Icelandic - Íslenska',
			'it' => 'Italian - Italiano',
			'ja' => 'Japanese - 日本語',
			'ka' => 'Georgian - ქართული ენა',
			'kh' => 'Khmer - ភាសាខ្មែរ',
			'ko' => 'Korean - 한국어',
			'ku-CKB' => 'Kurdish Central - کورد',
			'lt' => 'Lithuanian - Lietuvių',
			'lv' => 'Latvian - Latviešu',
			'nl' => 'Dutch - Nederlands',
			'no' => 'Norwegian - Norsk',
			'pl' => 'Polish - Polski',
			'pt' => 'Portuguese - Português',
			'ro' => 'Romanian - Română',
			'ru' => 'Russian - Русский',
			'sk' => 'Slovak - Slovenčina',
			'sl' => 'Slovenian - Slovenščina',
			'sq' => 'Albanian - Shqip',
			'sr' => 'Serbian - Српски',
			'sv' => 'Swedish - Svenska',
			'th' => 'Thai - ไทย',
			'tr' => 'Turkish - Türkçe',
			'ug' => 'Uyghur - ئۇيغۇرچە',
			'uk' => 'Ukrainian - Українська',
			'vi' => 'Vietnamese - Tiếng Việt',
			'zh-TW' => 'Chinese Traditional - 繁體中文',
			'zh' => 'Chinese Simplified - 简体中文',
		);
		
		$options=array('' => 'English (US)');
		
		$directory=@opendir(QA_LANG_DIR);
		if (is_resource($directory)) {
			while (($code=readdir($directory))!==false)
				if (is_dir(QA_LANG_DIR.$code) && isset($codetolanguage[$code]))
					$options[$code]=$codetolanguage[$code];

			closedir($directory);
		}
		
		asort($options, SORT_STRING);
		
		return $options;
	}
	
	
	function qa_admin_theme_options()
/*
	Return a sorted array of available themes, [theme name] => [theme name]
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$options=array();

		$directory=@opendir(QA_THEME_DIR);
		if (is_resource($directory)) {
			while (($theme=readdir($directory))!==false)
				if ( (substr($theme, 0, 1)!='.') && file_exists(QA_THEME_DIR.$theme.'/qa-styles.css') )
					$options[$theme]=$theme;

			closedir($directory);
		}
		
		asort($options, SORT_STRING);
		
		return $options;
	}
	
	
	function qa_admin_place_options()
/*
	Return an array of widget placement options, with keys matching the database value
*/
	{
		return array(
			'FT' => qa_lang_html('options/place_full_top'),
			'FH' => qa_lang_html('options/place_full_below_nav'),
			'FL' => qa_lang_html('options/place_full_below_content'),
			'FB' => qa_lang_html('options/place_full_below_footer'),
			'MT' => qa_lang_html('options/place_main_top'),
			'MH' => qa_lang_html('options/place_main_below_title'),
			'ML' => qa_lang_html('options/place_main_below_lists'),
			'MB' => qa_lang_html('options/place_main_bottom'),
			'ST' => qa_lang_html('options/place_side_top'),
			'SH' => qa_lang_html('options/place_side_below_sidebar'),
			'SL' => qa_lang_html('options/place_side_below_categories'),
			'SB' => qa_lang_html('options/place_side_last'),
		);
	}

	
	function qa_admin_page_size_options($maximum)
/*
	Return an array of page size options up to $maximum, [page size] => [page size]
*/
	{
		$rawoptions=array(5, 10, 15, 20, 25, 30, 40, 50, 60, 80, 100, 120, 150, 200, 250, 300, 400, 500, 600, 800, 1000);
		
		$options=array();
		foreach ($rawoptions as $rawoption) {
			if ($rawoption>$maximum)
				break;
				
			$options[$rawoption]=$rawoption;
		}
		
		return $options;
	}
	
	
	function qa_admin_match_options()
/*
	Return an array of options representing matching precision, [value] => [label]
*/
	{
		return array(
			5 => qa_lang_html('options/match_5'),
			4 => qa_lang_html('options/match_4'),
			3 => qa_lang_html('options/match_3'),
			2 => qa_lang_html('options/match_2'),
			1 => qa_lang_html('options/match_1'),
		);
	}

	
	function qa_admin_permit_options($widest, $narrowest, $doconfirms=true, $dopoints=true)
/*
	Return an array of options representing permission restrictions, [value] => [label]
	ranging from $widest to $narrowest. Set $doconfirms to whether email confirmations are on
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		
		$options=array(
			QA_PERMIT_ALL => qa_lang_html('options/permit_all'),
			QA_PERMIT_USERS => qa_lang_html('options/permit_users'),
			QA_PERMIT_CONFIRMED => qa_lang_html('options/permit_confirmed'),
			QA_PERMIT_POINTS => qa_lang_html('options/permit_points'),
			QA_PERMIT_POINTS_CONFIRMED => qa_lang_html('options/permit_points_confirmed'),
			QA_PERMIT_APPROVED => qa_lang_html('options/permit_approved'),
			QA_PERMIT_APPROVED_POINTS => qa_lang_html('options/permit_approved_points'),
			QA_PERMIT_EXPERTS => qa_lang_html('options/permit_experts'),
			QA_PERMIT_EDITORS => qa_lang_html('options/permit_editors'),
			QA_PERMIT_MODERATORS => qa_lang_html('options/permit_moderators'),
			QA_PERMIT_ADMINS => qa_lang_html('options/permit_admins'),
			QA_PERMIT_SUPERS => qa_lang_html('options/permit_supers'),
		);
		
		foreach ($options as $key => $label)
			if (($key<$narrowest) || ($key>$widest))
				unset($options[$key]);
		
		if (!$doconfirms) {
			unset($options[QA_PERMIT_CONFIRMED]);
			unset($options[QA_PERMIT_POINTS_CONFIRMED]);
		}
		
		if (!$dopoints) {
			unset($options[QA_PERMIT_POINTS]);
			unset($options[QA_PERMIT_POINTS_CONFIRMED]);
			unset($options[QA_PERMIT_APPROVED_POINTS]);
		}
		
		if (QA_FINAL_EXTERNAL_USERS || !qa_opt('moderate_users')) {
			unset($options[QA_PERMIT_APPROVED]);
			unset($options[QA_PERMIT_APPROVED_POINTS]);
		}
			
		return $options;
	}

	
	function qa_admin_sub_navigation()
/*
	Return the sub navigation structure common to admin pages
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$navigation=array();
		$level=qa_get_logged_in_level();
		
		if ($level>=QA_USER_LEVEL_ADMIN) {
			$navigation['admin/general']=array(
				'label' => qa_lang_html('admin/general_title'),
				'url' => qa_path_html('admin/general'),
			);
			
			$navigation['admin/emails']=array(
				'label' => qa_lang_html('admin/emails_title'),
				'url' => qa_path_html('admin/emails'),
			);
			
			$navigation['admin/user']=array(
				'label' => qa_lang_html('admin/users_title'),
				'url' => qa_path_html('admin/users'),
			);
			
			$navigation['admin/layout']=array(
				'label' => qa_lang_html('admin/layout_title'),
				'url' => qa_path_html('admin/layout'),
			);
			
			$navigation['admin/posting']=array(
				'label' => qa_lang_html('admin/posting_title'),
				'url' => qa_path_html('admin/posting'),
			);
			
			$navigation['admin/viewing']=array(
				'label' => qa_lang_html('admin/viewing_title'),
				'url' => qa_path_html('admin/viewing'),
			);
			
			$navigation['admin/lists']=array(
				'label' => qa_lang_html('admin/lists_title'),
				'url' => qa_path_html('admin/lists'),
			);
			
			if (qa_using_categories())
				$navigation['admin/categories']=array(
					'label' => qa_lang_html('admin/categories_title'),
					'url' => qa_path_html('admin/categories'),
				);
			
			$navigation['admin/permissions']=array(
				'label' => qa_lang_html('admin/permissions_title'),
				'url' => qa_path_html('admin/permissions'),
			);
			
			$navigation['admin/pages']=array(
				'label' => qa_lang_html('admin/pages_title'),
				'url' => qa_path_html('admin/pages'),
			);
			
			$navigation['admin/feeds']=array(
				'label' => qa_lang_html('admin/feeds_title'),
				'url' => qa_path_html('admin/feeds'),
			);
			
			$navigation['admin/points']=array(
				'label' => qa_lang_html('admin/points_title'),
				'url' => qa_path_html('admin/points'),
			);
			
			$navigation['admin/spam']=array(
				'label' => qa_lang_html('admin/spam_title'),
				'url' => qa_path_html('admin/spam'),
			);

			$navigation['admin/stats']=array(
				'label' => qa_lang_html('admin/stats_title'),
				'url' => qa_path_html('admin/stats'),
			);

			if (!QA_FINAL_EXTERNAL_USERS)
				$navigation['admin/mailing']=array(
					'label' => qa_lang_html('admin/mailing_title'),
					'url' => qa_path_html('admin/mailing'),
				);
			
			$navigation['admin/plugins']=array(
				'label' => qa_lang_html('admin/plugins_title'),
				'url' => qa_path_html('admin/plugins'),
			);
		}
				
		if (!qa_user_maximum_permit_error('permit_moderate')) {
			$count=qa_user_permit_error('permit_moderate') ? null : qa_opt('cache_queuedcount'); // if only in some categories don't show cached count
			
			$navigation['admin/moderate']=array(
				'label' => qa_lang_html('admin/moderate_title').($count ? (' ('.$count.')') : ''),
				'url' => qa_path_html('admin/moderate'),
			);
		}
		
		if (qa_opt('flagging_of_posts') && !qa_user_maximum_permit_error('permit_hide_show')) {
			$count=qa_user_permit_error('permit_hide_show') ? null : qa_opt('cache_flaggedcount'); // if only in some categories don't show cached count
			
			$navigation['admin/flagged']=array(
				'label' => qa_lang_html('admin/flagged_title').($count ? (' ('.$count.')') : ''),
				'url' => qa_path_html('admin/flagged'),
			);
		}
		
		if ( (!qa_user_maximum_permit_error('permit_hide_show')) || (!qa_user_maximum_permit_error('permit_delete_hidden')) )
			$navigation['admin/hidden']=array(
				'label' => qa_lang_html('admin/hidden_title'),
				'url' => qa_path_html('admin/hidden'),
			);
		
		if ( (!QA_FINAL_EXTERNAL_USERS) && qa_opt('moderate_users') && ($level>=QA_USER_LEVEL_MODERATOR)) {
			$count=qa_opt('cache_uapprovecount');
			
			$navigation['admin/moderate-users']=array(
				'label' => qa_lang_html('admin/approve_users_title').($count ? (' ('.$count.')') : ''),
				'url' => qa_path_html('admin/approve'),
			);
		}

		return $navigation;
	}
	
	
	function qa_admin_page_error()
/*
	Return the error that needs to displayed on all admin pages, or null if none
*/
	{
		if (file_exists(QA_INCLUDE_DIR.'qa-db-install.php')) // file can be removed for extra security
			include_once QA_INCLUDE_DIR.'qa-db-install.php';
		
		if (defined('QA_DB_VERSION_CURRENT') && (qa_opt('db_version')<QA_DB_VERSION_CURRENT) && (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN))
			return strtr(
				qa_lang_html('admin/upgrade_db'),
				
				array(
					'^1' => '<a href="'.qa_path_html('install').'">',
					'^2' => '</a>',
				)
			);

		elseif (defined('QA_BLOBS_DIRECTORY') && !is_writable(QA_BLOBS_DIRECTORY))
			return qa_lang_html_sub('admin/blobs_directory_error', qa_html(QA_BLOBS_DIRECTORY));
			
		else
			return null;
	}


	function qa_admin_url_test_html()
/*
	Return an HTML fragment to display for a URL test which has passed
*/
	{
		return '; font-size:9px; color:#060; font-weight:bold; font-family:arial,sans-serif; border-color:#060;">OK<';
	}


	function qa_admin_is_slug_reserved($requestpart)
/*
	Returns whether a URL path beginning with $requestpart is reserved by the engine or a plugin page module
*/
	{
		$requestpart=trim(strtolower($requestpart));
		$routing=qa_page_routing();
		
		if (isset($routing[$requestpart]) || isset($routing[$requestpart.'/']) || is_numeric($requestpart))
			return true;
		
		$pathmap=qa_get_request_map();

		foreach ($pathmap as $mappedrequest)
			if (trim(strtolower($mappedrequest)) == $requestpart)
				return true;
			
		switch ($requestpart) {
			case '':
			case 'qa':
			case 'feed':
			case 'install':
			case 'url':
			case 'image':
			case 'ajax':
				return true;
		}
		
		$pagemodules=qa_load_modules_with('page', 'match_request');
		foreach ($pagemodules as $pagemodule)
			if ($pagemodule->match_request($requestpart))
				return true;
			
		return false;
	}
	
	
	function qa_admin_single_click($entityid, $action)
/*
	Returns true if admin (hidden/flagged/approve/moderate) page $action performed on $entityid is permitted by the
	logged in user and was processed successfully
*/
	{	
		$userid=qa_get_logged_in_userid();

		if ( (!QA_FINAL_EXTERNAL_USERS) && (($action=='userapprove') || ($action=='userblock')) ) { // approve/block moderated users
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';

			$useraccount=qa_db_select_with_pending(qa_db_user_account_selectspec($entityid, true));
			
			if ( isset($useraccount) && (qa_get_logged_in_level()>=QA_USER_LEVEL_MODERATOR) )
				switch ($action) {
					case 'userapprove':
						if ($useraccount['level']<=QA_USER_LEVEL_APPROVED) { // don't demote higher level users
							require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
							qa_set_user_level($useraccount['userid'], $useraccount['handle'], QA_USER_LEVEL_APPROVED, $useraccount['level']);
							return true;
						}
						break;
						
					case 'userblock':
						require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
						qa_set_user_blocked($useraccount['userid'], $useraccount['handle'], true);
						return true;
						break;
				}
		
		} else { // something to do with a post
			require_once QA_INCLUDE_DIR.'qa-app-posts.php';
		
			$post=qa_post_get_full($entityid);
		
			if (isset($post)) {
				$queued=(substr($post['type'], 1)=='_QUEUED');
			
				switch ($action) {
					case 'approve':
						if ($queued && !qa_user_post_permit_error('permit_moderate', $post)) {
							qa_post_set_hidden($entityid, false, $userid);
							return true;
						}
						break;
					
					case 'reject':
						if ($queued && !qa_user_post_permit_error('permit_moderate', $post)) {
							qa_post_set_hidden($entityid, true, $userid);
							return true;
						}
						break;
				
					case 'hide':
						if ((!$queued) && !qa_user_post_permit_error('permit_hide_show', $post)) {
							qa_post_set_hidden($entityid, true, $userid);
							return true;
						}						
						break;
	
					case 'reshow':
						if ($post['hidden'] && !qa_user_post_permit_error('permit_hide_show', $post)) {
							qa_post_set_hidden($entityid, false, $userid);
							return true;
						}
						break;
					
					case 'delete':
						if ($post['hidden'] && !qa_user_post_permit_error('permit_delete_hidden', $post)) {
							qa_post_delete($entityid);
							return true;
						}
						break;
				
					case 'clearflags':
						require_once QA_INCLUDE_DIR.'qa-app-votes.php';
					
						if (!qa_user_post_permit_error('permit_hide_show', $post)) {
							qa_flags_clear_all($post, $userid, qa_get_logged_in_handle(), null);
							return true;
						}
						break;
				}
			}
		}
		
		return false;
	}
	
	
	function qa_admin_check_clicks()
/*
	Checks for a POSTed click on an admin (hidden/flagged/approve/moderate) page, and refresh the page if processed successfully (non Ajax)
*/
	{
		if (qa_is_http_post())
			foreach ($_POST as $field => $value)
				if (strpos($field, 'admin_')===0) {
					@list($dummy, $entityid, $action)=explode('_', $field);
					
					if (strlen($entityid) && strlen($action)) {
						if (!qa_check_form_security_code('admin/click', qa_post_text('code')))
							return qa_lang_html('misc/form_security_again');
						elseif (qa_admin_single_click($entityid, $action))
							qa_redirect(qa_request());
					}
				}
				
		return null;
	}
	
	
	function qa_admin_addon_metadata($contents, $fields)
/*
	Retrieve metadata information from the $contents of a qa-theme.php or qa-plugin.php file, mapping via $fields
*/
	{
		$metadata=array();

		foreach ($fields as $key => $field)
			if (preg_match('/'.str_replace(' ', '[ \t]*', preg_quote($field, '/')).':[ \t]*([^\n\f]*)[\n\f]/i', $contents, $matches))
				$metadata[$key]=trim($matches[1]);
		
		return $metadata;
	}
	
	
	function qa_admin_plugin_directory_hash($directory)
/*
	Return the hash code for the plugin in $directory, used for in-page navigation on admin/plugins page
*/
	{
		return md5($directory);
	}
	
	
	function qa_admin_plugin_options_path($directory)
/*
	Return the URL (relative to the current page) to navigate to the options panel for the plugin in $directory
*/
	{
		$hash=qa_admin_plugin_directory_hash($directory);		
		return qa_path_html('admin/plugins', array('show' => $hash), null, null, $hash);
	}
	

	function qa_admin_module_options_path($type, $name)
/*
	Return the URL (relative to the current page) to navigate to the options panel for plugin module $name of $type
*/
	{
		$info=qa_get_module_info($type, $name);
		return qa_admin_plugin_options_path($info['directory']);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/