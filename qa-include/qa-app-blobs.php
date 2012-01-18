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
	
	
	function qa_get_max_upload_size()
/*
	Return the maximum size of file that can be uploaded, based on database and PHP limits
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$mindb=16777215; // from MEDIUMBLOB column type
		
		$minphp=trim(ini_get('upload_max_filesize'));
		
		switch (strtolower(substr($minphp, -1))) {
			case 'g':
				$minphp*=1024;
			case 'm':
				$minphp*=1024;
			case 'k':
				$minphp*=1024;
		}
		
		return min($mindb, $minphp);
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/