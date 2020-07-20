<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database-level access to cache table


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

require_once QA_INCLUDE_DIR . 'db/maxima.php';


/**
 * Create (or replace) the item ($type, $cacheid) in the database cache table with $content
 * @param string $type
 * @param string $cacheid
 * @param string $content
 * @return mixed
 */
function qa_db_cache_set($type, $cacheid, $content)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	qa_db_query_sub(
		'DELETE FROM ^cache WHERE lastread<NOW()-INTERVAL # SECOND',
		QA_DB_MAX_CACHE_AGE
	);

	qa_db_query_sub(
		'INSERT INTO ^cache (type, cacheid, content, created, lastread) VALUES ($, $, $, NOW(), NOW()) ' .
		'ON DUPLICATE KEY UPDATE content = VALUES(content), created = VALUES(created), lastread = VALUES(lastread)',
		$type, $cacheid, $content
	);
}


/**
 * Retrieve the item ($type, $cacheid) from the database cache table
 * @param string $type
 * @param string $cacheid
 * @return mixed|null
 */
function qa_db_cache_get($type, $cacheid)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$content = qa_db_read_one_value(qa_db_query_sub(
		'SELECT content FROM ^cache WHERE type=$ AND cacheid=#',
		$type, $cacheid
	), true);

	if (isset($content))
		qa_db_query_sub(
			'UPDATE ^cache SET lastread=NOW() WHERE type=$ AND cacheid=#',
			$type, $cacheid
		);

	return $content;
}
