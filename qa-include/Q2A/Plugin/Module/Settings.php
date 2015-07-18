<?php

abstract class Q2A_Plugin_Module_Settings extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'settings';
	}

	public function getDefaultValue($option) { }

	public function getForm(&$qa_content) { }
}