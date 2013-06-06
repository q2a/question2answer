<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-updates.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Definitions relating to favorites and updates in the database tables


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


//	Character codes for the different types of entity that can be followed (entitytype columns)

	define('QA_ENTITY_QUESTION', 'Q');
	define('QA_ENTITY_USER', 'U');
	define('QA_ENTITY_TAG', 'T');
	define('QA_ENTITY_CATEGORY', 'C');
	define('QA_ENTITY_NONE', '-');
	

//	Character codes for the different types of updates on a post (updatetype columns)

	define('QA_UPDATE_CATEGORY', 'A'); // questions only, category changed
	define('QA_UPDATE_CLOSED', 'C'); // questions only, closed or reopened
	define('QA_UPDATE_CONTENT', 'E'); // title or content edited
	define('QA_UPDATE_PARENT', 'M'); // e.g. comment moved when converting its parent answer to a comment
	define('QA_UPDATE_SELECTED', 'S'); // answers only, removed if unselected
	define('QA_UPDATE_TAGS', 'T'); // questions only
	define('QA_UPDATE_TYPE', 'Y'); // e.g. answer to comment
	define('QA_UPDATE_VISIBLE', 'H'); // hidden or reshown


//	Character codes for types of update that only appear in the streams tables, not on the posts themselves
	
	define('QA_UPDATE_FOLLOWS', 'F'); // if a new question was asked related to one of its answers, or for a comment that follows another
	define('QA_UPDATE_C_FOR_Q', 'U'); // if comment created was on a question of the user whose stream this appears in
	define('QA_UPDATE_C_FOR_A', 'N'); // if comment created was on an answer of the user whose stream this appears in

/*
	Omit PHP closing tag to help avoid accidental output
*/