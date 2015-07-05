<?php

abstract class Q2A_Plugin_Module_Viewer extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'viewer';
	}

	public function calculateQuality($content, $format)
	{
		return 1;
	}

	public abstract function getHtml($content, $format, $options);

	public abstract function getText($content, $format, $options);
}