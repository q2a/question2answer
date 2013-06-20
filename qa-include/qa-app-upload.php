<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-upload.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Application-level file upload functionality


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
	
	
	function qa_upload_file($localfilename, $sourcefilename, $maxfilesize=null, $onlyimage=false, $imagemaxwidth=null, $imagemaxheight=null)
/*
	Move an uploaded image or other file into blob storage. Pass the $localfilename where the file is currently stored
	(temporarily) and the $sourcefilename of the file on the user's computer (if using PHP's usual file upload
	mechanism, these are obtained from $_FILES[..]['tmp_name'] and $_FILES[..]['name'] fields respectively). To apply a
	maximum file size (in bytes) beyond the general one, use $maxfilesize, otherwise set it to null. Set $onlyimage to
	true if only image uploads (PNG, GIF, JPEG) are allowed. To apply a maximum width or height (in pixels) to uploaded
	images, set $imagemaxwidth and $imagemaxheight. The function returns an array which may contain the following elements:

	'error' => a string containing an error, if one occurred
	'format' => the format (file extension) of the blob created (all scaled images end up as 'jpeg')
	'width' => if an image, the width in pixels of the blob created (after possible scaling)
	'height' => if an image, the height in pixels of the blob created (after possible scaling)
	'blobid' => the blobid that was created (if there was no error)
	'bloburl' => the url that can be used to view/download the created blob (if there was no error)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$result=array();
		
	//	Check per-user upload limits
		
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';
		
		switch (qa_user_permit_error(null, QA_LIMIT_UPLOADS))
		{
			case 'limit':
				$result['error']=qa_lang('main/upload_limit');
				return $result;
			
			case false:
				qa_limits_increment(qa_get_logged_in_userid(), QA_LIMIT_UPLOADS);
				break;

			default:
				$result['error']=qa_lang('users/no_permission');
				return $result;
		}
		
	//	Check the uploaded file is not too large
	
		$filesize=filesize($localfilename);
		if (isset($maxfilesize))
			$maxfilesize=min($maxfilesize, qa_get_max_upload_size());
		else
			$maxfilesize=qa_get_max_upload_size();
		
		if ( ($filesize<=0) || ($filesize>$maxfilesize) ) { // if file was too big for PHP, $filesize will be zero
			$result['error']=qa_lang_sub('main/max_upload_size_x', number_format($maxfilesize/1048576, 1).'MB');
			return $result;
		}
		
	//	Find out what type of source file was uploaded and if appropriate, check it's an image and get preliminary size measure

		$pathinfo=pathinfo($sourcefilename);
		$format=strtolower(@$pathinfo['extension']);
		$isimage=($format=='png') || ($format=='gif') || ($format=='jpeg') || ($format=='jpg'); // allowed image extensions
		
		if ($isimage) {
			$imagesize=@getimagesize($localfilename);

			if (is_array($imagesize)) {
				$result['width']=$imagesize[0];
				$result['height']=$imagesize[1];
				
				switch ($imagesize['2']) { // reassign format based on actual content, if we can
					case IMAGETYPE_GIF:
						$format='gif';
						break;
						
					case IMAGETYPE_JPEG:
						$format='jpeg';
						break;
						
					case IMAGETYPE_PNG:
						$format='png';
						break;
				}
			}
		}
		
		$result['format']=$format;
			
		if ($onlyimage)
			if ( (!$isimage) || !is_array($imagesize) ) {
				$result['error']=qa_lang_sub('main/image_not_read', 'GIF, JPG, PNG');
				return $result;
			}
			
	//	Read in the raw file contents
	
		$content=file_get_contents($localfilename);
	
	//	If appropriate, get more accurate image size and apply constraints to it
		
		require_once QA_INCLUDE_DIR.'qa-util-image.php';

		if ($isimage && qa_has_gd_image()) {
			$image=@imagecreatefromstring($content);
		
			if (is_resource($image)) {
				$result['width']=$width=imagesx($image);
				$result['height']=$height=imagesy($image);
				
				if (isset($imagemaxwidth) || isset($imagemaxheight))
					if (qa_image_constrain(
						$width, $height,
						isset($imagemaxwidth) ? $imagemaxwidth : $width,
						isset($imagemaxheight) ? $imagemaxheight : $height
					)) {
						qa_gd_image_resize($image, $width, $height);

						if (is_resource($image)) {
							$content=qa_gd_image_jpeg($image);
							$result['format']=$format='jpeg';
							$result['width']=$width;
							$result['height']=$height;
						}
					}
					
				if (is_resource($image)) // might have been lost
					imagedestroy($image);
			}
		}
		
	//	Create the blob and return
	
		require_once QA_INCLUDE_DIR.'qa-app-blobs.php';

		$userid=qa_get_logged_in_userid();
		$cookieid=isset($userid) ? qa_cookie_get() : qa_cookie_get_create();
		$result['blobid']=qa_create_blob($content, $format, $sourcefilename, $userid, $cookieid, qa_remote_ip_address());
		
		if (!isset($result['blobid'])) {
			$result['error']=qa_lang('main/general_error');
			return $result;
		}
		
		$result['bloburl']=qa_get_blob_url($result['blobid'], true);
	
		return $result;		
	}
	

	function qa_upload_file_one($maxfilesize=null, $onlyimage=false, $imagemaxwidth=null, $imagemaxheight=null)
/*
	In response to a file upload, move the first uploaded file into blob storage. Other parameters are as for qa_upload_file(...)
*/
	{
		$file=reset($_FILES);
		
		return qa_upload_file($file['tmp_name'], $file['name'], $maxfilesize, $onlyimage, $imagemaxwidth, $imagemaxheight);
	}
	
	
/*
	Omit PHP closing tag to help avoid accidental output
*/