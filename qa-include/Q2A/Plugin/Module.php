<?php

abstract class Q2A_Plugin_Module
{
	protected $plugin;
	protected $id;

	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		$this->id = Q2A_Plugin_BasePlugin::getModuleId($plugin->getId(), $this->getInternalId());
	}

	public abstract function getInternalId();

	public abstract function getType();

	public function getId()
	{
		return $this->id;
	}

	public function getPlugin()
	{
		return $this->plugin;
	}

	public function getPluginDirectory()
	{
		return $this->plugin->getPluginDirectory();
	}

	public function getPluginUrlToRoot()
	{
		return $this->plugin->getUrlToRoot();
	}

	public function initialization() {}
}