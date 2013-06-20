<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-stats.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for admin page showing usage statistics and clean-up buttons


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

	require_once QA_INCLUDE_DIR.'qa-db-recalc.php';
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-admin.php';

	
//	Check admin privileges (do late to allow one DB query)

	if (!qa_admin_check_privileges($qa_content))
		return $qa_content;


//	Get the information to display

	$qcount=(int)qa_opt('cache_qcount');
	$qcount_anon=qa_db_count_posts('Q', false);

	$acount=(int)qa_opt('cache_acount');
	$acount_anon=qa_db_count_posts('A', false);

	$ccount=(int)qa_opt('cache_ccount');
	$ccount_anon=qa_db_count_posts('C', false);
	

//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/stats_title');
	
	$qa_content['error']=qa_admin_page_error();

	$qa_content['form']=array(
		'style' => 'wide',
		
		'fields' => array(
			'q2a_version' => array(
				'label' => qa_lang_html('admin/q2a_version'),
				'value' => qa_html(QA_VERSION),
			),
			
			'q2a_date' => array(
				'label' => qa_lang_html('admin/q2a_build_date'),
				'value' => qa_html(QA_BUILD_DATE),
			),
			
			'q2a_latest' => array(
				'label' => qa_lang_html('admin/q2a_latest_version'),
				'type' => 'custom',
				'html' => '<iframe src="http://www.question2answer.org/question2answer-latest.php?version='.urlencode(QA_VERSION).'&language='.urlencode(qa_opt('site_language')).
					'" width="100" height="16" style="vertical-align:middle; border:0; background:transparent;" allowTransparency="true" scrolling="no" frameborder="0"></iframe>',
			),
			
			'break0' => array(
				'type' => 'blank',
			),
			
			'db_version' => array(
				'label' => qa_lang_html('admin/q2a_db_version'),
				'value' => qa_html(qa_opt('db_version')),
			),
			
			'db_size' => array(
				'label' => qa_lang_html('admin/q2a_db_size'),
				'value' => qa_html(number_format(qa_db_table_size()/1048576, 1).' MB'),
			),
			
			'break1' => array(
				'type' => 'blank',
			),
			
			'php_version' => array(
				'label' => qa_lang_html('admin/php_version'),
				'value' => qa_html(phpversion()),
			),
			
			'mysql_version' => array(
				'label' => qa_lang_html('admin/mysql_version'),
				'value' => qa_html(qa_db_mysql_version()),
			),
			
			'break2' => array(
				'type' => 'blank',
			),
	
			'qcount' => array(
				'label' => qa_lang_html('admin/total_qs'),
				'value' => qa_html(number_format($qcount)),
			),
			
			'qcount_users' => array(
				'label' => qa_lang_html('admin/from_users'),
				'value' => qa_html(number_format($qcount-$qcount_anon)),
			),
	
			'qcount_anon' => array(
				'label' => qa_lang_html('admin/from_anon'),
				'value' => qa_html(number_format($qcount_anon)),
			),
			
			'break3' => array(
				'type' => 'blank',
			),
	
			'acount' => array(
				'label' => qa_lang_html('admin/total_as'),
				'value' => qa_html(number_format($acount)),
			),
	
			'acount_users' => array(
				'label' => qa_lang_html('admin/from_users'),
				'value' => qa_html(number_format($acount-$acount_anon)),
			),
	
			'acount_anon' => array(
				'label' => qa_lang_html('admin/from_anon'),
				'value' => qa_html(number_format($acount_anon)),
			),
			
			'break4' => array(
				'type' => 'blank',
			),
			
			'ccount' => array(
				'label' => qa_lang_html('admin/total_cs'),
				'value' => qa_html(number_format($ccount)),
			),
	
			'ccount_users' => array(
				'label' => qa_lang_html('admin/from_users'),
				'value' => qa_html(number_format($ccount-$ccount_anon)),
			),
	
			'ccount_anon' => array(
				'label' => qa_lang_html('admin/from_anon'),
				'value' => qa_html(number_format($ccount_anon)),
			),
			
			'break5' => array(
				'type' => 'blank',
			),
			
			'users' => array(
				'label' => qa_lang_html('admin/users_registered'),
				'value' => QA_FINAL_EXTERNAL_USERS ? '' : qa_html(number_format(qa_db_count_users())),
			),
	
			'users_active' => array(
				'label' => qa_lang_html('admin/users_active'),
				'value' => qa_html(number_format((int)qa_opt('cache_userpointscount'))),
			),
			
			'users_posted' => array(
				'label' => qa_lang_html('admin/users_posted'),
				'value' => qa_html(number_format(qa_db_count_active_users('posts'))),
			),
	
			'users_voted' => array(
				'label' => qa_lang_html('admin/users_voted'),
				'value' => qa_html(number_format(qa_db_count_active_users('uservotes'))),
			),
		),
	);
	
	if (QA_FINAL_EXTERNAL_USERS)
		unset($qa_content['form']['fields']['users']);
	else
		unset($qa_content['form']['fields']['users_active']);

	foreach ($qa_content['form']['fields'] as $index => $field)
		if (empty($field['type']))
			$qa_content['form']['fields'][$index]['type']='static';
	
	$qa_content['form_2']=array(
		'tags' => 'method="post" action="'.qa_path_html('admin/recalc').'"',
		
		'title' => qa_lang_html('admin/database_cleanup'),
		
		'style' => 'basic',
		
		'buttons' => array(
			'recount_posts' => array(
				'label' => qa_lang_html('admin/recount_posts'),
				'tags' => 'name="dorecountposts" onclick="return qa_recalc_click(this.name, this, '.qa_js(qa_lang('admin/recount_posts_stop')).', \'recount_posts_note\');"',
				'note' => '<span id="recount_posts_note">'.qa_lang_html('admin/recount_posts_note').'</span>',
			),
	
			'reindex_content' => array(
				'label' => qa_lang_html('admin/reindex_content'),
				'tags' => 'name="doreindexcontent" onclick="return qa_recalc_click(this.name, this, '.qa_js(qa_lang('admin/reindex_content_stop')).', \'reindex_content_note\');"',
				'note' => '<span id="reindex_content_note">'.qa_lang_html('admin/reindex_content_note').'</span>',
			),
			
			'recalc_points' => array(
				'label' => qa_lang_html('admin/recalc_points'),
				'tags' => 'name="dorecalcpoints" onclick="return qa_recalc_click(this.name, this, '.qa_js(qa_lang('admin/recalc_stop')).', \'recalc_points_note\');"',
				'note' => '<span id="recalc_points_note">'.qa_lang_html('admin/recalc_points_note').'</span>',
			),
			
			'refill_events' => array(
				'label' => qa_lang_html('admin/refill_events'),
				'tags' => 'name="dorefillevents" onclick="return qa_recalc_click(this.name, this, '.qa_js(qa_lang('admin/recalc_stop')).', \'refill_events_note\');"',
				'note' => '<span id="refill_events_note">'.qa_lang_html('admin/refill_events_note').'</span>',
			),
			
			'recalc_categories' => array(
				'label' => qa_lang_html('admin/recalc_categories'),
				'tags' => 'name="dorecalccategories" onclick="return qa_recalc_click(this.name, this, '.qa_js(qa_lang('admin/recalc_stop')).', \'recalc_categories_note\');"',
				'note' => '<span id="recalc_categories_note">'.qa_lang_html('admin/recalc_categories_note').'</span>',
			),
			
			'delete_hidden' => array(
				'label' => qa_lang_html('admin/delete_hidden'),
				'tags' => 'name="dodeletehidden" onclick="return qa_recalc_click(this.name, this, '.qa_js(qa_lang('admin/delete_stop')).', \'delete_hidden_note\');"',
				'note' => '<span id="delete_hidden_note">'.qa_lang_html('admin/delete_hidden_note').'</span>',
			),
		),
		
		'hidden' => array(
			'code' => qa_get_form_security_code('admin/recalc'),
		),
	);
	
	if (!qa_using_categories())
		unset($qa_content['form_2']['buttons']['recalc_categories']);
	
	if (defined('QA_BLOBS_DIRECTORY')) {
		if (qa_db_has_blobs_in_db())
			$qa_content['form_2']['buttons']['blobs_to_disk']=array(
				'label' => qa_lang_html('admin/blobs_to_disk'),
				'tags' => 'name="doblobstodisk" onclick="return qa_recalc_click(this.name, this, '.qa_js(qa_lang('admin/blobs_stop')).', \'blobs_to_disk_note\');"',
				'note' => '<span id="blobs_to_disk_note">'.qa_lang_html('admin/blobs_to_disk_note').'</span>',
			);
		
		if (qa_db_has_blobs_on_disk())
			$qa_content['form_2']['buttons']['blobs_to_db']=array(
				'label' => qa_lang_html('admin/blobs_to_db'),
				'tags' => 'name="doblobstodb" onclick="return qa_recalc_click(this.name, this, '.qa_js(qa_lang('admin/blobs_stop')).', \'blobs_to_db_note\');"',
				'note' => '<span id="blobs_to_db_note">'.qa_lang_html('admin/blobs_to_db_note').'</span>',
			);
	}

	
	$qa_content['script_rel'][]='qa-content/qa-admin.js?'.QA_VERSION;
	$qa_content['script_var']['qa_warning_recalc']=qa_lang('admin/stop_recalc_warning');

	$qa_content['navigation']['sub']=qa_admin_sub_navigation();

	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/