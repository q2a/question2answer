<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-userfields.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for admin page for editing custom user fields


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

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';

	
//	Get current list of user fields and determine the state of this admin page

	$fieldid=qa_post_text('edit');
	if (!isset($fieldid))
		$fieldid=qa_get('edit');
		
	$userfields=qa_db_select_with_pending(qa_db_userfields_selectspec());

	$editfield=null;
	foreach ($userfields as $userfield)
		if ($userfield['fieldid']==$fieldid)
			$editfield=$userfield;
	

//	Check admin privileges (do late to allow one DB query)

	if (!qa_admin_check_privileges($qa_content))
		return $qa_content;
		
		
//	Process saving an old or new user field

	if (qa_clicked('docancel'))
		qa_redirect('admin/users');

	elseif (qa_clicked('dosavefield')) {
		require_once QA_INCLUDE_DIR.'qa-db-admin.php';
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		if (qa_post_text('dodelete')) {
			qa_db_userfield_delete($editfield['fieldid']);
			qa_redirect('admin/users');
		
		} else {
			$inname=qa_post_text('name');
			$inflags=qa_post_text('flags');
			$inposition=qa_post_text('position');

			$errors=array();
			
		//	Verify the name is legitimate
		
			if (qa_strlen($inname)>QA_DB_MAX_PROFILE_TITLE_LENGTH)
				$errors['name']=qa_lang_sub('main/max_length_x', QA_DB_MAX_PROFILE_TITLE_LENGTH);

		//	Perform appropriate database action
	
			if (isset($editfield['fieldid'])) { // changing existing user field
				qa_db_userfield_set_fields($editfield['fieldid'], isset($errors['name']) ? $editfield['content'] : $inname, $inflags);
				qa_db_userfield_move($editfield['fieldid'], $inposition);
				
				if (empty($errors))
					qa_redirect('admin/users');

				else {
					$userfields=qa_db_select_with_pending(qa_db_userfields_selectspec()); // reload after changes
					foreach ($userfields as $userfield)
						if ($userfield['fieldid']==$editfield['fieldid'])
							$editfield=$userfield;
				}
	
			} elseif (empty($errors)) { // creating a new user field

				for ($attempt=0; $attempt<1000; $attempt++) {
					$suffix=$attempt ? ('-'.(1+$attempt)) : '';
					$newtag=qa_substr(implode('-', qa_string_to_words($inname)), 0, QA_DB_MAX_PROFILE_TITLE_LENGTH-strlen($suffix)).$suffix;
					$uniquetag=true;
				
					foreach ($userfields as $userfield)	
						if (qa_strtolower(trim($newtag)) == qa_strtolower(trim($userfield['title'])))
							$uniquetag=false;
							
					if ($uniquetag) {
						$fieldid=qa_db_userfield_create($newtag, $inname, $inflags);
						qa_db_userfield_move($fieldid, $inposition);
						qa_redirect('admin/users');
					}
				}
				
				qa_fatal_error('Could not create a unique database tag');
			}
		}
	}
	
		
//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/users_title');
	
	$qa_content['error']=qa_admin_page_error();

	$positionoptions=array();
	$previous=null;
	$passedself=false;
	
	foreach ($userfields as $userfield) {
		if (isset($previous))
			$positionhtml=qa_lang_html_sub('admin/after_x', qa_html(qa_user_userfield_label($passedself ? $userfield : $previous)));
		else
			$positionhtml=qa_lang_html('admin/first');
				
		$positionoptions[$userfield['position']]=$positionhtml;
			
		if ($userfield['fieldid']==@$editfield['fieldid'])
			$passedself=true;

		$previous=$userfield;
	}
	
	if (isset($editfield['position']))
		$positionvalue=$positionoptions[$editfield['position']];
	else {
		$positionvalue=isset($previous) ? qa_lang_html_sub('admin/after_x', qa_html(qa_user_userfield_label($previous))) : qa_lang_html('admin/first');
		$positionoptions[1+@max(array_keys($positionoptions))]=$positionvalue;
	}
	
	$typeoptions=array(
		0 => qa_lang_html('admin/field_single_line'),
		QA_FIELD_FLAGS_MULTI_LINE => qa_lang_html('admin/field_multi_line'),
		QA_FIELD_FLAGS_LINK_URL => qa_lang_html('admin/field_link_url'),
	);
	
	$qa_content['form']=array(
		'tags' => 'METHOD="POST" ACTION="'.qa_path_html(qa_request()).'"',
		
		'style' => 'tall',
		
		'fields' => array(
			'name' => array(
				'tags' => 'NAME="name" ID="name"',
				'label' => qa_lang_html('admin/field_name'),
				'value' => qa_html(isset($inname) ? $inname : qa_user_userfield_label($editfield)),
				'error' => qa_html(@$errors['name']),
			),
			
			'type' => array(
				'id' => 'type_display',
				'tags' => 'NAME="flags"',
				'label' => qa_lang_html('admin/field_type'),
				'type' => 'select',
				'options' => $typeoptions,
				'value' => @$typeoptions[isset($inflags) ? $inflags : @$editfield['flags']],
			),
			
			'delete' => array(
				'tags' => 'NAME="dodelete" ID="dodelete"',
				'label' => qa_lang_html('admin/delete_field'),
				'value' => 0,
				'type' => 'checkbox',
			),
			
			'position' => array(
				'id' => 'position_display',
				'tags' => 'NAME="position"',
				'label' => qa_lang_html('admin/position'),
				'type' => 'select',
				'options' => $positionoptions,
				'value' => $positionvalue,
			),
		),

		'buttons' => array(
			'save' => array(
				'label' => qa_lang_html(isset($editfield['fieldid']) ? 'main/save_button' : ('admin/add_field_button')),
			),
			
			'cancel' => array(
				'tags' => 'NAME="docancel"',
				'label' => qa_lang_html('main/cancel_button'),
			),
		),
		
		'hidden' => array(
			'dosavefield' => '1', // for IE
			'edit' => @$editfield['fieldid'],
		),
	);
	
	if (isset($editfield['fieldid']))
		qa_set_display_rules($qa_content, array(
			'position_display' => '!dodelete',
		));
	else
		unset($qa_content['form']['fields']['delete']);
	
	$qa_content['focusid']='name';

	$qa_content['navigation']['sub']=qa_admin_sub_navigation();

	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/