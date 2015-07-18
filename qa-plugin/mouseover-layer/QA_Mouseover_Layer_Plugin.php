<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/mouseover-layer/qa-plugin.php
	Description: Initiates mouseover layer plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class QA_Mouseover_Layer_Plugin extends Q2A_Plugin_BasePlugin
{
	public function getId()
	{
		return 'QA_Mouseover_Layer_Plugin';
	}

	public function initialization() {
		$this->registerModule('QA_Mouseover_Layer_Settings.php');
		$this->registerLayer('qa-mouseover-layer.php', 'Mouseover Layer');
	}
}

