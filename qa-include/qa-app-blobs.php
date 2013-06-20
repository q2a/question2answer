<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-blobs.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Application-level blob-management functions


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

	
	function qa_get_blob_url($blobid, $absolute=false)
/*
	Return the URL which will output $blobid from the database when requested, $absolute or relative
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return qa_path('blob', array('qa_blobid' => $blobid), $absolute ? qa_opt('site_url') : null, QA_URL_FORMAT_PARAMS);
	}
	
	
	function qa_get_blob_directory($blobid)
/*
	Return the full path to the on-disk directory for blob $blobid (subdirectories are named by the first 3 digits of $blobid)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		return rtrim(QA_BLOBS_DIRECTORY, '/').'/'.substr(str_pad($blobid, 20, '0', STR_PAD_LEFT), 0, 3);
	}
	
	
	function qa_get_blob_filename($blobid, $format)
/*
	Return the full page and filename of blob $blobid which is in $format ($format is used as the file name suffix e.g. .jpg)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		return qa_get_blob_directory($blobid).'/'.$blobid.'.'.preg_replace('/[^A-Za-z0-9]/', '', $format);
	}
	
	
	function qa_create_blob($content, $format, $sourcefilename=null, $userid=null, $cookieid=null, $ip=null)
/*
	Create a new blob (storing the content in the database or on disk as appropriate) with $content and $format, returning its blobid.
	Pass the original name of the file uploaded in $sourcefilename and the $userid, $cookieid and $ip of the user creating it
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
		
		$blobid=qa_db_blob_create(defined('QA_BLOBS_DIRECTORY') ? null : $content, $format, $sourcefilename, $userid, $cookieid, $ip);

		if (isset($blobid) && defined('QA_BLOBS_DIRECTORY'))
			if (!qa_write_blob_file($blobid, $content, $format))
				qa_db_blob_set_content($blobid, $content); // still write content to the database if writing to disk failed

		return $blobid;
	}
	
	
	function qa_write_blob_file($blobid, $content, $format)
/*
	Write the on-disk file for blob $blobid with $content and $format. Returns true if the write succeeded, false otherwise.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$written=false;
		
		$directory=qa_get_blob_directory($blobid);
		if (is_dir($directory) || mkdir($directory, fileperms(rtrim(QA_BLOBS_DIRECTORY, '/')) & 0777)) {
			$filename=qa_get_blob_filename($blobid, $format);
			
			$file=fopen($filename, 'xb');
			if (is_resource($file)) {
				if (fwrite($file, $content)>=strlen($content))
					$written=true;

				fclose($file);
				
				if (!$written)
					unlink($filename);
			}
		}
		
		return $written;	
	}
	
	
	function qa_read_blob($blobid)
/*
	Retrieve blob $blobid from the database, reading the content from disk if appropriate
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
	
		$blob=qa_db_blob_read($blobid);
		
		if (defined('QA_BLOBS_DIRECTORY') && !isset($blob['content']))
			$blob['content']=qa_read_blob_file($blobid, $blob['format']);
			
		return $blob;
	}
	
	
	function qa_read_blob_file($blobid, $format)
/*
	Read the content of blob $blobid in $format from disk. On failure, it will return false.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		return file_get_contents(qa_get_blob_filename($blobid, $format));
	}
	
	
	function qa_delete_blob($blobid)
/*
	Delete blob $blobid from the database, and remove the on-disk file if appropriate
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
	
		if (defined('QA_BLOBS_DIRECTORY')) {
			$blob=qa_db_blob_read($blobid);
			
			if (isset($blob) && !isset($blob['content']))
				unlink(qa_get_blob_filename($blobid, $blob['format']));
		}
		
		qa_db_blob_delete($blobid);
	}
	
	
	function qa_delete_blob_file($blobid, $format)
/*
	Delete the on-disk file for blob $blobid in $format
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		unlink(qa_get_blob_filename($blobid, $format));
	}
	
	
	function qa_blob_exists($blobid)
/*
	Check if blob $blobid exists
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
		
		return qa_db_blob_exists($blobid);
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/