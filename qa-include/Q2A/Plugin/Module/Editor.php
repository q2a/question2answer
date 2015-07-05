<?php

abstract class Q2A_Plugin_Module_Editor extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'editor';
	}

	public function calculateQuality($content, $format)
	{
		return 1;
	}

	public function getLoadScript($fieldName)
	{
		return '';
	}

	public function getFocusScript($fieldName)
	{
		return '';
	}

	public function getUpdateScript($fieldName)
	{
		return '';
	}

	public abstract function getDisplayName(); // Used in admin/posting

	public abstract function getField(&$qa_content, $content, $format, $fieldName, $rows);

	public abstract function readPost($fieldName);
}