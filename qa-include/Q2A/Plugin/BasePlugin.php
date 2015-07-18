<?php

abstract class Q2A_Plugin_BasePlugin
{
	const PLUGIN_MODULE_SEPARATOR = '-';

	public static function getModuleId($pluginId, $moduleId)
	{
		return $pluginId . self::PLUGIN_MODULE_SEPARATOR . $moduleId;
	}

	public static function getPluginIdFromModuleId($moduleId)
	{
		return substr($moduleId, 0, strpos($moduleId, self::PLUGIN_MODULE_SEPARATOR));
	}

	protected $pluginDirectory;
	protected $urlToRoot;
	protected $modulesByType = array();
	protected $modulesById = array();

	public function __construct($pluginDirectory, $urlToRoot)
	{
		$this->pluginDirectory = $pluginDirectory;
		$this->urlToRoot = $urlToRoot;
	}

	// Would allow to make modules IDs and ^options values more "unique"
	public abstract function getId();

	public function requiresDatabaseInitialization($tables)
	{
		return false;
	}

	public function initializeDatabase($progressUpdater) {}

	public function initialization() {}

	public function postInitialization() {}

	public function getModulesByType($type)
	{
		return isset($this->modulesByType[$type]) ? $this->modulesByType[$type] : array();
	}

	public function getModuleById($id)
	{
		if (!isset($this->modulesById[$id]))
			throw new ModuleNotFoundException();
		return $this->modulesById[$id];
	}

	public function getSettingsForm(&$qa_content)
	{
		if (isset($this->modulesByType['settings'])) {
			$settingsModule = reset($this->modulesByType['settings']);
			return $settingsModule->getForm($qa_content);
		}
		return null;
	}

	public function getPluginDirectory()
	{
		return $this->pluginDirectory;
	}

	public function getUrlToRoot()
	{
		return $this->urlToRoot;
	}

	protected function registerModule($fileName)
	{
		$path = $this->pluginDirectory . $fileName;

		require_once $path;

		$className = basename($fileName, '.php');

		$module = new $className($this);

		$internalId = $module->getInternalId();
		if (strpos($internalId, self::PLUGIN_MODULE_SEPARATOR) !== false)
			qa_fatal_error(sprintf('Invalid character ("%s") found in module ID "%s".', self::PLUGIN_MODULE_SEPARATOR, $internalId));

		$type = $module->getType();
		$id = $module->getId();

		if (isset($this->modulesById[$id]))
			qa_fatal_error(sprintf('Duplicated module detected. Module type: "%s". Module ID: "%s".', $type, $id));

		if ($type === 'settings' && isset($this->modulesByType['settings']))
			qa_fatal_error(sprintf('Only one "settings" module is accepted per plugin. Module ID: "%s".', $id));

		$module->initialization();

		$this->modulesByType[$type][$id] = $module;
		$this->modulesById[$id] = $module;
	}

	protected function registerLayer($include, $name)
	{
		qa_register_layer($include, $name, $this->pluginDirectory, $this->urlToRoot);
	}

	protected function registerOverride($include)
	{
		qa_register_overrides($include, $this->pluginDirectory, $this->urlToRoot);
	}

	protected function registerPhrases($pattern, $name)
	{
		qa_register_phrases($this->pluginDirectory . $pattern, $name);
	}

}

class ModuleNotFoundException extends Exception {}