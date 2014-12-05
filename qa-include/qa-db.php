<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-db.php
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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	/**
	 * Indicates to the Q2A database layer that database connections are permitted fro this point forwards
	 * (before this point, some plugins may not have had a chance to override some database access functions).
	 */
	function qa_db_allow_connect()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		global $qa_db_allow_connect;

		$qa_db_allow_connect=true;
	}


	/**
	 * Connect to the Q2A database, select the right database, optionally install the $failhandler (and call it if necessary).
	 * Uses mysqli as of Q2A 1.7.
	 */
	function qa_db_connect($failhandler=null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		global $qa_db_connection, $qa_db_fail_handler, $qa_db_allow_connect;

		if (!$qa_db_allow_connect)
			qa_fatal_error('It appears that a plugin is trying to access the database, but this is not allowed until Q2A initialization is complete.');

		if (isset($failhandler))
			$qa_db_fail_handler = $failhandler; // set this even if connection already opened

		if ($qa_db_connection instanceof mysqli)
			return;

		// in mysqli we connect and select database in constructor
		if (QA_PERSISTENT_CONN_DB)
			$db = new mysqli('p:'.QA_FINAL_MYSQL_HOSTNAME, QA_FINAL_MYSQL_USERNAME, QA_FINAL_MYSQL_PASSWORD, QA_FINAL_MYSQL_DATABASE);
		else
			$db = new mysqli(QA_FINAL_MYSQL_HOSTNAME, QA_FINAL_MYSQL_USERNAME, QA_FINAL_MYSQL_PASSWORD, QA_FINAL_MYSQL_DATABASE);

		// must use procedural `mysqli_connect_error` here prior to 5.2.9
		$conn_error = mysqli_connect_error();
		if ($conn_error)
			qa_db_fail_error('connect', $db->connect_errno, $conn_error);

		// From Q2A 1.5, we explicitly set the character encoding of the MySQL connection, instead of using lots of "SELECT BINARY col"-style queries.
		// Testing showed that overhead is minimal, so this seems worth trading off against the benefit of more straightforward queries, especially
		// for plugin developers.
		if (!$db->set_charset('utf8'))
			qa_db_fail_error('set_charset', $db->errno, $db->error);

		qa_report_process_stage('db_connected');

		$qa_db_connection=$db;
	}


	/**
	 * If a DB error occurs, call the installed fail handler (if any) otherwise report error and exit immediately.
	 */
	function qa_db_fail_error($type, $errno=null, $error=null, $query=null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		global $qa_db_fail_handler;

		@error_log('PHP Question2Answer MySQL '.$type.' error '.$errno.': '.$error.(isset($query) ? (' - Query: '.$query) : ''));

		if (function_exists($qa_db_fail_handler))
			$qa_db_fail_handler($type, $errno, $error, $query);

		else {
			echo '<hr><font color="red">Database '.htmlspecialchars($type.' error '.$errno).'<p>'.nl2br(htmlspecialchars($error."\n\n".$query));
			qa_exit('error');
		}
	}


	/**
	 * Return the current connection to the Q2A database, connecting if necessary and $connect is true.
	 */
	function qa_db_connection($connect=true)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		global $qa_db_connection;

		if ($connect && !($qa_db_connection instanceof mysqli)) {
			qa_db_connect();

			if (!($qa_db_connection instanceof mysqli))
				qa_fatal_error('Failed to connect to database');
		}

		return $qa_db_connection;
	}


	/**
	 * Disconnect from the Q2A database.
	 */
	function qa_db_disconnect()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		global $qa_db_connection;

		if ($qa_db_connection instanceof mysqli) {
			qa_report_process_stage('db_disconnect');

			if (!QA_PERSISTENT_CONN_DB) {
				if (!$qa_db_connection->close())
					qa_fatal_error('Database disconnect failed');
			}

			$qa_db_connection=null;
		}
	}


	/**
	 * Run the raw $query, call the global failure handler if necessary, otherwise return the result resource.
	 * If appropriate, also track the resources used by database queries, and the queries themselves, for performance debugging.
	 */
	function qa_db_query_raw($query)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		if (QA_DEBUG_PERFORMANCE) {
			global $qa_usage;

			// time the query
			$oldtime = array_sum(explode(' ', microtime()));
			$result = qa_db_query_execute($query);
			$usedtime = array_sum(explode(' ', microtime())) - $oldtime;

			// fetch counts
			$gotrows = $gotcolumns = null;
			if ($result instanceof mysqli_result) {
				$gotrows = $result->num_rows;
				$gotcolumns = $result->field_count;
			}

			$qa_usage->logDatabaseQuery($query, $usedtime, $gotrows, $gotcolumns);
		}
		else
			$result = qa_db_query_execute($query);

	//	@error_log('Question2Answer MySQL query: '.$query);

		if ($result === false) {
			$db = qa_db_connection();
			qa_db_fail_error('query', $db->errno, $db->error, $query);
		}

		return $result;
	}


	/**
	 * Lower-level function to execute a query, which automatically retries if there is a MySQL deadlock error.
	 */
	function qa_db_query_execute($query)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$db = qa_db_connection();

		for ($attempt = 0; $attempt < 100; $attempt++) {
			$result = $db->query($query);

			if ($result === false && $db->errno == 1213)
				usleep(10000); // deal with InnoDB deadlock errors by waiting 0.01s then retrying
			else
				break;
		}

		return $result;
	}


	/**
	 * Return $string escaped for use in queries to the Q2A database (to which a connection must have been made).
	 */
	function qa_db_escape_string($string)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$db = qa_db_connection();
		return $db->real_escape_string($string);
	}


	/**
	 * Return $argument escaped for MySQL. Add quotes around it if $alwaysquote is true or it's not numeric.
	 * If $argument is an array, return a comma-separated list of escaped elements, with or without $arraybrackets.
	 */
	function qa_db_argument_to_mysql($argument, $alwaysquote, $arraybrackets=false)
	{
		if (is_array($argument)) {
			$parts=array();

			foreach ($argument as $subargument)
				$parts[] = qa_db_argument_to_mysql($subargument, $alwaysquote, true);

			if ($arraybrackets)
				$result = '('.implode(',', $parts).')';
			else
				$result = implode(',', $parts);

		}
		elseif (isset($argument)) {
			if ($alwaysquote || !is_numeric($argument))
				$result = "'".qa_db_escape_string($argument)."'";
			else
				$result = qa_db_escape_string($argument);
		}
		else
			$result = 'NULL';

		return $result;
	}


	/**
	 * Return the full name (with prefix) of database table $rawname, usually if it used after a ^ symbol.
	 */
	function qa_db_add_table_prefix($rawname)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$prefix = QA_MYSQL_TABLE_PREFIX;

		if (defined('QA_MYSQL_USERS_PREFIX')) {
			switch (strtolower($rawname)) {
				case 'users':
				case 'userlogins':
				case 'userprofile':
				case 'userfields':
				case 'messages':
				case 'cookies':
				case 'blobs':
				case 'cache':
				case 'userlogins_ibfk_1': // also special cases for constraint names
				case 'userprofile_ibfk_1':
					$prefix = QA_MYSQL_USERS_PREFIX;
					break;
			}
		}

		return $prefix.$rawname;
	}


	/**
	 * Callback function to add table prefixes, as used in qa_db_apply_sub().
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
	 */
	function qa_db_apply_sub($query, $arguments)
	{
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
			}
			else {
				$alwaysquote = true;
				$position = $stringpos;
			}

			if (!is_numeric($position))
				qa_fatal_error('Insufficient parameters in query: '.$query);

			$value = qa_db_argument_to_mysql($arguments[$argument], $alwaysquote);
			$query = substr_replace($query, $value, $position, 1);
			$offset = $position + strlen($value); // allows inserting strings which contain #/$ character
		}

		return $query;
	}


	/**
	 * Run $query after substituting ^, # and $ symbols, and return the result resource (or call fail handler).
	 */
	function qa_db_query_sub($query) // arguments for substitution retrieved using func_get_args()
	{
		$funcargs=func_get_args();

		return qa_db_query_raw(qa_db_apply_sub($query, array_slice($funcargs, 1)));
	}


	/**
	 * Return the number of rows in $result. (Simple wrapper for mysqli_result::num_rows.)
	 */
	function qa_db_num_rows($result)
	{
		if ($result instanceof mysqli_result)
			return $result->num_rows;

		return 0;
	}


	/**
	 * Return the value of the auto-increment column for the last inserted row.
	 */
	function qa_db_last_insert_id()
	{
		$db = qa_db_connection();
		return $db->insert_id;
	}


	/**
	 * Return the number of rows affected by the last query.
	 */
	function qa_db_affected_rows()
	{
		$db = qa_db_connection();
		return $db->affected_rows;
	}


	/**
	 * For the previous INSERT ... ON DUPLICATE KEY UPDATE query, return whether an insert operation took place.
	 */
	function qa_db_insert_on_duplicate_inserted()
	{
		return (qa_db_affected_rows() == 1);
	}


	/**
	 * Return a random integer (as a string) for use in a BIGINT column.
	 * Actual limit is 18,446,744,073,709,551,615 - we aim for 18,446,743,999,999,999,999.
	 */
	function qa_db_random_bigint()
	{
		return sprintf('%d%06d%06d', mt_rand(1, 18446743), mt_rand(0, 999999), mt_rand(0, 999999));
	}


	/**
	 * Return an array of the names of all tables in the Q2A database, converted to lower case.
	 * No longer used by Q2A and shouldn't be needed.
	 */
	function qa_db_list_tables_lc()
	{
		return array_map('strtolower', qa_db_list_tables());
	}


	/**
	 * Return an array of the names of all tables in the Q2A database.
	 */
	function qa_db_list_tables()
	{
		return qa_db_read_all_values(qa_db_query_raw('SHOW TABLES'));
	}


