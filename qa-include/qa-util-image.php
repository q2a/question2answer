<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-image.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Some useful image-related functions (using GD)


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


	function qa_has_gd_image()
/*
	Return true if PHP has the GD extension installed and it appears to be usable
*/
	{
		return extension_loaded('gd') && function_exists('imagecreatefromstring') && function_exists('imagejpeg');
	}


	function qa_image_file_too_big($imagefile, $size=null)
/*
	Check if the image in $imagefile will be too big for PHP/GD to process given memory usage and limits
	Pass the width and height limit beyond which the image will require scaling in $size (if any)
	Returns false if the image will fit fine, otherwise a safe estimate of the factor the image should be sized by
*/
	{
		if (function_exists('memory_get_usage')) {
			$gotbytes=trim(@ini_get('memory_limit'));
			
			switch (strtolower(substr($gotbytes, -1))) {
				case 'g':
					$gotbytes*=1024;
				case 'm':
					$gotbytes*=1024;
				case 'k':
					$gotbytes*=1024;
			}
			
			if ($gotbytes>0) { // otherwise we clearly don't know our limit
				$gotbytes=($gotbytes-memory_get_usage())*0.9; // safety margin of 10%
				
				$needbytes=filesize($imagefile); // memory to store file contents

				$imagesize=@getimagesize($imagefile);
				
				if (is_array($imagesize)) { // if image can't be parsed, don't worry about anything else
					$width=$imagesize[0];
					$height=$imagesize[1];
					$bits=isset($imagesize['bits']) ? $imagesize['bits'] : 8; // these elements can be missing (PHP bug) so assume this as default
					$channels=isset($imagesize['channels']) ? $imagesize['channels'] : 3; // for more info: http://gynvael.coldwind.pl/?id=223
					
					$needbytes+=$width*$height*$bits*$channels/8*2; // memory to load original image
					
					if (isset($size) && qa_image_constrain($width, $height, $size)) // memory for constrained image
						$needbytes+=$width*$height*3*2; // *2 here and above based on empirical tests
				}
				
				if ($needbytes>$gotbytes)
					return sqrt($gotbytes/($needbytes*1.5)); // additional 50% safety margin since JPEG quality may change
			}
		}
		
		return false;
	}
	
	
	function qa_image_constrain_data($imagedata, &$width, &$height, $maxwidth, $maxheight=null)
/*
	Given $imagedata containing JPEG/GIF/PNG data, constrain it proportionally to fit in $maxwidth x $maxheight.
	Return the new image data (will always be a JPEG), and set the $width and $height variables.
	If $maxheight is omitted or set to null, assume it to be the same as $maxwidth.
*/
	{
		$inimage=@imagecreatefromstring($imagedata);
		
		if (is_resource($inimage)) {
			$width=imagesx($inimage);
			$height=imagesy($inimage);
			
			// always call qa_gd_image_resize(), even if the size is the same, to take care of possible PNG transparency
			qa_image_constrain($width, $height, $maxwidth, $maxheight);
			qa_gd_image_resize($inimage, $width, $height);
		}
		
		if (is_resource($inimage)) {
			$imagedata=qa_gd_image_jpeg($inimage);
			imagedestroy($inimage);
			return $imagedata;
		}
		
		return null;	
	}
	
	
	function qa_image_constrain(&$width, &$height, $maxwidth, $maxheight=null)
/*
	Given and $width and $height, return true if those need to be contrained to fit in $maxwidth x $maxheight.
	If so, also set $width and $height to the new proportionally constrained values.
	If $maxheight is omitted or set to null, assume it to be the same as $maxwidth.
*/
	{
		if (!isset($maxheight))
			$maxheight=$maxwidth;
		
		if (($width>$maxwidth) || ($height>$maxheight)) {
			$multiplier=min($maxwidth/$width, $maxheight/$height);
			$width=floor($width*$multiplier);
			$height=floor($height*$multiplier);

			return true;
		}
		
		return false;
	}
	
	
	function qa_gd_image_resize(&$image, $width, $height)
/*
	Resize the GD $image to $width and $height, setting it to null if the resize failed
*/
	{
		$oldimage=$image;
		$image=null;

		$newimage=imagecreatetruecolor($width, $height);
		$white=imagecolorallocate($newimage, 255, 255, 255); // fill with white first in case we have a transparent PNG
		imagefill($newimage, 0, 0, $white);

		if (is_resource($newimage)) {
			if (imagecopyresampled($newimage, $oldimage, 0, 0, 0, 0, $width, $height, imagesx($oldimage), imagesy($oldimage)))
				$image=$newimage;
			else
				imagedestroy($newimage);
		}	

		imagedestroy($oldimage);
	}
	
	
	function qa_gd_image_jpeg($image, $output=false)
/*
	Return the JPEG data for GD $image, also echoing it to browser if $output is true
*/
	{
		ob_start();
		imagejpeg($image, null, 90);
		return $output ? ob_get_flush() : ob_get_clean();
	}
	
	
	function qa_gd_image_formats()
/*
	Return an array of strings listing the image formats that are supported
*/
	{
		$imagetypebits=imagetypes();
		
		$bitstrings=array(
			IMG_GIF => 'GIF',
			IMG_JPG => 'JPG',
			IMG_PNG => 'PNG',
		);
		
		foreach (array_keys($bitstrings) as $bit)
			if (!($imagetypebits&$bit))
				unset($bitstrings[$bit]);
				
		return $bitstrings;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/