<?php

abstract class Q2A_Plugin_Module_Login extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'login';
	}

	public function matchSource($source)
	{
		return true;
	}

	public function checkLogin() { }

	public function loginHtml($toUrl, $context) { }

	public function logoutHtml($toUrl) { }
}