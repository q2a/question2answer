<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Common functions for connecting to and accessing database


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

use Q2A\Database\DbResult;

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../');
	exit;
}


/**
 * Indicates to the Q2A database layer that database connections are permitted fro this point forwards
 * (before this point, some plugins may not have had a chance to override some database access functions).
 * @deprecated 1.9.0 Use DbConnection->allowConnect() instead.
 */
function qa_db_allow_connect()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	qa_service('database')->allowConnect();
}


/**
 * Connect to the Q2A database, select the right database, optionally install the $failhandler (and call it if necessary).
 * Uses mysqli as of Q2A 1.7.
 * @deprecated 1.9.0 Use DbConnection->connect() instead.
 * @param string|null $failhandler
 * @return mixed
 */
function qa_db_connect($failhandler = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	qa_service('database')->connect($failhandler);
}


/**
 * If a DB error occurs, call the installed fail handler (if any) otherwise report error and exit immediately.
 * @deprecated 1.9.0 Use DbConnection->failError() instead.
 * @param string $type
 * @param int $errno
 * @param string $error
 * @param string $query
 * @return mixed
 */
function qa_db_fail_error($type, $errno = null, $error = null, $query = null)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	qa_service('database')->failError($type, $errno, $error, $query);
}


/**
 * Return the current connection to the Q2A database, connecting if necessary and $connect is true.
 * @deprecated 1.9.0
 * @param bool $connect
 * @return mixed
 */
function qa_db_connection($connect = true)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$db = qa_service('database');
	if ($connect && !$db->isConnected()) {
		$db->connect();
	}

	return $db;
}


/**
 * Disconnect from the Q2A database.
 * @deprecated 1.9.0 Use DbConnection->disconnect() instead.
 */
function qa_db_disconnect()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	qa_service('database')->disconnect();
}


/**
 * Run the raw $query, call the global failure handler if necessary, otherwise return the result resource.
 * If appropriate, also track the resources used by database queries, and the queries themselves, for performance debugging.
 * @deprecated 1.9.0 Use DbConnection->query() instead.
 * @param string $query
 * @return DbResult
 */
function qa_db_query_raw($query)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return qa_service('database')->query($query);
}


/**
 * Lower-level function to execute a query, which automatically retries if there is a MySQL deadlock error.
 * @deprecated 1.9.0 Use DbConnection->query() instead.
 * @param string $query
 * @return DbResult
 */
function qa_db_query_execute($query)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return qa_service('database')->query($query);
}


/**
 * Return $string escaped for use in queries to the Q2A database (to which a connection must have been made).
 * @deprecated 1.9.0 No longer needed: parameters passed to DbConnection->query() are automatically escaped.
 * @param string $string
 * @return string
 */
function qa_db_escape_string($string)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$pdo = qa_service('database')->getPDO();
	// PDO::quote wraps the value in single quotes, so remove them for backwards compatibility
	$quotedString = $pdo->quote($string);
	return substr($quotedString, 1, -1);
}


/**
 * Return $argument escaped for MySQL. Add quotes around it if $alwaysquote is true or it's not numeric.
 * If $argument is an array, return a comma-separated list of escaped elements, with or without $arraybrackets.
 * @deprecated 1.9.0 Use DbQueryHelper->expandParameters() instead.
 * @param mixed|null $argument
 * @param bool $alwaysquote
 * @param bool $arraybrackets
 * @return mixed|string
 */
function qa_db_argument_to_mysql($argument, $alwaysquote, $arraybrackets = false)
{
	if (is_array($argument)) {
		$parts = array();

		foreach ($argument as $subargument)
			$parts[] = qa_db_argument_to_mysql($subargument, $alwaysquote, true);

		if ($arraybrackets)
			$result = '(' . implode(',', $parts) . ')';
		else
			$result = implode(',', $parts);

	} elseif (isset($argument)) {
		if ($alwaysquote || !is_numeric($argument))
			$result = "'" . qa_db_escape_string($argument) . "'";
		else
			$result = qa_db_escape_string($argument);
	} else
		$result = 'NULL';

	return $result;
}


/**
 * Return the full name (with prefix) of database table $rawname, usually if it used after a ^ symbol.
 * @deprecated 1.9.0 Use DbQueryHelper->addTablePrefix() instead.
 * @param string $rawname
 * @return string
 */
function qa_db_add_table_prefix($rawname)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return (new \Q2A\Database\DbQueryHelper)->addTablePrefix($rawname);
}


/**
 * Callback function to add table prefixes, as used in qa_db_apply_sub().
 * @deprecated 1.9.0 No longer needed.
 * @param array $matches
 * @return string
 */
function qa_db_prefix_callback($matches)
{
	return qa_db_add_table_prefix($matches[1]);
}


