<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-plugins.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for admin page listing plugins and showing their options


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

	
//	Check admin privileges

	if (!qa_admin_check_privileges($qa_content))
		return $qa_content;
		
		
//	Map modules with options to their containing plugins
	
	$pluginoptionanchors=array();
	
	$tables=qa_db_list_tables_lc();
	$moduletypes=qa_list_module_types();
	
	foreach ($moduletypes as $type) {
		$modules=qa_list_modules($type);
		
		foreach ($modules as $name) {
			$module=qa_load_module($type, $name);
			
			if (method_exists($module, 'admin_form')) {
				$info=qa_get_module_info($type, $name);
				$pluginoptionanchors[$info['directory']][]=md5($type.'/'.$name);
			}
		}
	}


//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/plugins_title');
	
	$qa_content['error']=qa_admin_page_error();
	
	$qa_content['script_rel'][]='qa-content/qa-admin.js?'.QA_VERSION;

	$pluginfiles=glob(QA_PLUGIN_DIR.'*/qa-plugin.php');

	if (count($pluginfiles)) {
		$qa_content['form']=array(
			'style' => 'tall',
			
			'fields' => array(
				'plugins' => array(
					'type' => 'custom',
					'label' => qa_lang_html('admin/installed_plugins'),
					'html' => '',
				),		
			),
		);
		
		foreach ($pluginfiles as $pluginfile) {
			$plugindirectory=dirname($pluginfile).'/';
			
			$contents=file_get_contents($pluginfile);
			
			$metadata=qa_admin_addon_metadata($contents, array(
				'name' => 'Plugin Name',
				'uri' => 'Plugin URI',
				'description' => 'Plugin Description',
				'version' => 'Plugin Version',
				'date' => 'Plugin Date',
				'author' => 'Plugin Author',
				'author_uri' => 'Plugin Author URI',
				'license' => 'Plugin License',
				'min_q2a' => 'Plugin Minimum Question2Answer Version',
				'min_php' => 'Plugin Minimum PHP Version',
				'update' => 'Plugin Update Check URI',
			));
			
			if (strlen(@$metadata['name']))
				$namehtml=qa_html($metadata['name']);
			else
				$namehtml=qa_lang_html('admin/unnamed_plugin');
				
			if (strlen(@$metadata['uri']))
				$namehtml='<A HREF="'.qa_html($metadata['uri']).'">'.$namehtml.'</A>';
			
			$namehtml='<B>'.$namehtml.'</B>';
				
			if (strlen(@$metadata['version']))
				$namehtml.=' v'.qa_html($metadata['version']);
				
			if (strlen(@$metadata['author'])) {
				$authorhtml=qa_html($metadata['author']);
				
				if (strlen(@$metadata['author_uri']))
					$authorhtml='<A HREF="'.qa_html($metadata['author_uri']).'">'.$authorhtml.'</A>';
					
				$authorhtml=qa_lang_html_sub('main/by_x', $authorhtml);
				
			} else
				$authorhtml='';
				
			if (strlen(@$metadata['version']) && strlen(@$metadata['update'])) {
				$elementid='version_check_'.md5($plugindirectory);
				
				$updatehtml='(<SPAN ID="'.$elementid.'">...</SPAN>)';
				
				$qa_content['script_onloads'][]=array(
					"qa_version_check(".qa_js($metadata['update']).", 'Plugin Version', ".qa_js($metadata['version']).", 'Plugin URI', ".qa_js($elementid).");"
				);

			} else
				$updatehtml='';
			
			if (strlen(@$metadata['description']))
				$deschtml=qa_html($metadata['description']);
			else
				$deschtml='';
			
			if (isset($pluginoptionanchors[$plugindirectory]))
				foreach ($pluginoptionanchors[$plugindirectory] as $anchor)
					$deschtml.=(strlen($deschtml) ? ' - ' : '').'<A HREF="#'.qa_html($anchor).'">'.qa_lang_html('admin/options').'</A>';
				
			$pluginhtml=$namehtml.' '.$authorhtml.' '.$updatehtml.'<BR>'.$deschtml.(strlen($deschtml) ? '<BR>' : '').
				'<SMALL STYLE="color:#666">'.qa_html($plugindirectory).'</SMALL>';
				
			if (is_numeric(@$metadata['min_q2a']) && ((float)QA_VERSION>0) && $metadata['min_q2a']>(float)QA_VERSION)
				$pluginhtml='<STRIKE STYLE="color:#999">'.$pluginhtml.'</STRIKE><BR><SPAN STYLE="color:#f00">'.
					qa_lang_html_sub('admin/requires_q2a_version', qa_html($metadata['min_q2a'])).'</SPAN>';
					
			elseif (is_numeric(@$metadata['min_php']) && ((float)phpversion()>0) && $metadata['min_php']>(float)phpversion())
				$pluginhtml='<STRIKE STYLE="color:#999">'.$pluginhtml.'</STRIKE><BR><SPAN STYLE="color:#f00">'.
					qa_lang_html_sub('admin/requires_php_version', qa_html($metadata['min_php'])).'</SPAN>';
				
			$qa_content['form']['fields'][]=array(
				'type' => 'custom',
				'html' => $pluginhtml,
			);
		}
	}
	
	$formadded=false;
	
	$moduletypes=qa_list_module_types();
	
	foreach ($moduletypes as $type) {
		$modules=qa_load_modules_with($type, 'admin_form');
		
		foreach ($modules as $name => $module) {
			$form=$module->admin_form($qa_content);

			if (!isset($form['title']))
				$form['title']=qa_html($name);
			
			$identifierhtml=qa_html(md5($type.'/'.$name));
			
			$form['title']='<A NAME="'.$identifierhtml.'">'.$form['title'].'</A>';
				
			if (!isset($form['tags']))
				$form['tags']='METHOD="POST" ACTION="'.qa_self_html().'#'.$identifierhtml.'"';
			
			if (!isset($form['style']))
				$form['style']='tall';
				
			$qa_content['form_'.$type.'_'.$name]=$form;
			$formadded=true;
		}
	}
		
	foreach ($moduletypes as $type) {
		$modules=qa_load_modules_with($type, 'init_queries');

		foreach ($modules as $name => $module) {
			$queries=$module->init_queries($tables);
		
			if (!empty($queries)) {
				if (qa_is_http_post())
					qa_redirect('install');
				
				else
					$qa_content['error']=strtr(qa_lang_html('admin/module_x_database_init'), array(
						'^1' => qa_html($name),
						'^2' => qa_html($type),
						'^3' => '<A HREF="'.qa_path_html('install').'">',
						'^4' => '</A>',
					));
			}
		}
	}

	if (!$formadded)
		$qa_content['suggest_next']=qa_lang_html('admin/no_plugin_options');
	
	$qa_content['navigation']['sub']=qa_admin_sub_navigation();

	
	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/