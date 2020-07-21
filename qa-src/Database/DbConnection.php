<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

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

namespace Q2A\Database;

use PDO;
use PDOException;
use PDOStatement;
use Q2A\Database\Exceptions\SelectSpecException;
use Q2A\Storage\CacheFactory;

/**
 * Core database class. Handles all SQL queries within Q2A.
 */
class DbConnection
{
	/** @var PDO */
	protected $pdo;

	/** @var array */
	protected $config;

	/** @var bool */
	protected $allowConnect = false;

	/** @var string */
	protected $failHandler;

	/** @var int */
	protected $updateCountsSuspended = 0;

	public function __construct()
	{
		$this->config = array(
			'driver' => 'mysql',
			'host' => QA_FINAL_MYSQL_HOSTNAME,
			'username' => QA_FINAL_MYSQL_USERNAME,
			'password' => QA_FINAL_MYSQL_PASSWORD,
			'database' => QA_FINAL_MYSQL_DATABASE,
		);

		if (defined('QA_FINAL_WORDPRESS_INTEGRATE_PATH')) {
			// Wordpress allows setting port inside DB_HOST constant, like 127.0.0.1:3306
			$hostAndPort = explode(':', $this->config['host']);
			if (count($hostAndPort) >= 2) {
				$this->config['host'] = $hostAndPort[0];
				$this->config['port'] = $hostAndPort[1];
			}
		} elseif (defined('QA_FINAL_MYSQL_PORT')) {
			$this->config['port'] = QA_FINAL_MYSQL_PORT;
		}
	}

	/**
	 * Obtain the raw PDO object.
	 * @return PDO
	 */
	public function getPDO()
	{
		return $this->pdo;
	}

	/**
	 * Indicates to the Q2A database layer that database connections are permitted from this point forwards (before
	 * this point, some plugins may not have had a chance to override some database access functions).
	 * @return void
	 */
	public function allowConnect()
	{
		$this->allowConnect = true;
	}

	/**
	 * Indicates whether Q2A is connected to the database.
	 * @return boolean
	 */
	public function isConnected()
	{
		return $this->pdo !== null;
	}

	/**
	 * Connect to the Q2A database, optionally install the $failHandler (and call it if necessary).
	 * Uses PDO as of Q2A 1.9.
	 * @param string $failHandler
	 * @return mixed|void
	 */
	public function connect($failHandler = null)
	{
		if (!$this->allowConnect) {
			qa_fatal_error('It appears that a plugin is trying to access the database, but this is not allowed until Q2A initialization is complete.');
			return;
		}
		if ($failHandler !== null) {
			// set this even if connection already opened
			$this->failHandler = $failHandler;
		}
		if ($this->pdo) {
			return;
		}

		$dsn = sprintf('%s:host=%s;dbname=%s;charset=utf8', $this->config['driver'], $this->config['host'], $this->config['database']);
		if (isset($this->config['port'])) {
			$dsn .= ';port=' . $this->config['port'];
		}

		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => true, // required for queries like LOCK TABLES (also, slightly faster)
		);
		if (QA_PERSISTENT_CONN_DB) {
			$options[PDO::ATTR_PERSISTENT] = true;
		}

		try {
			$this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
		} catch (PDOException $ex) {
			$this->failError('connect', $ex->getCode(), $ex->getMessage());
		}

