<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db.php
	Version: See define()s at top of qa-include/qa-base.php
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


	function qa_db_allow_connect()
/*
	Indicates to the Q2A database layer that database connections are permitted fro this point forwards
	(before this point, some plugins may not have had a chance to override some database access functions)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_db_allow_connect;

		$qa_db_allow_connect=true;
	}
	
	
	function qa_db_connect($failhandler=null)
/*
	Connect to the Q2A database, select the right database, optionally install the $failhandler (and call it if necessary)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_db_connection, $qa_db_fail_handler, $qa_db_allow_connect;
		
		if (!$qa_db_allow_connect)
			qa_fatal_error('It appears that a plugin is trying to access the database, but this is not allowed until Q2A initialization is complete.');
		
		if (isset($failhandler))
			$qa_db_fail_handler=$failhandler; // set this even if connection already opened
			
		if (!is_resource($qa_db_connection)) {
			if (QA_PERSISTENT_CONN_DB)
				$db=mysql_pconnect(QA_FINAL_MYSQL_HOSTNAME, QA_FINAL_MYSQL_USERNAME, QA_FINAL_MYSQL_PASSWORD);
			else
				$db=mysql_connect(QA_FINAL_MYSQL_HOSTNAME, QA_FINAL_MYSQL_USERNAME, QA_FINAL_MYSQL_PASSWORD);
			
			if (is_resource($db)) {
				if (!mysql_select_db(QA_FINAL_MYSQL_DATABASE, $db)) {
					mysql_close($db);
					qa_db_fail_error('select');
				}
				
			/*
				From Q2A 1.5, we *do* explicitly set the character encoding of the MySQL connection, instead
				of using lots of "SELECT BINARY col AS col"-style queries, as in previous versions of Q2A.
				Although this introduces the latency of another query here, testing showed that the delay is
				the same as that from calling mysql_select_db() and only a quarter of that of mysql_connect().
				So overall it adds 20% to the latency delay in qa_db_connect(), and this seems worth trading
				off against the benefit of more straightforward queries, especially for plugin developers.
			*/
				
				if (function_exists('mysql_set_charset'))
					mysql_set_charset('utf8', $db);
				else
					mysql_query('SET NAMES utf8', $db);
					
				qa_report_process_stage('db_connected');
					
			} else
				qa_db_fail_error('connect');
		
			$qa_db_connection=$db;
		}
	}
	
	
	function qa_db_fail_error($type, $errno=null, $error=null, $query=null)
/*
	If a DB error occurs, call the installed fail handler (if any) otherwise report error and exit immediately
*/
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

	
	function qa_db_connection($connect=true)
/*
	Return the current connection to the Q2A database, connecting if necessary and $connect is true
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_db_connection;
		
		if ($connect && !is_resource($qa_db_connection)) {
			qa_db_connect();
			
			if (!is_resource($qa_db_connection))
				qa_fatal_error('Failed to connect to database');
		}
		
		return $qa_db_connection;
	}

	
	function qa_db_disconnect()
/*
	Disconnect from the Q2A database
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_db_connection;
		
		if (is_resource($qa_db_connection)) {
			qa_report_process_stage('db_disconnect');
			
			if (!QA_PERSISTENT_CONN_DB)
				if (!mysql_close($qa_db_connection))
					qa_fatal_error('Database disconnect failed');
					
			$qa_db_connection=null;
		}
	}

	
	function qa_db_query_raw($query)
/*
	Run the raw $query, call the global failure handler if necessary, otherwise return the result resource.
	If appropriate, also track the resources used by database queries, and the queries themselves, for performance debugging.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if (QA_DEBUG_PERFORMANCE) {
			global $qa_database_usage, $qa_database_queries;
			
			$oldtime=array_sum(explode(' ', microtime()));
			$result=qa_db_query_execute($query);
			$usedtime=array_sum(explode(' ', microtime()))-$oldtime;

			if (is_array($qa_database_usage)) {
				$qa_database_usage['clock']+=$usedtime;
		
				if (strlen($qa_database_queries)<1048576) { // don't keep track of big tests
					$gotrows=is_resource($result) ? mysql_num_rows($result) : null;
					$gotcolumns=is_resource($result) ? mysql_num_fields($result) : null;
					
					$qa_database_queries.=$query."\n\n".sprintf('%.2f ms', $usedtime*1000).
						(is_numeric($gotrows) ? (' - '.$gotrows.(($gotrows==1) ? ' row' : ' rows')) : '').
						(is_numeric($gotcolumns) ? (' - '.$gotcolumns.(($gotcolumns==1) ? ' column' : ' columns')) : '').
						"\n\n";
				}
			
				$qa_database_usage['queries']++;
			}
		
		} else
			$result=qa_db_query_execute($query);
	
	//	@error_log('Question2Answer MySQL query: '.$query);

		if ($result===false) {
			$db=qa_db_connection();
			qa_db_fail_error('query', mysql_errno($db), mysql_error($db), $query);
		}
			
		return $result;
	}
	
	
	function qa_db_query_execute($query)
/*
	Lower-level function to execute a query, which automatically retries if there is a MySQL deadlock error
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$db=qa_db_connection();
		
		for ($attempt=0; $attempt<100; $attempt++) {
			$result=mysql_query($query, $db);
			
			if (($result===false) && (mysql_errno($db)==1213))
				usleep(10000); // dead with InnoDB deadlock errors by waiting 0.01s then retrying
			else
				break;
		}
		
		return $result;
	}
	
	
	function qa_db_escape_string($string)
/*
	Return $string escaped for use in queries to the Q2A database (to which a connection must have been made)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return mysql_real_escape_string($string, qa_db_connection());
	}

	
	function qa_db_argument_to_mysql($argument, $alwaysquote, $arraybrackets=false)
/*
	Return $argument escaped for MySQL. Add quotes around it if $alwaysquote is true or it's not numeric.
	If $argument is an array, return a comma-separated list of escaped elements, with or without $arraybrackets.
*/
	{
		if (is_array($argument)) {
			$parts=array();
			
			foreach ($argument as $subargument)
				$parts[]=qa_db_argument_to_mysql($subargument, $alwaysquote, true);
			
			if ($arraybrackets)
				$result='('.implode(',', $parts).')';
			else
				$result=implode(',', $parts);
		
		} elseif (isset($argument)) {
			if ($alwaysquote || !is_numeric($argument))
				$result="'".qa_db_escape_string($argument)."'";
			else
				$result=qa_db_escape_string($argument);
		
		} else
			$result='NULL';
		
		return $result;
	}
	
	
	function qa_db_add_table_prefix($rawname)
