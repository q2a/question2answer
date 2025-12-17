<?php

/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Plugin/PluginManager.php
	Description: Keeps track of the installed plugins


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

class Q2A_Plugin_PluginManager
{
	const PLUGIN_DELIMITER = ';';
	const OPT_ENABLED_PLUGINS = 'enabled_plugins';

	private $loadBeforeDbInit = array(); //New structure: [dependency_level => [pluginKey => pluginInfo]]
	private $loadAfterDbInit = array(); //New structure: [dependency_level => [pluginKey => pluginInfo]]

	public function readAllPluginMetadatas()
	{
		$pluginDirectories = $this->getFilesystemPlugins(true);

		foreach ($pluginDirectories as $pluginDirectory) {
			$pluginFile = $pluginDirectory . 'qa-plugin.php';

			if (!file_exists($pluginFile)) {
				continue;
			}

			$metadataUtil = new Q2A_Util_Metadata();
			$metadata = $metadataUtil->fetchFromAddonPath($pluginDirectory);
			if (empty($metadata)) {
				// limit plugin parsing to first 8kB
				$contents = file_get_contents($pluginFile, false, null, 0, 8192);
				$metadata = qa_addon_metadata($contents, 'Plugin', true);
			}

			// skip plugin which requires a later version of Q2A
			if (qa_qa_version_below(isset($metadata['min_q2a']) ? $metadata['min_q2a'] : '')) {
				continue;
			}
			// skip plugin which requires a later version of PHP
			if (qa_php_version_below(isset($metadata['min_php']) ? $metadata['min_php'] : '')) {
				continue;
			}
			
			//Dependency level of the plugin useful while loading it. Lower the dependency number, loads earlier.
			$dependencyLevel = 1;
			if (isset($metadata['dependency_level']) && ctype_digit((string)$metadata['dependency_level'])) {
				$dependencyLevel = (int)$metadata['dependency_level'];
			}


			$pluginInfoKey = basename($pluginDirectory);
			$pluginInfo = array(
				'pluginfile' => $pluginFile,
				'directory' => $pluginDirectory,
				'urltoroot' => substr($pluginDirectory, strlen(QA_BASE_DIR)),
			);

			if (isset($metadata['load_order'])) {
				switch ($metadata['load_order']) {
					case 'after_db_init':
						//$this->loadAfterDbInit[$pluginInfoKey] = $pluginInfo;
						$this->loadAfterDbInit[$dependencyLevel][$pluginInfoKey] = $pluginInfo;
						break;
					case 'before_db_init':
						//$this->loadBeforeDbInit[$pluginInfoKey] = $pluginInfo;
						$this->loadBeforeDbInit[$dependencyLevel][$pluginInfoKey] = $pluginInfo;
						break;
					default:
				}
			} else {
				$this->loadBeforeDbInit[$dependencyLevel][$pluginInfoKey] = $pluginInfo;
			}
		}
	}

	//This function is not useful anymore
	private function loadPlugins($pluginInfos)
	{
		global $qa_plugin_directory, $qa_plugin_urltoroot;

		foreach ($pluginInfos as $pluginInfo) {
			$qa_plugin_directory = $pluginInfo['directory'];
			$qa_plugin_urltoroot = $pluginInfo['urltoroot'];

			require_once $pluginInfo['pluginfile'];
		}

		$qa_plugin_directory = null;
		$qa_plugin_urltoroot = null;
	}
	
	//New function to load plugins 
	private function loadPluginsByDependency(array $groupedPlugins)
	{
		global $qa_plugin_directory, $qa_plugin_urltoroot;

		if (empty($groupedPlugins)) {
			return;
		}

		ksort($groupedPlugins, SORT_NUMERIC);

		foreach ($groupedPlugins as $level => $pluginsAtLevel) {

			//SAFETY: level may accidentally contain flat array
			if (!is_array($pluginsAtLevel)) {
				continue;
			}

			foreach ($pluginsAtLevel as $pluginKey => $pluginInfo) {

				//SAFETY: pluginInfo must be an array
				if (!is_array($pluginInfo) || !isset($pluginInfo['pluginfile'])) {
					continue;
				}

				$qa_plugin_directory = $pluginInfo['directory'];
				$qa_plugin_urltoroot = $pluginInfo['urltoroot'];

				require_once $pluginInfo['pluginfile'];
			}
		}

		$qa_plugin_directory = null;
		$qa_plugin_urltoroot = null;
	}



	public function loadPluginsBeforeDbInit()
	{
		//$this->loadPlugins($this->loadBeforeDbInit);
		$this->loadPluginsByDependency($this->loadBeforeDbInit);
	}

	public function loadPluginsAfterDbInit()
	{
		$enabledPlugins = $this->getEnabledPlugins(false);
		/* Old way
		$enabledForAfterDbInit = array();

		foreach ($enabledPlugins as $enabledPluginDirectory) {
			if (isset($this->loadAfterDbInit[$enabledPluginDirectory])) {
				$enabledForAfterDbInit[$enabledPluginDirectory] = $this->loadAfterDbInit[$enabledPluginDirectory];
			}
		}

		$this->loadPlugins($enabledForAfterDbInit);
		*/
		
		$enabledGrouped = array();
		foreach ($this->loadAfterDbInit as $level => $pluginsAtLevel) {
			foreach ($pluginsAtLevel as $pluginKey => $pluginInfo) {
				if (in_array($pluginKey, $enabledPlugins, true)) {
					$enabledGrouped[$level][$pluginKey] = $pluginInfo;
				}
			}
		}
		$this->loadPluginsByDependency($enabledGrouped);
	}

	public function getEnabledPlugins($fullPath = false)
	{
		$pluginDirectories = $this->getEnabledPluginsOption();

		if ($fullPath) {
			foreach ($pluginDirectories as $key => &$pluginDirectory) {
				$pluginDirectory = QA_PLUGIN_DIR . $pluginDirectory . '/';
			}
		}

		return $pluginDirectories;
	}

	public function setEnabledPlugins($array)
	{
		$this->setEnabledPluginsOption($array);
	}

	public function getFilesystemPlugins($fullPath = false)
	{
		$result = array();

		$fileSystemPluginFiles = glob(QA_PLUGIN_DIR . '*/qa-plugin.php');

		foreach ($fileSystemPluginFiles as $pluginFile) {
			$directory = dirname($pluginFile) . '/';

			if (!$fullPath) {
				$directory = basename($directory);
			}
			$result[] = $directory;
		}

		return $result;
	}

	public function getHashesForPlugins($pluginDirectories)
	{
		$result = array();

		foreach ($pluginDirectories as $pluginDirectory) {
			$result[$pluginDirectory] = md5($pluginDirectory);
		}

		return $result;
	}

	private function getEnabledPluginsOption()
	{
		return explode(self::PLUGIN_DELIMITER, qa_opt(self::OPT_ENABLED_PLUGINS));
	}

	private function setEnabledPluginsOption($array)
	{
		qa_opt(self::OPT_ENABLED_PLUGINS, implode(self::PLUGIN_DELIMITER, $array));
	}

	public function cleanRemovedPlugins()
	{
		$finalEnabledPlugins = array_intersect(
			$this->getFilesystemPlugins(),
			$this->getEnabledPlugins()
		);

		$this->setEnabledPluginsOption($finalEnabledPlugins);
	}
}
