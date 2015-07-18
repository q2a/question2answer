<?php

abstract class Q2A_Plugin_Module_Widget extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'widget';
	}

	public function isAllowedInTemplate($template)
	{
		return true;
	}

	public function isAllowedInRegion($region)
	{
		return true;
	}

	public abstract function getDisplayName();

	public abstract function output($region, $place, $themeObject, $template, &$qa_content);
}