/*
	Return the full name (with prefix) of database table $rawname, usually if it used after a ^ symbol
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$prefix=QA_MYSQL_TABLE_PREFIX;
		
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
					$prefix=QA_MYSQL_USERS_PREFIX;
					break;
			}
		}
			
		return $prefix.$rawname;
	}
	
	
	function qa_db_prefix_callback($matches)
/*
	Callback function to add table prefixes, as used in qa_db_apply_sub()
*/
	{
		return qa_db_add_table_prefix($matches[1]);
	}

	
	function qa_db_apply_sub($query, $arguments)
/*
	Substitute ^, $ and # symbols in $query. ^ symbols are replaced with the table prefix set in qa-config.php.
	$ and # symbols are replaced in order by the corresponding element in $arguments (if the element is an array,
	it is converted recursively into comma-separated list). Each element in $arguments is escaped.
	$ is replaced by the argument in quotes (even if it's a number), # only adds quotes if the argument is non-numeric.
	It's important to use $ when matching a textual column since MySQL won't use indexes to compare text against numbers.
*/
	{
		$query=preg_replace_callback('/\^([A-Za-z_0-9]+)/', 'qa_db_prefix_callback', $query);
		
		if (is_array($arguments)) {
			$countargs=count($arguments);
			$offset=0;
			
			for ($argument=0; $argument<$countargs; $argument++) {
				$stringpos=strpos($query, '$', $offset);
				$numberpos=strpos($query, '#', $offset);
				
				if ( ($stringpos===false) || ( ($numberpos!==false) && ($numberpos<$stringpos) ) ) {
					$alwaysquote=false;
					$position=$numberpos;
				
				} else {
					$alwaysquote=true;
					$position=$stringpos;
				}
				
				if (!is_numeric($position))
					qa_fatal_error('Insufficient parameters in query: '.$query);
				
				$value=qa_db_argument_to_mysql($arguments[$argument], $alwaysquote);
				$query=substr_replace($query, $value, $position, 1);
				$offset=$position+strlen($value); // allows inserting strings which contain #/$ character
			}
		}
		
		return $query;
	}

	
	function qa_db_query_sub($query) // arguments for substitution retrieved using func_get_args()
/*
	Run $query after substituting ^, # and $ symbols, and return the result resource (or call fail handler)
*/
	{
		$funcargs=func_get_args();

		return qa_db_query_raw(qa_db_apply_sub($query, array_slice($funcargs, 1)));
	}

	
	function qa_db_last_insert_id()
/*
	Return the value of the auto-increment column for the last inserted row
*/
	{
		return qa_db_read_one_value(qa_db_query_raw('SELECT LAST_INSERT_ID()'));
	}
	

	function qa_db_affected_rows()
/*
	Does what it says on the tin
*/
	{
		return mysql_affected_rows(qa_db_connection());
	}
	
	
	function qa_db_insert_on_duplicate_inserted()
/*
	For the previous INSERT ... ON DUPLICATE KEY UPDATE query, return whether an insert operation took place
*/
	{
		return (qa_db_affected_rows()==1);
	}

	
	function qa_db_random_bigint()
