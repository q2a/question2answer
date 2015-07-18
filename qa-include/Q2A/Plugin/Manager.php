<?php

class Q2A_Plugin_Manager
{
	private $tables;  // Used for performance
	private $metadataUtil; // Used for performance

	private $pluginsInitialized = array();
	private $pluginsPendingDbInitialization = array();

	public function __construct()
	{
		$this->tables = qa_db_list_tables();
		$this->metadataUtil = new Q2A_Util_Metadata();
	}

	public function registerAllPlugins()
	{
		$this->registerPluginsFromPath(QA_INCLUDE_DIR . 'plugins');
		$this->registerPluginsFromPath(QA_PLUGIN_DIR . '*');

		$this->executePostInitialization();
	}

	private function registerPluginsFromPath($path)
	{
		$pluginfiles = glob($path . '/*_Plugin.php');

		foreach ($pluginfiles as $pluginFile) {
			$pluginDirectory = dirname($pluginFile);
			$metadata = $this->metadataUtil->fetchFromAddonPath($pluginDirectory);
			if ($this->hasValidMetadata($metadata))
				$this->registerPlugin($pluginFile, $pluginDirectory);
		}
	}

	private function registerPlugin($pluginFile, $pluginDirectory)
	{
		$pluginDirectory .= '/';
		$urlToRoot = qa_path_to_root() . substr($pluginDirectory, strlen(QA_BASE_DIR));

		require_once $pluginFile;

		$className = basename($pluginFile, '.php');

		$pluginInstance = new $className($pluginDirectory, $urlToRoot);
		$pluginId = $pluginInstance->getId();
		if ($pluginInstance->requiresDatabaseInitialization($this->tables))
			$this->pluginsPendingDbInitialization[$pluginId] = $pluginInstance;
		else {
			$pluginInstance->initialization();
			$this->pluginsInitialized[$pluginId] = $pluginInstance;
		}
	}

	private function hasValidMetadata($metadata)
	{
		// skip plugin which requires a later version of Q2A
		if (isset($metadata['min_q2a']) && qa_qa_version_below($metadata['min_q2a']))
			return false;
		// skip plugin which requires a later version of PHP
		if (isset($metadata['min_php']) && qa_php_version_below($metadata['min_php']))
			return false;
		return true;
	}

	private function executePostInitialization()
	{
		foreach ($this->pluginsInitialized as $plugin)
			$plugin->postInitialization();
	}

	public function getPluginsPendingDatabaseInitialization()
	{
		// Check if plugins have not been installed. Properly done with an observer pattern
		$this->refreshPluginsPendingDatabaseInitialization();

		return $this->pluginsPendingDbInitialization;
	}

	public function getPlugin($id)
	{
		if (isset($this->pluginsInitialized[$id]))
			return $this->pluginsInitialized[$id];
		elseif (isset($this->pluginsPendingDbInitialization[$id]))
			return $this->pluginsPendingDbInitialization[$id];
		throw new PluginNotFoundException();
	}

	public function getModuleById($moduleId)
	{
		$pluginId = Q2A_Plugin_BasePlugin::getPluginIdFromModuleId($moduleId);
		$plugin = $this->getPlugin($pluginId);
		return $plugin->getModuleById($moduleId);
	}

	public function getPluginsWithModulesByType($type)
	{
		$plugins = array();
		foreach ($this->pluginsInitialized as $plugin) {
			$modules = $plugin->getModulesByType($type);
			if (!empty($modules))
				$plugins[] = $plugin;
		}
		return $plugins;
	}

	public function getModulesByType($type)
	{
		$modules = array();
		foreach ($this->pluginsInitialized as $plugin) {
			$pluginModules = $plugin->getModulesByType($type);
			$modules = array_merge($modules, $pluginModules);
		}
		return $modules;
	}

	private function refreshPluginsPendingDatabaseInitialization()
	{
		$this->tables = qa_db_list_tables();
		$initializedPluginIndexes = array();

		foreach ($this->pluginsPendingDbInitialization as $index => $plugin) {
			if (!$plugin->requiresDatabaseInitialization($this->tables))
				$initializedPluginIndexes[] = $index;
		}

		foreach ($initializedPluginIndexes as $index)
			unset($this->pluginsPendingDbInitialization[$index]);
	}
}

class PluginNotFoundException extends Exception {}