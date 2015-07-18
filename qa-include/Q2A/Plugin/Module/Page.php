<?php

abstract class Q2A_Plugin_Module_Page extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'page';
	}

	public function getSuggestedRequests()
	{
		return array();
	}

	public abstract function matchRequest($request);

	public abstract function processRequest($request);
}