/*
	Return a random integer (as a string) for use in a BIGINT column.
	Actual limit is 18,446,744,073,709,551,615 - we aim for 18,446,743,999,999,999,999
*/
	{
		return sprintf('%d%06d%06d', mt_rand(1,18446743), mt_rand(0,999999), mt_rand(0,999999));
	}
	
	
	function qa_db_list_tables_lc()
/*
	Return an array of the names of all tables in the Q2A database, converted to lower case
*/
	{
		return array_map('strtolower', qa_db_list_tables());
	}
	
	
	function qa_db_list_tables()
/*
	Return an array of the names of all tables in the Q2A database
*/
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


	function qa_db_single_select($selectspec)
/*
	Return the data specified by a single $selectspec - see long comment above.
*/
	{
		$query='SELECT ';
		
		foreach ($selectspec['columns'] as $columnas => $columnfrom)
			$query.=$columnfrom.(is_int($columnas) ? '' : (' AS '.$columnas)).', ';
		
		$results=qa_db_read_all_assoc(qa_db_query_raw(qa_db_apply_sub(
			substr($query, 0, -2).(strlen(@$selectspec['source']) ? (' FROM '.$selectspec['source']) : ''),
			@$selectspec['arguments'])
		), @$selectspec['arraykey']); // arrayvalue is applied in qa_db_post_select()
		
		qa_db_post_select($results, $selectspec); // post-processing

		return $results;
	}

	
	function qa_db_multi_select($selectspecs)
/*
	Return the data specified by each element of $selectspecs, where the keys of the
	returned array match the keys of the supplied $selectspecs array. See long comment above.
*/
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
	

	function qa_db_post_select(&$outresult, $selectspec)
/*
	Post-process $outresult according to $selectspec, applying 'sortasc', 'sortdesc', 'arrayvalue' and 'single'
*/
	{
		// PHP's sorting algorithm is not 'stable', so we use '_order_' element to keep stability.
		// By contrast, MySQL's ORDER BY does seem to give the results in a reliable order.

		if (isset($selectspec['sortasc'])) {
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';
		
			$index=0;
			foreach ($outresult as $key => $value)
				$outresult[$key]['_order_']=$index++;
				
			qa_sort_by($outresult, $selectspec['sortasc'], '_order_');

		} elseif (isset($selectspec['sortdesc'])) {
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';
			
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

	
	function qa_db_read_all_assoc($result, $key=null, $value=null)
/*
	Return the full results from the $result resource as an array. The key of each element in the returned array
	is from column $key if specified, otherwise it's integer. The value of each element in the returned array
	is from column $value if specified, otherwise it's a named array of all columns, given an array of arrays.
*/
	{
		if (!is_resource($result))
			qa_fatal_error('Reading all assoc from invalid result');
			
		$assocs=array();
		
		while ($assoc=mysql_fetch_assoc($result)) {
			if (isset($key))
				$assocs[$assoc[$key]]=isset($value) ? $assoc[$value] : $assoc;
			else
				$assocs[]=isset($value) ? $assoc[$value] : $assoc;
		}
			
		return $assocs;
	}
	

	function qa_db_read_one_assoc($result, $allowempty=false)
/*
	Return the first row from the $result resource as an array of [column name] => [column value].
	If there's no first row, throw a fatal error unless $allowempty is true.
*/
	{
		if (!is_resource($result))
			qa_fatal_error('Reading one assoc from invalid result');

		$assoc=mysql_fetch_assoc($result);
		
		if (!is_array($assoc)) {
			if ($allowempty)
				return null;
			else
				qa_fatal_error('Reading one assoc from empty results');
		}
		
		return $assoc;
	}

	
	function qa_db_read_all_values($result)
/*
	Return a numbered array containing the first (and presumably only) column from the $result resource
*/
	{
		if (!is_resource($result))
			qa_fatal_error('Reading column from invalid result');
			
		$output=array();
		
		while ($row=mysql_fetch_row($result))
			$output[]=$row[0];
		
		return $output;
	}

	
	function qa_db_read_one_value($result, $allowempty=false)
/*
	Return the first column of the first row (and presumably only cell) from the $result resource.
	If there's no first row, throw a fatal error unless $allowempty is true.
*/
	{
		if (!is_resource($result))
			qa_fatal_error('Reading one value from invalid result');

		$row=mysql_fetch_row($result);
		
		if (!is_array($row)) {
			if ($allowempty)
				return null;
			else
				qa_fatal_error('Reading one value from empty results');
		}
			
		return $row[0];
	}
	
	
	function qa_suspend_update_counts($suspend=true)
/*
	Suspend the updating of counts (of many different types) in the database, to save time when making a lot of changes
	if $suspend is true, otherwise reinstate it. A counter is kept to allow multiple calls.
*/
	{
		global $qa_update_counts_suspended;
		
		$qa_update_counts_suspended+=($suspend ? 1 : -1);
	}

	
	function qa_should_update_counts()
/*
	Returns whether counts should currently be updated (i.e. if count updating has not been suspended)
*/
	{
		global $qa_update_counts_suspended;
		
		return ($qa_update_counts_suspended<=0);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/