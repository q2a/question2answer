<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

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

namespace Q2A\Controllers\Admin;

use Q2A\Controllers\BaseController;
use Q2A\Database\DbConnection;
use Q2A\Middleware\Auth\MinimumUserLevel;

/**
 * Controller for admin page listing plugins and showing their options.
 */
class Plugins extends BaseController
{
	public function __construct(DbConnection $db)
	{
		require_once QA_INCLUDE_DIR . 'app/admin.php';

		parent::__construct($db);

		$this->addMiddleware(new MinimumUserLevel(QA_USER_LEVEL_ADMIN));
	}

	public function index()
	{
		// Prepare content for theme

		$qa_content = qa_content_prepare();

		$qa_content['title'] = qa_lang_html('admin/admin_title') . ' - ' . qa_lang_html('admin/plugins_title');

		$qa_content['error'] = qa_admin_page_error();

		$qa_content['script_rel'][] = 'qa-content/qa-admin.js?' . QA_VERSION;


		$pluginManager = new \Q2A\Plugin\PluginManager();
		$pluginManager->cleanRemovedPlugins();

		$enabledPlugins = $pluginManager->getEnabledPlugins();
		$fileSystemPlugins = $pluginManager->getFilesystemPlugins();

		$pluginHashes = $pluginManager->getHashesForPlugins($fileSystemPlugins);

		$showpluginforms = true;
		if (qa_is_http_post()) {
			if (!qa_check_form_security_code('admin/plugins', qa_post_text('qa_form_security_code'))) {
				$qa_content['error'] = qa_lang_html('misc/form_security_reload');
				$showpluginforms = false;
			} else {
				if (qa_clicked('dosave')) {
					$enabledPluginHashes = qa_post_text('enabled_plugins_hashes');
					$enabledPluginHashesArray = explode(';', $enabledPluginHashes);
					$pluginDirectories = array_keys(array_intersect($pluginHashes, $enabledPluginHashesArray));
					$pluginManager->setEnabledPlugins($pluginDirectories);
					qa_redirect('admin/plugins');
				} elseif (qa_clicked('doversioncheck')) {
					$pluginManager->performUpdateCheck(0);
					qa_redirect('admin/plugins');
				}
			}
		}

		// Map modules with options to their containing plugins

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

		foreach ($moduletypes as $type) {
			$modules = qa_load_modules_with($type, 'init_queries');

			foreach ($modules as $name => $module) {
				$queries = $module->init_queries($tables);

				if (!empty($queries)) {
					if (qa_is_http_post()) {
						qa_redirect('install');
					} else {
						$qa_content['error'] = strtr(qa_lang_html('admin/module_x_database_init'), array(
							'^1' => qa_html($name),
							'^2' => qa_html($type),
							'^3' => '<a href="' . qa_path_html('install') . '">',
							'^4' => '</a>',
						));
					}
				}
			}
		}


		if (!empty($fileSystemPlugins)) {
			$metadataUtil = new \Q2A\Util\Metadata();
			$sortedPluginFiles = array();

			foreach ($fileSystemPlugins as $pluginDirectory) {
				$pluginDirectoryPath = QA_PLUGIN_DIR . $pluginDirectory;
				$metadata = $metadataUtil->fetchFromAddonPath($pluginDirectoryPath);
				if (empty($metadata)) {
					$pluginFile = $pluginDirectoryPath . '/qa-plugin.php';

					// limit plugin parsing to first 8kB
					$contents = file_get_contents($pluginFile, false, null, 0, 8192);
					$metadata = qa_addon_metadata($contents, 'Plugin');
				}

				$metadata['name'] = isset($metadata['name']) && !empty($metadata['name'])
					? qa_html($metadata['name'])
					: qa_lang_html('admin/unnamed_plugin');
				$sortedPluginFiles[$pluginDirectory] = $metadata;
			}

			qa_sort_by($sortedPluginFiles, 'name');

			$versionChecks = array();
			$shouldCheckForUpdate = $pluginManager->shouldCheckForUpdate();

			$pluginIndex = -1;
			foreach ($sortedPluginFiles as $pluginDirectory => $metadata) {
				$pluginIndex++;

				$pluginDirectoryPath = QA_PLUGIN_DIR . $pluginDirectory;
				$hash = $pluginHashes[$pluginDirectory];
				$showthisform = $showpluginforms && (qa_get('show') == $hash);

				$namehtml = $metadata['name'];

				if (isset($metadata['uri']) && strlen($metadata['uri']))
					$namehtml = '<a href="' . qa_html($metadata['uri']) . '">' . $namehtml . '</a>';

				$namehtml = '<b>' . $namehtml . '</b>';

				$metaver = isset($metadata['version']) && strlen($metadata['version']);
				if ($metaver)
					$namehtml .= ' v' . qa_html($metadata['version']);

				if (isset($metadata['author']) && strlen($metadata['author'])) {
					$authorhtml = qa_html($metadata['author']);

					if (isset($metadata['author_uri']) && strlen($metadata['author_uri']))
						$authorhtml = '<a href="' . qa_html($metadata['author_uri']) . '">' . $authorhtml . '</a>';

					$authorhtml = qa_lang_html_sub('main/by_x', $authorhtml);
				} else {
					$authorhtml = '';
				}

				if ($shouldCheckForUpdate && $metaver && isset($metadata['update_uri']) && strlen($metadata['update_uri'])) {
					$elementid = 'version_check_' . md5($pluginDirectory);

					$versionChecks[] = array(
						'uri' => $metadata['update_uri'],
						'version' => $metadata['version'],
						'elem' => $elementid,
					);

					$updatehtml = '(<span id="' . $elementid . '">...</span>)';
				} else {
					$updatehtml = '';
				}

				if (isset($metadata['description']))
					$deschtml = qa_html($metadata['description']);
				else
					$deschtml = '';

				if (isset($pluginoptionmodules[$pluginDirectoryPath]) && !$showthisform) {
					$deschtml .= (strlen($deschtml) ? ' - ' : '') . '<a href="' . qa_admin_plugin_options_path($pluginDirectory) . '">' .
						qa_lang_html('admin/options') . '</a>';
				}

				$allowDisable = isset($metadata['load_order']) && $metadata['load_order'] === 'after_db_init';
				$beforeDbInit = isset($metadata['load_order']) && $metadata['load_order'] === 'before_db_init';
				$enabled = $beforeDbInit || !$allowDisable || in_array($pluginDirectory, $enabledPlugins);

				$pluginhtml = $namehtml . ' ' . $authorhtml . ' ' . $updatehtml . '<br>';
				$pluginhtml .= $deschtml . (strlen($deschtml) > 0 ? '<br>' : '');
				$pluginhtml .= '<small style="color:#666">' . qa_html($pluginDirectoryPath) . '/</small>';

				if (qa_qa_version_below(@$metadata['min_q2a'])) {
					$pluginhtml = '<s style="color:#999">'.$pluginhtml.'</s><br><span style="color:#f00">'.
						qa_lang_html_sub('admin/requires_q2a_version', qa_html($metadata['min_q2a'])).'</span>';
				} elseif (qa_php_version_below(@$metadata['min_php'])) {
					$pluginhtml = '<s style="color:#999">'.$pluginhtml.'</s><br><span style="color:#f00">'.
						qa_lang_html_sub('admin/requires_php_version', qa_html($metadata['min_php'])).'</span>';
				}

				$qa_content['form_plugin_'.$pluginIndex] = array(
					'tags' => 'id="'.qa_html($hash).'"',
					'style' => 'tall',
					'fields' => array(
						array(
							'type' => 'checkbox',
							'label' => qa_lang_html('admin/enabled'),
							'value' => $enabled,
							'tags' => sprintf('id="plugin_enabled_%s"%s', $hash, $allowDisable ? '' : ' disabled'),
						),
						array(
							'type' => 'custom',
							'html' => $pluginhtml,
						),
					),
				);

				if ($showthisform && isset($pluginoptionmodules[$pluginDirectoryPath])) {
					foreach ($pluginoptionmodules[$pluginDirectoryPath] as $pluginoptionmodule) {
						$type = $pluginoptionmodule['type'];
						$name = $pluginoptionmodule['name'];

						$module = qa_load_module($type, $name);

						$form = $module->admin_form($qa_content);

						if (!isset($form['tags']))
							$form['tags'] = 'method="post" action="' . qa_admin_plugin_options_path($pluginDirectory) . '"';

						if (!isset($form['style']))
							$form['style'] = 'tall';

						$form['boxed'] = true;

						$form['hidden']['qa_form_security_code'] = qa_get_form_security_code('admin/plugins');

						$qa_content['form_plugin_options'] = $form;
					}
				}
			}

			if ($shouldCheckForUpdate) {
				$pluginManager->performUpdateCheck();

				$qa_content['script_onloads'][] = array(
					sprintf('qa_version_check_array(%s);', json_encode($versionChecks)),
				);
			}
		}

		$qa_content['navigation']['sub'] = qa_admin_sub_navigation();

		$qa_content['form'] = array(
			'tags' => 'method="post" action="' . qa_self_html() . '" name="plugins_form" onsubmit="qa_get_enabled_plugins_hashes(); return true;"',

			'style' => 'wide',

			'buttons' => array(
				'dosave' => array(
					'tags' => 'name="dosave"',
					'label' => qa_lang_html('admin/save_options_button'),
				),
				'doversioncheck' => array(
					'tags' => 'name="doversioncheck"',
					'label' => qa_lang_html('admin/version_check'),
				),
			),

			'hidden' => array(
				'qa_form_security_code' => qa_get_form_security_code('admin/plugins'),
				'enabled_plugins_hashes' => '',
			),
		);


		return $qa_content;
	}
}
