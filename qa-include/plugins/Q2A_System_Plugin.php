<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/basic-adsense/qa-plugin.php
	Description: Initiates Adsense widget plugin


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

/*
	Plugin Name: Basic AdSense
	Plugin URI:
	Plugin Description: Provides a basic widget for displaying Google AdSense ads
	Plugin Version: 1.0
	Plugin Date: 2011-03-27
	Plugin Author: Question2Answer
	Plugin Author URI: http://www.question2answer.org/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.4
	Plugin Update Check URI:
*/

class Q2A_System_Plugin extends Q2A_Plugin_BasePlugin
{
	public function getId() {
		return 'Q2A_System_Plugin';
	}

	public function initialization() {
		$this->registerModule('Q2A_Editor_Basic.php');
		$this->registerModule('Q2A_Event_Limits.php');
		$this->registerModule('Q2A_Event_Notify.php');
		$this->registerModule('Q2A_Event_Updates.php');
		$this->registerModule('Q2A_Filter_Basic.php');
		$this->registerModule('Q2A_Search_Basic.php');
		$this->registerModule('Q2A_Viewer_Basic.php');
		$this->registerModule('Q2A_Widget_Activity_Count.php');
		$this->registerModule('Q2A_Widget_Ask_Box.php');
		$this->registerModule('Q2A_Widget_Related_QS.php');
	}

}