		qa_report_process_stage('db_connected');
	}

	/**
	 * Disconnect from the Q2A database. This is not strictly required, but we do it in case there is a long Q2A shutdown process.
	 * @return void
	 */
	public function disconnect()
	{
		$this->pdo = null;
	}

	/**
	 * If a DB error occurs, call the installed fail handler (if any) otherwise report error and exit immediately.
	 * @param string $type
	 * @param int $errno
	 * @param string $error
	 * @param string $query
	 * @return mixed
	 */
	public function failError($type, $errno = null, $error = null, $query = null)
	{
		@error_log('PHP Question2Answer MySQL ' . $type . ' error ' . $errno . ': ' . $error . (isset($query) ? (' - Query: ' . $query) : ''));

		if (function_exists($this->failHandler)) {
			$failFunc = $this->failHandler;
			$failFunc($type, $errno, $error, $query);
		} else {
			echo sprintf(
				'<hr><div style="color: red">Database %s<p>%s</p><code>%s</code></div>',
				htmlspecialchars($type . ' error ' . $errno),
				nl2br(htmlspecialchars($error)),
				nl2br(htmlspecialchars($query))
			);
			qa_exit('error');
		}
	}

	/**
	 * Prepare and execute a SQL query, handling any failures. In debugging mode, track the queries and resources used.
	 * @throws SelectSpecException
	 * @param string $query
	 * @param array $params
	 * @return DbResult
	 */
	public function query($query, $params = [])
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$helper = new DbQueryHelper;
		$query = $helper->applyTableSub($query);
		// handle WHERE..IN and INSERT..VALUES queries
		list($query, $params) = $helper->expandParameters($query, $params);

		if (substr_count($query, '?') != count($params)) {
			throw new SelectSpecException('The number of parameters and placeholders do not match');
		}

		try {
			if (QA_DEBUG_PERFORMANCE) {
				global $qa_usage;

				// time the query
				$oldtime = array_sum(explode(' ', microtime()));
				$stmt = $this->execute($query, $params);
				$usedtime = array_sum(explode(' ', microtime())) - $oldtime;

				$qa_usage->logDatabaseQuery($query, $usedtime, $stmt->rowCount(), $stmt->columnCount());
			} else {
				$stmt = $this->execute($query, $params);
			}

			return new DbResult($stmt);
		} catch (PDOException $ex) {
			$this->failError('query', $ex->getCode(), $ex->getMessage(), $query);
		}
	}

	/**
	 * Lower-level function to prepare and execute a SQL query. Automatically retries if there is a MySQL deadlock
	 * error.
	 * @param string $query
	 * @param array $params
	 * @return PDOStatement
	 */
	protected function execute($query, $params = array())
	{
		$stmt = $this->pdo->prepare($query);
		// PDO quotes parameters by default, which breaks LIMIT clauses, so we bind parameters manually
		foreach (array_values($params) as $i => $param) {
			if (filter_var($param, FILTER_VALIDATE_INT) !== false) {
				$dataType = PDO::PARAM_INT;
				$param = (int) $param;
			} else {
				$dataType = PDO::PARAM_STR;
			}
			$stmt->bindValue($i + 1, $param, $dataType);
		}

		for ($attempt = 0; $attempt < 100; $attempt++) {
			$success = $stmt->execute();

			if ($success === true || $stmt->errorCode() !== '1213') {
				break;
			}

			// deal with InnoDB deadlock errors by waiting 0.01s then retrying
			usleep(10000);
		}

		return $stmt;
	}

	/*
		The selectspec array can contain the elements below. See db/selects.php for lots of examples.

		By default, singleSelect() and multiSelect() return the data for each selectspec as a numbered
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


		Why does multiSelect() combine usually unrelated SELECT statements into a single query?

		Because if the database and web servers are on different computers, there will be latency.
		This way we ensure that every read pageview on the site requires as few DB queries as possible, so
		that we pay for this latency only one time.

		For writes we worry less, since the user is more likely to be expecting a delay.

		If QA_OPTIMIZE_DISTANT_DB is set to false in qa-config.php, we assume zero latency and go back to
		simple queries, since this will allow both MySQL and PHP to provide quicker results.
	*/

	/**
	 * Return the data specified by a single $selectspec - see long comment above.
	 * @param $selectspec
	 * @return array|mixed
	 */
	public function singleSelect($selectspec)
	{
		// check for cached results
		if (isset($selectspec['caching'])) {
			$cacheDriver = CacheFactory::getCacheDriver();
			$cacheKey = 'query:' . $selectspec['caching']['key'];

			if ($cacheDriver->isEnabled()) {
				$queryData = $cacheDriver->get($cacheKey);
				if ($queryData !== null) {
					return $queryData;
				}
			}
		}

		$query = 'SELECT ';
		foreach ($selectspec['columns'] as $columnas => $columnfrom) {
			$query .= is_int($columnas) ? "$columnfrom, " : "$columnfrom AS `$columnas`, ";
		}

		$query = substr($query, 0, -2);
		if (isset($selectspec['source']) && strlen($selectspec['source']) > 0) {
			$query .= ' FROM ' . $selectspec['source'];
		}

		$params = isset($selectspec['arguments']) ? $selectspec['arguments'] : array();
		$arraykey = isset($selectspec['arraykey']) ? $selectspec['arraykey'] : null;

		$result = $this->query($query, $params);
		$data = $result->fetchAllAssoc($arraykey); // arrayvalue is applied in formatSelect()

		$this->formatSelect($data, $selectspec); // post-processing

		// save cached results
		if (isset($selectspec['caching'])) {
			if ($cacheDriver->isEnabled()) {
				$cacheDriver->set($cacheKey, $data, $selectspec['caching']['ttl']);
			}
		}

		return $data;
	}

	/**
	 * Return the data specified by each element of $selectspecs, where the keys of the
	 * returned array match the keys of the supplied $selectspecs array. See long comment above.
	 * @param array $selectspecs
	 * @return array
	 */
	public function multiSelect($selectspecs)
	{
		// Perform simple queries if the database is local or there are only 0-1 selectspecs

		if (!QA_OPTIMIZE_DISTANT_DB || count($selectspecs) <= 1) {
			$outresults = array();

			foreach ($selectspecs as $selectkey => $selectspec) {
				$outresults[$selectkey] = $this->singleSelect($selectspec);
			}

			return $outresults;
		}

		// Otherwise, parse columns for each spec to deal with columns without an 'AS' specification

		foreach ($selectspecs as $selectkey => $selectspec) {
			$selectspecs[$selectkey]['outcolumns'] = array();
			$selectspecs[$selectkey]['autocolumn'] = array();

			foreach ($selectspec['columns'] as $columnas => $columnfrom) {
				if (is_int($columnas)) {
					// fetch column name if not already provided in the array key
					$periodpos = strpos($columnfrom, '.');
					$columnas = is_numeric($periodpos) ? substr($columnfrom, $periodpos + 1) : $columnfrom;
					$selectspecs[$selectkey]['autocolumn'][$columnas] = true;
				}

				if (isset($selectspecs[$selectkey]['outcolumns'][$columnas])) {
					qa_fatal_error('Duplicate column name in DbConnection::multiSelect()');
				}

				$selectspecs[$selectkey]['outcolumns'][$columnas] = $columnfrom;
			}

			if (isset($selectspec['arraykey']) && !isset($selectspecs[$selectkey]['outcolumns'][$selectspec['arraykey']])) {
				qa_fatal_error('Used arraykey not in columns in DbConnection::multiSelect()');
			}
			if (isset($selectspec['arrayvalue']) && !isset($selectspecs[$selectkey]['outcolumns'][$selectspec['arrayvalue']])) {
				qa_fatal_error('Used arrayvalue not in columns in DbConnection::multiSelect()');
			}
		}

		// Work out the full list of columns used

		$outcolumns = array();
		foreach ($selectspecs as $selectspec) {
			$outcolumns = array_unique(array_merge($outcolumns, array_keys($selectspec['outcolumns'])));
		}

		// Build the query based on this full list

		$query = '';
		$queryParams = array();
		foreach ($selectspecs as $selectkey => $selectspec) {
			$subquery = "(SELECT " . $this->pdo->quote($selectkey) . ' AS selectkey';

			foreach ($outcolumns as $columnas) {
				$subquery .= ', ' . (isset($selectspec['outcolumns'][$columnas]) ? $selectspec['outcolumns'][$columnas] : 'NULL');

				if (empty($query) && !isset($selectspec['autocolumn'][$columnas])) {
					$subquery .= ' AS ' . $columnas;
				}
			}

			if (isset($selectspec['source']) && strlen($selectspec['source']) > 0) {
				$subquery .= ' FROM ' . $selectspec['source'];
			}

			$subquery .= ')';

			if (strlen($query)) {
				$query .= ' UNION ALL ';
			}

			$query .= $subquery;
			if (isset($selectspec['arguments'])) {
				$queryParams = array_merge($queryParams, array_values($selectspec['arguments']));
			}
		}

		// Perform query and extract results

		$stmt = $this->query($query, $queryParams);
		$rawresults = $stmt->fetchAllAssoc();

		$outresults = array();
		foreach ($selectspecs as $selectkey => $selectspec) {
			$outresults[$selectkey] = array();
		}

		foreach ($rawresults as $rawresult) {
			$selectkey = $rawresult['selectkey'];
			$selectspec = $selectspecs[$selectkey];

			$keepresult = array();
			foreach ($selectspec['outcolumns'] as $columnas => $columnfrom) {
				$keepresult[$columnas] = $rawresult[$columnas];
			}

			if (isset($selectspec['arraykey'])) {
				$outresults[$selectkey][$keepresult[$selectspec['arraykey']]] = $keepresult;
			} else {
				$outresults[$selectkey][] = $keepresult;
			}
		}

		// Post-processing to apply various stuff include sorting request, since we can't rely on ORDER BY due to UNION

		foreach ($selectspecs as $selectkey => $selectspec) {
			$this->formatSelect($outresults[$selectkey], $selectspec);
		}

		return $outresults;
	}

	/**
	 * Post-process $outresult according to $selectspec, applying 'sortasc', 'sortdesc', 'arrayvalue' and 'single'.
	 * @param array $outresult
	 * @param array $selectspec
	 */
	private function formatSelect(&$outresult, $selectspec)
	{
		// PHP's sorting algorithm is not 'stable', so we use '_order_' element to keep stability.
		// By contrast, MySQL's ORDER BY does seem to give the results in a reliable order.

		if (isset($selectspec['sortasc'])) {
			require_once QA_INCLUDE_DIR . 'util/sort.php';

			$index = 0;
			foreach ($outresult as $key => $value) {
				$outresult[$key]['_order_'] = $index++;
			}

			qa_sort_by($outresult, $selectspec['sortasc'], '_order_');
		} elseif (isset($selectspec['sortdesc'])) {
			require_once QA_INCLUDE_DIR . 'util/sort.php';

			if (isset($selectspec['sortdesc_2'])) {
				qa_sort_by($outresult, $selectspec['sortdesc'], $selectspec['sortdesc_2']);
			} else {
				$index = count($outresult);
				foreach ($outresult as $key => $value) {
					$outresult[$key]['_order_'] = $index--;
				}

				qa_sort_by($outresult, $selectspec['sortdesc'], '_order_');
			}

			$outresult = array_reverse($outresult, true);
		}

		if (isset($selectspec['arrayvalue'])) {
			foreach ($outresult as $key => $value) {
				$outresult[$key] = $value[$selectspec['arrayvalue']];
			}
		}

		if (@$selectspec['single']) {
			$outresult = count($outresult) ? reset($outresult) : null;
		}
	}

	/**
	 * Return the value of the auto-increment column for the last inserted row.
	 * @return string
	 */
	public function lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}

	/**
	 * Suspend or reinstate the updating of counts (of many different types) in the database, to save time when making
	 * a lot of changes. A counter is kept to allow multiple calls.
	 * @param bool $suspend
	 */
	public function suspendUpdateCounts($suspend = true)
	{
		$this->updateCountsSuspended += ($suspend ? 1 : -1);
	}

	/**
	 * Returns whether counts should currently be updated (i.e. if count updating has not been suspended).
	 * @return bool
	 */
	public function shouldUpdateCounts()
	{
		return $this->updateCountsSuspended <= 0;
	}
}
