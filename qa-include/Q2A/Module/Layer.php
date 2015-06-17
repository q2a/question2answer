<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Module/Layer.php
	Description: Some useful metadata handling stuff


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

class Q2A_Module_Layer
{

	protected $htmlPrinter;
	protected $content;
	protected $directory;
	protected $urlToRoot;

	public function __construct($htmlPrinter, &$content, $directory, $urlToRoot)
	{
		$this->htmlPrinter = $htmlPrinter;
		$this->content = &$content;
		$this->directory = $directory;
		$this->urlToRoot = $urlToRoot;
	}

}
