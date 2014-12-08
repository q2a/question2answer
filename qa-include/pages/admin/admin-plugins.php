<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-page-admin-plugins.php
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

	require_once QA_INCLUDE_DIR.'app/admin.php';


//	Check admin privileges

	if (!qa_admin_check_privileges($qa_content))
		return $qa_content;


//	Map modules with options to their containing plugins

	$pluginoptionmodules = array();

	$tables = qa_db_list_tables();
	$moduletypes = qa_list_module_types();

	foreach ($moduletypes as $type) {
		$modules = qa_list_modules($type);

		foreach ($modules as $name) {
			$module = qa_load_module($type, $name);

			if (method_exists($module, 'admin_form')) {
				$info = qa_get_module_info($type, $name);
				$dir = rtrim($info['directory'], '/');
				$pluginoptionmodules[$dir][] = array(
					'type' => $type,
					'name' => $name,
				);
			}
		}
	}


//	Prepare content for theme

	$qa_content = qa_content_prepare();

	$qa_content['title'] = qa_lang_html('admin/admin_title') . ' - ' . qa_lang_html('admin/plugins_title');

	$qa_content['error'] = qa_admin_page_error();

	$qa_content['script_rel'][] = 'qa-content/qa-admin.js?'.QA_VERSION;

	$pluginfiles = glob(QA_PLUGIN_DIR.'*/qa-plugin.php');

	foreach ($moduletypes as $type) {
		$modules = qa_load_modules_with($type, 'init_queries');

		foreach ($modules as $name => $module) {
			$queries = $module->init_queries($tables);

			if (!empty($queries)) {
				if (qa_is_http_post())
					qa_redirect('install');

				else {
					$qa_content['error'] = strtr(qa_lang_html('admin/module_x_database_init'), array(
						'^1' => qa_html($name),
						'^2' => qa_html($type),
						'^3' => '<a href="'.qa_path_html('install').'">',
						'^4' => '</a>',
					));
				}
			}
		}
	}

	if ( qa_is_http_post() && !qa_check_form_security_code('admin/plugins', qa_post_text('qa_form_security_code')) ) {
		$qa_content['error'] = qa_lang_html('misc/form_security_reload');
		$showpluginforms = false;
	}
	else
		$showpluginforms = true;

	if (!empty($pluginfiles)) {
		$metadataUtil = new Q2A_Util_Metadata();
		$sortedPluginFiles = array();

		foreach ($pluginfiles as $pluginFile) {
			$metadata = $metadataUtil->fetchFromAddonPath(dirname($pluginFile));
			if (empty($metadata)) {
				// limit plugin parsing to first 8kB
				$contents = file_get_contents($pluginFile, false, null, -1, 8192);
				$metadata = qa_addon_metadata($contents, 'Plugin');
			}
			$metadata['name'] = isset($metadata['name']) && !empty($metadata['name'])
				? qa_html($metadata['name'])
				: qa_lang_html('admin/unnamed_plugin')
			;
			$sortedPluginFiles[$pluginFile] = $metadata;
		}

		qa_sort_by($sortedPluginFiles, 'name');

		$pluginIndex = -1;
		foreach ($sortedPluginFiles as $pluginFile => $metadata) {
			$pluginIndex++;
			$plugindirectory = dirname($pluginFile);
			$hash = qa_admin_plugin_directory_hash($plugindirectory);
			$showthisform = $showpluginforms && (qa_get('show') == $hash);

			$namehtml = $metadata['name'];

			if (isset($metadata['uri']) && strlen($metadata['uri']))
				$namehtml = '<a href="'.qa_html($metadata['uri']).'">'.$namehtml.'</a>';

			$namehtml = '<b>'.$namehtml.'</b>';

			$metaver = isset($metadata['version']) && strlen($metadata['version']);
			if ($metaver)
				$namehtml .= ' v'.qa_html($metadata['version']);

			if (isset($metadata['author']) && strlen($metadata['author'])) {
				$authorhtml = qa_html($metadata['author']);

				if (isset($metadata['author_uri']) && strlen($metadata['author_uri']))
					$authorhtml = '<a href="'.qa_html($metadata['author_uri']).'">'.$authorhtml.'</a>';

				$authorhtml = qa_lang_html_sub('main/by_x', $authorhtml);

			}
			else
				$authorhtml = '';

			if ($metaver && isset($metadata['update_uri']) && strlen($metadata['update_uri'])) {
				$elementid = 'version_check_'.md5($plugindirectory);

				$updatehtml = '(<span id="'.$elementid.'">...</span>)';

				$qa_content['script_onloads'][] = array(
					"qa_version_check(".qa_js($metadata['update_uri']).", ".qa_js($metadata['version'], true).", ".qa_js($elementid).");"
				);

			}
			else
				$updatehtml = '';

			if (isset($metadata['description']))
				$deschtml = qa_html($metadata['description']);
			else
				$deschtml = '';

			if (isset($pluginoptionmodules[$plugindirectory]) && !$showthisform)
				$deschtml .= (strlen($deschtml) ? ' - ' : '').'<a href="'.
					qa_admin_plugin_options_path($plugindirectory).'">'.qa_lang_html('admin/options').'</a>';

			$pluginhtml = $namehtml.' '.$authorhtml.' '.$updatehtml.'<br>'.$deschtml.(strlen($deschtml) ? '<br>' : '').
				'<small style="color:#666">'.qa_html($plugindirectory).'/</small>';

			if (qa_qa_version_below(@$metadata['min_q2a']))
				$pluginhtml = '<strike style="color:#999">'.$pluginhtml.'</strike><br><span style="color:#f00">'.
					qa_lang_html_sub('admin/requires_q2a_version', qa_html($metadata['min_q2a'])).'</span>';

			elseif (qa_php_version_below(@$metadata['min_php']))
				$pluginhtml = '<strike style="color:#999">'.$pluginhtml.'</strike><br><span style="color:#f00">'.
					qa_lang_html_sub('admin/requires_php_version', qa_html($metadata['min_php'])).'</span>';

			$qa_content['form_plugin_'.$pluginIndex] = array(
				'tags' => 'id="'.qa_html($hash).'"',
				'style' => 'tall',
				'fields' => array(
					array(
						'type' => 'custom',
						'html' => $pluginhtml,
					)
				),
			);

			if ($showthisform && isset($pluginoptionmodules[$plugindirectory]))
				foreach ($pluginoptionmodules[$plugindirectory] as $pluginoptionmodule) {
					$type = $pluginoptionmodule['type'];
					$name = $pluginoptionmodule['name'];

					$module = qa_load_module($type, $name);

					$form = $module->admin_form($qa_content);

					if (!isset($form['tags']))
						$form['tags'] = 'method="post" action="'.qa_admin_plugin_options_path($plugindirectory).'"';

					if (!isset($form['style']))
						$form['style'] = 'tall';

					$form['boxed'] = true;

					$form['hidden']['qa_form_security_code'] = qa_get_form_security_code('admin/plugins');

					$qa_content['form_plugin_options'] = $form;
				}

		}
	}

	$qa_content['navigation']['sub'] = qa_admin_sub_navigation();


	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/