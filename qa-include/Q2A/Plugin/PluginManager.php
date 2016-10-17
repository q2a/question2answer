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

	public function getEnabledPlugins($fullPath = false)
	{
		$pluginDirectories = $this->getEnabledPluginsOption();

		if ($fullPath) {
			foreach ($pluginDirectories as $key => &$pluginDirectory)
				$pluginDirectory = QA_PLUGIN_DIR . $pluginDirectory . '/';
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
			$directory = dirname($pluginFile);

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