/**
 * Substitute ^, $ and # symbols in $query. ^ symbols are replaced with the table prefix set in qa-config.php.
 * $ and # symbols are replaced in order by the corresponding element in $arguments (if the element is an array,
 * it is converted recursively into comma-separated list). Each element in $arguments is escaped.
 * $ is replaced by the argument in quotes (even if it's a number), # only adds quotes if the argument is non-numeric.
 * It's important to use $ when matching a textual column since MySQL won't use indexes to compare text against numbers.
 * @deprecated 1.9.0 Use DbQueryHelper->expandParameters() instead.
 * @param string $query
 * @param array $arguments
 * @return mixed
 */
function qa_db_apply_sub($query, $arguments)
{
	// function left intact as some code calls this directly

	$query = preg_replace_callback('/\^([A-Za-z_0-9]+)/', 'qa_db_prefix_callback', $query);

	if (!is_array($arguments))
		return $query;

	$countargs = count($arguments);
	$offset = 0;

	for ($argument = 0; $argument < $countargs; $argument++) {
		$stringpos = strpos($query, '$', $offset);
		$numberpos = strpos($query, '#', $offset);

		if ($stringpos === false || ($numberpos !== false && $numberpos < $stringpos)) {
			$alwaysquote = false;
			$position = $numberpos;
		} else {
			$alwaysquote = true;
			$position = $stringpos;
		}

		if (!is_numeric($position))
			qa_fatal_error('Insufficient parameters in query: ' . $query);

		$value = qa_db_argument_to_mysql($arguments[$argument], $alwaysquote);
		$query = substr_replace($query, $value, $position, 1);
		$offset = $position + strlen($value); // allows inserting strings which contain #/$ character
	}

	return $query;
}


/**
 * Run $query after substituting ^, # and $ symbols, and return the result resource (or call fail handler).
 * @deprecated 1.9.0 Use DbConnection->query() instead.
 * @param string $query
 * @return DbResult
 */
function qa_db_query_sub($query) // arguments for substitution retrieved using func_get_args()
{
	$params = array_slice(func_get_args(), 1);
	return qa_service('database')->query($query, $params);
}


/**
 * Run $query after substituting ^, # and $ symbols, and return the result resource (or call fail handler).
 * Query parameters are passed as an array.
 * @deprecated 1.9.0 Use DbConnection->query() instead.
 * @param string $query
 * @param array $params
 * @return DbResult
 */
function qa_db_query_sub_params($query, $params)
{
	return qa_service('database')->query($query, $params);
}


/**
 * Return the number of rows in $result. (Simple wrapper for mysqli_result::num_rows.)
 * @deprecated 1.9.0 Use DbResult->affectedRows() instead.
 * @param DbResult|mysqli_result $result
 * @return int
 */
function qa_db_num_rows($result)
{
	if ($result instanceof \Q2A\Database\DbResult)
		return $result->affectedRows();

	// backwards compatibility
	if ($result instanceof mysqli_result)
		return $result->num_rows;

	return 0;
}


/**
 * Return the value of the auto-increment column for the last inserted row.
 * @deprecated 1.9.0 Use DbConnection->lastInsertId() instead.
 * @return string
 */
function qa_db_last_insert_id()
{
	return qa_service('database')->lastInsertId();
}


/**
 * Return the number of rows affected by the last query.
 * @deprecated 1.9.0 Use DbResult->affectedRows() instead.
 * @return int
 */
function qa_db_affected_rows()
{
	// not doable with new DB system (requires a PDOStatement, which if we had we could pass into DbResult instead)
	return 0;
}


/**
 * For the previous INSERT ... ON DUPLICATE KEY UPDATE query, return whether an insert operation took place.
 * @deprecated 1.9.0 Use DbResult->affectedRows() instead.
 * @return bool
 */
function qa_db_insert_on_duplicate_inserted()
{
	return false;
}


/**
 * Return a random integer (as a string) for use in a BIGINT column.
 * Actual limit is 18,446,744,073,709,551,615 - we aim for 18,446,743,999,999,999,999.
 * @return string
 */
function qa_db_random_bigint()
{
	return sprintf('%d%06d%06d', mt_rand(1, 18446743), mt_rand(0, 999999), mt_rand(0, 999999));
}


/**
 * Return an array of the names of all tables in the Q2A database, converted to lower case.
 * No longer used by Q2A and shouldn't be needed.
 * @return array
 */
function qa_db_list_tables_lc()
{
	return array_map('strtolower', qa_db_list_tables());
}


/**
 * Return an array of the names of all tables in the Q2A database.
 *
 * @param bool $onlyTablesWithPrefix Determine if the result should only include tables with the
 * QA_MYSQL_TABLE_PREFIX or if it should include all tables in the database.
 * @return array
 */
