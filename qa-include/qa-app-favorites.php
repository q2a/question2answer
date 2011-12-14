<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-favorites.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Handles favoriting and unfavoriting (application level)


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


	function qa_user_favorite_set($userid, $handle, $cookieid, $entitytype, $entityid, $favorite)
/*
	If $favorite is true, set $entitytype and $entityid to be favorites of $userid with $handle and $cookieid, otherwise
	remove them from its favorites list. Handles event reporting.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-favorites.php';
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		if ($favorite)
			qa_db_favorite_create($userid, $entitytype, $entityid);
		else
			qa_db_favorite_delete($userid, $entitytype, $entityid);
		
		switch ($entitytype) {
			case QA_ENTITY_QUESTION:
				$action=$favorite ? 'q_favorite' : 'q_unfavorite';
				$params=array('postid' => $entityid);
				break;
			
			case QA_ENTITY_USER:
				$action=$favorite ? 'u_favorite' : 'u_unfavorite';
				$params=array('userid' => $entityid);
				break;
				
			case QA_ENTITY_TAG:
				$action=$favorite ? 'tag_favorite' : 'tag_unfavorite';
				$params=array('wordid' => $entityid);
				break;
				
			case QA_ENTITY_CATEGORY:
				$action=$favorite ? 'cat_favorite' : 'cat_unfavorite';
				$params=array('categoryid' => $entityid);
				break;
			
			default:
				qa_fatal_error('Favorite type not recognized');
				break;
		}
		
		qa_report_event($action, $userid, $handle, $cookieid, $params);
	}
	
	
/*
	Omit PHP closing tag to help avoid accidental output
*/