<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-metas.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Database-level access to metas tables


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


	function qa_db_usermeta_set($userid, $key, $value)
/*
	Set the metadata for user $userid with $key to $value. Keys beginning qa_ are reserved for the Q2A core.
*/
	{
		qa_db_meta_set('usermetas', 'userid', $userid, $key, $value);
	}

	
	function qa_db_usermeta_clear($userid, $key)
/*
	Clear the metadata for user $userid with $key ($key can also be an array of keys)
*/
	{
		qa_db_meta_clear('usermetas', 'userid', $userid, $key);
	}

	
	function qa_db_usermeta_get($userid, $key)
/*
	Return the metadata value for user $userid with $key ($key can also be an array of keys in which case this
	returns an array of metadata key => value).
*/
	{
		return qa_db_meta_get('usermetas', 'userid', $userid, $key);
	}
	

	function qa_db_postmeta_set($postid, $key, $value)
/*
	Set the metadata for post $postid with $key to $value. Keys beginning qa_ are reserved for the Q2A core.
*/
	{
		qa_db_meta_set('postmetas', 'postid', $postid, $key, $value);
	}

	
	function qa_db_postmeta_clear($postid, $key)
/*
	Clear the metadata for post $postid with $key ($key can also be an array of keys)
*/
	{
		qa_db_meta_clear('postmetas', 'postid', $postid, $key);
	}

	
	function qa_db_postmeta_get($postid, $key)
/*
	Return the metadata value for post $postid with $key ($key can also be an array of keys in which case this
	returns an array of metadata key => value).
*/
	{
		return qa_db_meta_get('postmetas', 'postid', $postid, $key);
	}


	function qa_db_categorymeta_set($categoryid, $key, $value)
/*
	Set the metadata for category $categoryid with $key to $value. Keys beginning qa_ are reserved for the Q2A core.
*/
	{
		qa_db_meta_set('categorymetas', 'categoryid', $categoryid, $key, $value);
	}

	
	function qa_db_categorymeta_clear($categoryid, $key)
/*
	Clear the metadata for category $categoryid with $key ($key can also be an array of keys)
*/
	{
		qa_db_meta_clear('categorymetas', 'categoryid', $categoryid, $key);
	}

	
	function qa_db_categorymeta_get($categoryid, $key)
/*
	Return the metadata value for category $categoryid with $key ($key can also be an array of keys in which
	case this returns an array of metadata key => value).
*/
	{
		return qa_db_meta_get('categorymetas', 'categoryid', $categoryid, $key);
	}
	

	function qa_db_tagmeta_set($tag, $key, $value)
/*
	Set the metadata for tag $tag with $key to $value. Keys beginning qa_ are reserved for the Q2A core.
*/
	{
		qa_db_meta_set('tagmetas', 'tag', $tag, $key, $value);
	}

	
	function qa_db_tagmeta_clear($tag, $key)
/*
	Clear the metadata for tag $tag with $key ($key can also be an array of keys)
*/
	{
		qa_db_meta_clear('tagmetas', 'tag', $tag, $key);
	}

	
	function qa_db_tagmeta_get($tag, $key)
/*
	Return the metadata value for tag $tag with $key ($key can also be an array of keys in which case this
	returns an array of metadata key => value).
*/
	{
		return qa_db_meta_get('tagmetas', 'tag', $tag, $key);
	}


	function qa_db_meta_set($metatable, $idcolumn, $idvalue, $title, $content)	
/*
	Internal general function to set metadata
*/
	{
		qa_db_query_sub(
			'REPLACE ^'.$metatable.' ('.$idcolumn.', title, content) VALUES ($, $, $)',
			$idvalue, $title, $content
		);
	}

	
	function qa_db_meta_clear($metatable, $idcolumn, $idvalue, $title)
/*
	Internal general function to clear metadata
*/
	{
		if (is_array($title)) {
			if (count($title))
				qa_db_query_sub(
					'DELETE FROM ^'.$metatable.' WHERE '.$idcolumn.'=$ AND title IN ($)',
					$idvalue, $title
				);
			
		} else
			qa_db_query_sub(
				'DELETE FROM ^'.$metatable.' WHERE '.$idcolumn.'=$ AND title=$',
				$idvalue, $title
			);
	}

	
	function qa_db_meta_get($metatable, $idcolumn, $idvalue, $title)
/*
	Internal general function to return metadata
*/
	{
		if (is_array($title)) {
			if (count($title))
				return qa_db_read_all_assoc(qa_db_query_sub(
					'SELECT title, content FROM ^'.$metatable.' WHERE '.$idcolumn.'=$ AND title IN($)',
					$idvalue, $title
				), 'title', 'content');
			else
				return array();
		
		} else
			return qa_db_read_one_value(qa_db_query_sub(
				'SELECT content FROM ^'.$metatable.' WHERE '.$idcolumn.'=$ AND title=$',
				$idvalue, $title
			), true);
	}

	
/*
	Omit PHP closing tag to help avoid accidental output
*/