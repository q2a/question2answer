<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database-level access to table containing admin options


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
 * Set option $name to $value in the database
 * @param $name
 * @param $value
 */
function qa_db_set_option($name, $value)
{
	qa_db_query_sub(
		'INSERT INTO ^options (title, content) VALUES ($, $) ' .
		'ON DUPLICATE KEY UPDATE content = VALUES(content)',
		$name, $value
	);
}

/**
 * Update a cached count in the ^options table for a given $cacheKey, using $countQuery SQL. The $increment
 * is optional and allows to perform and increment/decrement of the given value rather than a full recalc.
 *
 * @param string $cacheKey
 * @param string $countQuery
 * @param int|null $increment
 */
function qa_db_generic_cache_update($cacheKey, $countQuery, $increment = null)
{
	if (!qa_should_update_counts()) {
		return;
	}

	$sql =
		'INSERT INTO ^options (title, content) ' .
		sprintf('VALUES ("%s", #) ', $cacheKey) .
		'ON DUPLICATE KEY UPDATE content = VALUES(content)';

	if (isset($increment)) {
		$sql .= ' + CAST(content AS INT)';
	} else {
		$increment = qa_db_read_one_value(qa_db_query_sub($countQuery));
	}

	qa_db_query_sub($sql, $increment);
}
