<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-theme/Candy/qa-theme.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Override something in base theme class for Candy theme


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

	class qa_html_theme extends qa_html_theme_base
	{
		function nav_user_search() // reverse the usual order
		{
			$this->search();
			$this->nav('user');
		}
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/