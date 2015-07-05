<?php

abstract class Q2A_Plugin_Module_Process extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'process';
	}

	public function initAjax() { }

	public function initBlob() { }

	public function initFeed() { }

	public function initImage() { }

	public function initInstall() { }

	public function initPage() { }

	public function dbDisconnect() { }

	public function shutdown($reason) { }

}