function qa_db_list_tables($onlyTablesWithPrefix = false)
{
	$query = 'SHOW TABLES';

	if ($onlyTablesWithPrefix) {
		$col = 'Tables_in_' . QA_FINAL_MYSQL_DATABASE;
		$query .= ' WHERE `' . $col . '` LIKE "' . str_replace('_', '\\_', QA_MYSQL_TABLE_PREFIX) . '%"';
		if (defined('QA_MYSQL_USERS_PREFIX')) {
			$query .= ' OR `' . $col . '` LIKE "' . str_replace('_', '\\_', QA_MYSQL_USERS_PREFIX) . '%"';
		}
	}

	return qa_db_read_all_values(qa_db_query_raw($query));
}


/*
	The selectspec array can contain the elements below. See db/selects.php for lots of examples.

	By default, qa_db_single_select() and qa_db_multi_select() return the data for each selectspec as a numbered
	array of arrays, one per row. The array for each row has column names in the keys, and data in the values.
	But this can be changed using the 'arraykey', 'arrayvalue' and 'single' in the selectspec.

	Note that even if you specify ORDER BY in 'source', the final results may not be ordered. This is because
	the SELECT could be done within a UNION that (annoyingly) doesn't maintain order. Use 'sortasc' or 'sortdesc'
	to fix this. You can however rely on the combination of ORDER BY and LIMIT retrieving the appropriate records.


	'columns' => Array of names of columns to be retrieved (required)

		If a value in the columns array has an integer key, it is retrieved AS itself (in a SQL sense).
		If a value in the columns array has a non-integer key, it is retrieved AS that key.
		Values in the columns array can include table specifiers before the period.

	'source' => Any SQL after FROM, including table names, JOINs, GROUP BY, ORDER BY, WHERE, etc... (required)

	'arguments' => Substitutions in order for $s and #s in the query, applied in qa_db_apply_sub() above (required)

	'arraykey' => Name of column to use for keys of the outer-level returned array, instead of numbers by default

	'arrayvalue' => Name of column to use for values of outer-level returned array, instead of arrays by default

	'single' => If true, return the array for a single row and don't embed it within an outer-level array

	'sortasc' => Sort the output ascending by this column

	'sortdesc' => Sort the output descending by this column


	Why does qa_db_multi_select() combine usually unrelated SELECT statements into a single query?

	Because if the database and web servers are on different computers, there will be latency.
	This way we ensure that every read pageview on the site requires as few DB queries as possible, so
	that we pay for this latency only one time.

	For writes we worry less, since the user is more likely to be expecting a delay.

	If QA_OPTIMIZE_DISTANT_DB is set to false in qa-config.php, we assume zero latency and go back to
	simple queries, since this will allow both MySQL and PHP to provide quicker results.
*/


/**
 * Return the data specified by a single $selectspec - see long comment above.
 * @deprecated 1.9.0 Use DbSelect->singleSelect() instead.
 * @param array $selectspec
 * @return mixed
 */
function qa_db_single_select($selectspec)
{
	$dbSelect = qa_service('dbselect');

	return $dbSelect->singleSelect($selectspec);
}


/**
 * Return the data specified by each element of $selectspecs, where the keys of the
 * returned array match the keys of the supplied $selectspecs array. See long comment above.
 * @deprecated 1.9.0 Use DbSelect->multiSelect() instead.
 * @param array $selectspecs
 * @return array
 */
function qa_db_multi_select($selectspecs)
{
	$dbSelect = qa_service('dbselect');

	return $dbSelect->multiSelect($selectspecs);
}


/**
 * Post-process $outresult according to $selectspec, applying 'sortasc', 'sortdesc', 'arrayvalue' and 'single'.
 * @deprecated 1.9.0 Private method in DbConnection (code left in place for backwards compatibility)
 * @param array $outresult
 * @param array $selectspec
 */
function qa_db_post_select(&$outresult, $selectspec)
{
	// PHP's sorting algorithm is not 'stable', so we use '_order_' element to keep stability.
	// By contrast, MySQL's ORDER BY does seem to give the results in a reliable order.

	if (isset($selectspec['sortasc'])) {
		require_once QA_INCLUDE_DIR . 'util/sort.php';

		$index = 0;
		foreach ($outresult as $key => $value)
			$outresult[$key]['_order_'] = $index++;

		qa_sort_by($outresult, $selectspec['sortasc'], '_order_');

	} elseif (isset($selectspec['sortdesc'])) {
		require_once QA_INCLUDE_DIR . 'util/sort.php';

		if (isset($selectspec['sortdesc_2']))
			qa_sort_by($outresult, $selectspec['sortdesc'], $selectspec['sortdesc_2']);

		else {
			$index = count($outresult);
			foreach ($outresult as $key => $value)
				$outresult[$key]['_order_'] = $index--;

			qa_sort_by($outresult, $selectspec['sortdesc'], '_order_');
		}

		$outresult = array_reverse($outresult, true);
	}

	if (isset($selectspec['arrayvalue']))
		foreach ($outresult as $key => $value)
			$outresult[$key] = $value[$selectspec['arrayvalue']];

	if (@$selectspec['single'])
		$outresult = count($outresult) ? reset($outresult) : null;
}


