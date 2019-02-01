<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

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
	header('Location: ../../');
	exit;
}


/**
 * Return the URL which will output $blobid from the database when requested, $absolute or relative
 * @param string $blobid
 * @param bool $absolute
 * @return string
 */
function qa_get_blob_url($blobid, $absolute = false)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return qa_path('blob', array('qa_blobid' => $blobid), $absolute ? qa_opt('site_url') : null, QA_URL_FORMAT_PARAMS);
}


/**
 * Return the full path to the on-disk directory for blob $blobid (subdirectories are named by the first 3 digits of $blobid)
 * @param string $blobid
 * @return string
 */
function qa_get_blob_directory($blobid)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return rtrim(QA_BLOBS_DIRECTORY, '/') . '/' . substr(str_pad($blobid, 20, '0', STR_PAD_LEFT), 0, 3);
}


/**
 * Return the full page and filename of blob $blobid which is in $format ($format is used as the file name suffix e.g. .jpg)
 * @param string $blobid
 * @param string $format
 * @return string
 */
function qa_get_blob_filename($blobid, $format)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return qa_get_blob_directory($blobid) . '/' . $blobid . '.' . preg_replace('/[^A-Za-z0-9]/', '', $format);
}


/**
 * Create a new blob (storing the content in the database or on disk as appropriate) with $content and $format, returning its blobid.
 * Pass the original name of the file uploaded in $sourcefilename and the $userid, $cookieid and $ip of the user creating it
 * @param string $content
 * @param string $format
 * @param string|null $sourcefilename
 * @param mixed|null $userid
 * @param string|null $cookieid
 * @param string $ip
 * @return string
 */
function qa_create_blob($content, $format, $sourcefilename = null, $userid = null, $cookieid = null, $ip = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/blobs.php';

	$blobid = qa_db_blob_create(defined('QA_BLOBS_DIRECTORY') ? null : $content, $format, $sourcefilename, $userid, $cookieid, $ip);

	if (isset($blobid) && defined('QA_BLOBS_DIRECTORY')) {
		// still write content to the database if writing to disk failed
		if (!qa_write_blob_file($blobid, $content, $format))
			qa_db_blob_set_content($blobid, $content);
	}

	return $blobid;
}


/**
 * Write the on-disk file for blob $blobid with $content and $format. Returns true if the write succeeded, false otherwise.
 * @param string $blobid
 * @param string $content
 * @param string $format
 * @return bool
 */
function qa_write_blob_file($blobid, $content, $format)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$written = false;

	$directory = qa_get_blob_directory($blobid);
	if (is_dir($directory) || mkdir($directory, fileperms(rtrim(QA_BLOBS_DIRECTORY, '/')) & 0777)) {
		$filename = qa_get_blob_filename($blobid, $format);

		$file = fopen($filename, 'xb');
		if (is_resource($file)) {
			if (fwrite($file, $content) >= strlen($content))
				$written = true;

			fclose($file);

			if (!$written)
				unlink($filename);
		}
	}

	return $written;
}


/**
 * Retrieve blob $blobid from the database, reading the content from disk if appropriate
 * @param string $blobid
 * @return array
 */
function qa_read_blob($blobid)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/blobs.php';

	$blob = qa_db_blob_read($blobid);

	if (isset($blob) && defined('QA_BLOBS_DIRECTORY') && !isset($blob['content']))
		$blob['content'] = qa_read_blob_file($blobid, $blob['format']);

	return $blob;
}


/**
 * Read the content of blob $blobid in $format from disk. On failure, it will return false.
 * @param string $blobid
 * @param string $format
 * @return false|string|null
 */
function qa_read_blob_file($blobid, $format)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$filename = qa_get_blob_filename($blobid, $format);
	if (is_readable($filename))
		return file_get_contents($filename);
	else
		return null;
}


/**
 * Delete blob $blobid from the database, and remove the on-disk file if appropriate
 * @param string $blobid
 * @return mixed
 */
function qa_delete_blob($blobid)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/blobs.php';

	if (defined('QA_BLOBS_DIRECTORY')) {
		$blob = qa_db_blob_read($blobid);

		if (isset($blob) && !isset($blob['content']))
			unlink(qa_get_blob_filename($blobid, $blob['format']));
	}

	qa_db_blob_delete($blobid);
}


/**
 * Delete the on-disk file for blob $blobid in $format
 * @param string $blobid
 * @param string $format
 * @return mixed
 */
function qa_delete_blob_file($blobid, $format)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	unlink(qa_get_blob_filename($blobid, $format));
}


/**
 * Check if blob $blobid exists
 * @param string $blobid
 * @return bool
 */
function qa_blob_exists($blobid)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'db/blobs.php';

	return qa_db_blob_exists($blobid);
}
