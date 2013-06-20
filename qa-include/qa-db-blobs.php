<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-blobs.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Database-level access to blobs table for large chunks of data (e.g. images)


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


	function qa_db_blob_create($content, $format, $sourcefilename=null, $userid=null, $cookieid=null, $ip=null)
/*
	Create a new blob in the database with $content and $format, other fields as provided 
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		for ($attempt=0; $attempt<10; $attempt++) {
			$blobid=qa_db_random_bigint();
			
			if (qa_db_blob_exists($blobid))
				continue;

			qa_db_query_sub(
				'INSERT INTO ^blobs (blobid, format, content, filename, userid, cookieid, createip, created) VALUES (#, $, $, $, $, #, INET_ATON($), NOW())',
				$blobid, $format, $content, $sourcefilename, $userid, $cookieid, $ip
			);
		
			return $blobid;
		}
		
		return null;
	}
	
	
	function qa_db_blob_read($blobid)
/*
	Get the information about blob $blobid from the database
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return qa_db_read_one_assoc(qa_db_query_sub(
			'SELECT content, format, filename FROM ^blobs WHERE blobid=#',
			$blobid
		), true);
	}
	
	
	function qa_db_blob_set_content($blobid, $content)
/*
	Change the content of blob $blobid in the database to $content (can also be null)
*/
	{
		qa_db_query_sub(
			'UPDATE ^blobs SET content=$ WHERE blobid=#',
			$content, $blobid
		);
	}
	
	
	function qa_db_blob_delete($blobid)
/*
	Delete blob $blobid in the database
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		qa_db_query_sub(
			'DELETE FROM ^blobs WHERE blobid=#',
			$blobid
		);
	}

	
	function qa_db_blob_exists($blobid)
/*
	Check if blob $blobid exists in the database
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^blobs WHERE blobid=#',
			$blobid
		)) > 0;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/