/**
 * Return the full results from the $result resource as an array. The key of each element in the returned array
 * is from column $key if specified, otherwise it's integer. The value of each element in the returned array
 * is from column $value if specified, otherwise it's a named array of all columns, given an array of arrays.
 * @deprecated 1.9.0 Use DbResult->fetchAllAssoc() instead.
 * @param DbResult|mysqli_result $result
 * @param string|null $key
 * @param int|string|null $value
 * @return array
 */
function qa_db_read_all_assoc($result, $key = null, $value = null)
{
	if ($result instanceof \Q2A\Database\DbResult) {
		return $result->fetchAllAssoc($key, $value);
	}


	// backwards compatibility
	if (!($result instanceof mysqli_result))
		qa_fatal_error('Reading all assoc from invalid result');

	$assocs = array();

	while ($assoc = $result->fetch_assoc()) {
		if (isset($key))
			$assocs[$assoc[$key]] = isset($value) ? $assoc[$value] : $assoc;
		else
			$assocs[] = isset($value) ? $assoc[$value] : $assoc;
	}

	return $assocs;
}


/**
 * Return the first row from the $result resource as an array of [column name] => [column value].
 * If there's no first row, throw a fatal error unless $allowempty is true.
 * @deprecated 1.9.0 Use DbResult->fetchNextAssoc() instead.
 * @param DbResult|mysqli_result $result
 * @param bool $allowempty
 * @return array|null
 */
function qa_db_read_one_assoc($result, $allowempty = false)
{
	if ($result instanceof \Q2A\Database\DbResult) {
		return $allowempty ? $result->fetchNextAssoc() : $result->fetchNextAssocOrFail();
	}

	// backwards compatibility
	if (!($result instanceof mysqli_result))
		qa_fatal_error('Reading one assoc from invalid result');

	$assoc = $result->fetch_assoc();

	if (is_array($assoc))
		return $assoc;

	if ($allowempty)
		return null;
	else
		qa_fatal_error('Reading one assoc from empty results');
}


/**
 * Return a numbered array containing the first (and presumably only) column from the $result resource.
 * @deprecated 1.9.0 Use DbResult->fetchAllValues() instead.
 * @param DbResult|mysqli_result $result
 * @return array
 */
function qa_db_read_all_values($result)
{
	if ($result instanceof \Q2A\Database\DbResult) {
		return $result->fetchAllValues(0);
	}

	// backwards compatibility
	if (!($result instanceof mysqli_result))
		qa_fatal_error('Reading column from invalid result');

	$output = array();

	while ($row = $result->fetch_row())
		$output[] = $row[0];

	return $output;
}


/**
 * Return the first column of the first row (and presumably only cell) from the $result resource.
 * If there's no first row, throw a fatal error unless $allowempty is true.
 * @deprecated 1.9.0 Use DbResult->fetchOneValue() instead.
 * @param DbResult|mysqli_result $result
 * @param bool $allowempty
 * @return string|null
 */
function qa_db_read_one_value($result, $allowempty = false)
{
	if ($result instanceof \Q2A\Database\DbResult) {
		return $allowempty ? $result->fetchOneValue(0) : $result->fetchOneValueOrFail(0);
	}

	// backwards compatibility
	if (!($result instanceof mysqli_result))
		qa_fatal_error('Reading one value from invalid result');

	$row = $result->fetch_row();

	if (is_array($row))
		return $row[0];

	if ($allowempty)
		return null;
	else
		qa_fatal_error('Reading one value from empty results');
}


/**
 * Suspend the updating of counts (of many different types) in the database, to save time when making a lot of changes
 * if $suspend is true, otherwise reinstate it. A counter is kept to allow multiple calls.
 * @deprecated 1.9.0 Use DbConnection->suspendUpdateCounts() instead.
 * @param bool $suspend
 */
function qa_suspend_update_counts($suspend = true)
{
	qa_service('database')->suspendUpdateCounts($suspend);
}


/**
 * Returns whether counts should currently be updated (i.e. if count updating has not been suspended).
 * @deprecated 1.9.0 Use DbConnection->shouldUpdateCounts() instead.
 * @return bool
 */
function qa_should_update_counts()
{
	return qa_service('database')->shouldUpdateCounts();
}
