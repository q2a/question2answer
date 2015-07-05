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

	global $pluginManager;

	$plugins = $pluginManager->getPluginsWithModulesByType('settings');

//	Prepare content for theme

	$qa_content = qa_content_prepare();

	$qa_content['title'] = qa_lang_html('admin/admin_title') . ' - ' . qa_lang_html('admin/plugins_title');

	$qa_content['error'] = qa_admin_page_error();

	$qa_content['script_rel'][] = 'qa-content/qa-admin.js?'.QA_VERSION;

	$pluginsPendingDbInitialization = $pluginManager->getPluginsPendingDatabaseInitialization();
	if (!empty($pluginsPendingDbInitialization)) {
		$plugin = reset($pluginsPendingDbInitialization);
		$pluginId = qa_html($plugin->getId());
		if (qa_is_http_post())
			qa_redirect('install');
		else {
			$qa_content['error'] = strtr(qa_lang_html('admin/plugin_x_database_init'), array(
				'^1' => $pluginId,
				'^2' => '<a href="'.qa_path_html('install').'">',
				'^3' => '</a>',
			));
		}
	}

	if ( qa_is_http_post() && !qa_check_form_security_code('admin/plugins', qa_post_text('qa_form_security_code')) ) {
		$qa_content['error'] = qa_lang_html('misc/form_security_reload');
		$showpluginforms = false;
	}
	else
		$showpluginforms = true;

	if (!empty($plugins)) {
		$metadataUtil = new Q2A_Util_Metadata();

		$sortedPlugins = array();
		foreach ($plugins as $plugin) {
			$directory = $plugin->getPluginDirectory();
			$metadata = $metadataUtil->fetchFromAddonPath($directory);
			$metadata['name'] = isset($metadata['name']) && !empty($metadata['name'])
				? qa_html($metadata['name'])
				: qa_lang_html('admin/unnamed_plugin')
			;
			$sortedPlugins[$plugin->getId()] = $metadata;
		}
		qa_sort_by($sortedPlugins, 'name');

		$pluginIndex = -1;
		foreach ($sortedPlugins as $pluginId => $metadata) {
			$pluginIndex++;
			$plugin = $pluginManager->getPlugin($pluginId);
			$pluginDirectory = $plugin->getPluginDirectory();
			$pluginIdHtml = qa_html($pluginId);
			$showthisform = $showpluginforms && qa_get('show') === $pluginIdHtml;

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
				$elementid = 'version_check_' . $pluginIdHtml;

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

			if (!$showthisform)
				$deschtml .= (strlen($deschtml) ? ' - ' : '').'<a href="'.
					qa_admin_plugin_options_path($pluginId).'">'.qa_lang_html('admin/options').'</a>';

			$pluginhtml = $namehtml.' '.$authorhtml.' '.$updatehtml.'<br>'.$deschtml.(strlen($deschtml) ? '<br>' : '').
				'<small style="color:#666">'.qa_html($pluginDirectory).'</small>';

			if (qa_qa_version_below(@$metadata['min_q2a']))
				$pluginhtml = '<strike style="color:#999">'.$pluginhtml.'</strike><br><span style="color:#f00">'.
					qa_lang_html_sub('admin/requires_q2a_version', qa_html($metadata['min_q2a'])).'</span>';

			elseif (qa_php_version_below(@$metadata['min_php']))
				$pluginhtml = '<strike style="color:#999">'.$pluginhtml.'</strike><br><span style="color:#f00">'.
					qa_lang_html_sub('admin/requires_php_version', qa_html($metadata['min_php'])).'</span>';

			$qa_content['form_plugin_'.$pluginIndex] = array(
				'tags' => sprintf('id="%s"', $pluginIdHtml),
				'style' => 'tall',
				'fields' => array(
					array(
						'type' => 'custom',
						'html' => $pluginhtml,
					)
				),
			);
			if ($showthisform) {
				$form = $plugin->getSettingsForm($qa_content);

				if (isset($form)) {
					if (!isset($form['tags']))
						$form['tags'] = 'method="post" action="'.qa_admin_plugin_options_path($pluginId).'"';

					if (!isset($form['style']))
						$form['style'] = 'tall';

					$form['boxed'] = true;

					$form['hidden']['qa_form_security_code'] = qa_get_form_security_code('admin/plugins');

					$qa_content['form_plugin_options'] = $form;
				}
			}

		}
	}

	$qa_content['navigation']['sub'] = qa_admin_sub_navigation();


	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/