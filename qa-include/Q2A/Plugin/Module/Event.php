<?php

abstract class Q2A_Plugin_Module_Event extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'event';
	}

	public abstract function processEvent($event, $userId, $handle, $cookieId, $params);
}