/*
	The selectspec array can contain the elements below. See qa-db-selects.php for lots of examples.

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
	This way we ensure that every read pageview on the site requires only a single DB query, so
	that we pay for this latency only one time.

	For writes we worry less, since the user is more likely to be expecting a delay.

	If QA_OPTIMIZE_LOCAL_DB is set in qa-config.php, we assume zero latency and go back to
	simple queries, since this will allow both MySQL and PHP to provide quicker results.
*/


	/**
	 * Return the data specified by a single $selectspec - see long comment above.
	 */
	function qa_db_single_select($selectspec)
	{
		$query = 'SELECT ';

		foreach ($selectspec['columns'] as $columnas => $columnfrom)
			$query .= $columnfrom . (is_int($columnas) ? '' : (' AS '.$columnas)) . ', ';

		$results = qa_db_read_all_assoc(qa_db_query_raw(qa_db_apply_sub(
			substr($query, 0, -2).(strlen(@$selectspec['source']) ? (' FROM '.$selectspec['source']) : ''),
			@$selectspec['arguments'])
		), @$selectspec['arraykey']); // arrayvalue is applied in qa_db_post_select()

		qa_db_post_select($results, $selectspec); // post-processing

		return $results;
	}


	/**
	 * Return the data specified by each element of $selectspecs, where the keys of the
	 * returned array match the keys of the supplied $selectspecs array. See long comment above.
	 */
	function qa_db_multi_select($selectspecs)
	{
		if (!count($selectspecs))
			return array();

	//	Perform simple queries if the database is local or there are only 0 or 1 selectspecs

		if (QA_OPTIMIZE_LOCAL_DB || (count($selectspecs)<=1)) {
			$outresults=array();

			foreach ($selectspecs as $selectkey => $selectspec)
				$outresults[$selectkey]=qa_db_single_select($selectspec);

			return $outresults;
		}

	//	Otherwise, parse columns for each spec to deal with columns without an 'AS' specification

		foreach ($selectspecs as $selectkey => $selectspec) {
			$selectspecs[$selectkey]['outcolumns']=array();
			$selectspecs[$selectkey]['autocolumn']=array();

			foreach ($selectspec['columns'] as $columnas => $columnfrom) {
				if (is_int($columnas)) {
					$periodpos=strpos($columnfrom, '.');
					$columnas=is_numeric($periodpos) ? substr($columnfrom, $periodpos+1) : $columnfrom;
					$selectspecs[$selectkey]['autocolumn'][$columnas]=true;
				}

				if (isset($selectspecs[$selectkey]['outcolumns'][$columnas]))
					qa_fatal_error('Duplicate column name in qa_db_multi_select()');

				$selectspecs[$selectkey]['outcolumns'][$columnas]=$columnfrom;
			}

			if (isset($selectspec['arraykey']))
				if (!isset($selectspecs[$selectkey]['outcolumns'][$selectspec['arraykey']]))
					qa_fatal_error('Used arraykey not in columns in qa_db_multi_select()');

			if (isset($selectspec['arrayvalue']))
				if (!isset($selectspecs[$selectkey]['outcolumns'][$selectspec['arrayvalue']]))
					qa_fatal_error('Used arrayvalue not in columns in qa_db_multi_select()');
		}

	//	Work out the full list of columns used

		$outcolumns=array();
		foreach ($selectspecs as $selectspec)
			$outcolumns=array_unique(array_merge($outcolumns, array_keys($selectspec['outcolumns'])));

	//	Build the query based on this full list

		$query='';
		foreach ($selectspecs as $selectkey => $selectspec) {
			$subquery="(SELECT '".qa_db_escape_string($selectkey)."'".(empty($query) ? ' AS selectkey' : '');

			foreach ($outcolumns as $columnas) {
				$subquery.=', '.(isset($selectspec['outcolumns'][$columnas]) ? $selectspec['outcolumns'][$columnas] : 'NULL');

				if (empty($query) && !isset($selectspec['autocolumn'][$columnas]))
					$subquery.=' AS '.$columnas;
			}

			if (strlen(@$selectspec['source']))
				$subquery.=' FROM '.$selectspec['source'];

			$subquery.=')';

			if (strlen($query))
				$query.=' UNION ALL ';

			$query.=qa_db_apply_sub($subquery, @$selectspec['arguments']);
		}

	//	Perform query and extract results

		$rawresults=qa_db_read_all_assoc(qa_db_query_raw($query));

		$outresults=array();
		foreach ($selectspecs as $selectkey => $selectspec)
			$outresults[$selectkey]=array();

		foreach ($rawresults as $rawresult) {
			$selectkey=$rawresult['selectkey'];
			$selectspec=$selectspecs[$selectkey];

			$keepresult=array();
			foreach ($selectspec['outcolumns'] as $columnas => $columnfrom)
				$keepresult[$columnas]=$rawresult[$columnas];

			if (isset($selectspec['arraykey']))
				$outresults[$selectkey][$keepresult[$selectspec['arraykey']]]=$keepresult;
			else
				$outresults[$selectkey][]=$keepresult;
		}

	//	Post-processing to apply various stuff include sorting request, since we can't rely on ORDER BY due to UNION

		foreach ($selectspecs as $selectkey => $selectspec)
			qa_db_post_select($outresults[$selectkey], $selectspec);

	//	Return results

		return $outresults;
	}


	/**
	 * Post-process $outresult according to $selectspec, applying 'sortasc', 'sortdesc', 'arrayvalue' and 'single'.
	 */
	function qa_db_post_select(&$outresult, $selectspec)
	{
		// PHP's sorting algorithm is not 'stable', so we use '_order_' element to keep stability.
		// By contrast, MySQL's ORDER BY does seem to give the results in a reliable order.

		if (isset($selectspec['sortasc'])) {
			require_once QA_INCLUDE_DIR.'util/sort.php';

			$index=0;
			foreach ($outresult as $key => $value)
				$outresult[$key]['_order_']=$index++;

			qa_sort_by($outresult, $selectspec['sortasc'], '_order_');

		} elseif (isset($selectspec['sortdesc'])) {
			require_once QA_INCLUDE_DIR.'util/sort.php';

			if (isset($selectspec['sortdesc_2']))
				qa_sort_by($outresult, $selectspec['sortdesc'], $selectspec['sortdesc_2']);

			else {
				$index=count($outresult);
				foreach ($outresult as $key => $value)
					$outresult[$key]['_order_']=$index--;

				qa_sort_by($outresult, $selectspec['sortdesc'], '_order_');
			}

			$outresult=array_reverse($outresult, true);
		}

		if (isset($selectspec['arrayvalue']))
			foreach ($outresult as $key => $value)
				$outresult[$key]=$value[$selectspec['arrayvalue']];

		if (@$selectspec['single'])
			$outresult=count($outresult) ? reset($outresult) : null;
	}


	/**
	 * Return the full results from the $result resource as an array. The key of each element in the returned array
	 * is from column $key if specified, otherwise it's integer. The value of each element in the returned array
	 * is from column $value if specified, otherwise it's a named array of all columns, given an array of arrays.
	 */
	function qa_db_read_all_assoc($result, $key=null, $value=null)
	{
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
	 */
	function qa_db_read_one_assoc($result, $allowempty=false)
	{
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
	 */
	function qa_db_read_all_values($result)
	{
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
	 */
	function qa_db_read_one_value($result, $allowempty=false)
	{
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
	 */
	function qa_suspend_update_counts($suspend=true)
	{
		global $qa_update_counts_suspended;

		$qa_update_counts_suspended += ($suspend ? 1 : -1);
	}


	/**
	 * Returns whether counts should currently be updated (i.e. if count updating has not been suspended).
	 */
	function qa_should_update_counts()
	{
		global $qa_update_counts_suspended;

		return ($qa_update_counts_suspended <= 0);
	}
