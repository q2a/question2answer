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

class DbConnection
{
	protected $pdo;
	protected $config;
	protected $allowConnect = false;
	protected $failHandler;

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
			$hostAndPort = explode(':', $host);
			if (count($hostAndPort) >= 2) {
				$this->config['host'] = $hostAndPort[0];
				$this->config['port'] = $hostAndPort[1];
			}
		} elseif (defined('QA_FINAL_MYSQL_PORT')) {
			$this->config['port'] = QA_FINAL_MYSQL_PORT;
		}
	}

	/**
	 * Indicates to the Q2A database layer that database connections are permitted from this point forwards (before
	 * this point, some plugins may not have had a chance to override some database access functions).
	 */
	public function allowConnect()
	{
		$this->allowConnect = true;
	}

	/**
	 * Connect to the Q2A database, optionally install the $failhandler (and call it if necessary). Uses PDO as of Q2A
	 * 1.9.
	 * @param string $failhandler
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

		$options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
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
	 * If a DB error occurs, call the installed fail handler (if any) otherwise report error and exit immediately.
	 * @param $type
	 * @param int $errno
	 * @param string $error
	 * @param string $query
	 * @return mixed
	 */
	public function failError($type, $errno = null, $error = null, $query = null)
	{
		@error_log('PHP Question2Answer MySQL ' . $type . ' error ' . $errno . ': ' . $error . (isset($query) ? (' - Query: ' . $query) : ''));

		if (function_exists($this->failHandler)) {
			$this->failHandler($type, $errno, $error, $query);
		} else {
			echo sprintf(
				'<hr><div style="color: red">Database %s<p>%s</p><code>%s</code></div>',
				htmlspecialchars($type . ' error ' . $errno), nl2br(htmlspecialchars($error)), nl2br(htmlspecialchars($query))
			);
			qa_exit('error');
		}
	}

	/**
	 * Prepare and execute a SQL query, handling any failures. In debugging mode, track the queries and resources used.
	 * @param  string $query
	 * @param  array  $params
	 * @return PDOStatement
	 */
	public function query($query, $params = array())
	{
		try {
			if (QA_DEBUG_PERFORMANCE) {
				global $qa_usage;

				// time the query
				$oldtime = array_sum(explode(' ', microtime()));
				$stmt = $this->execute($query, $params);
				$usedtime = array_sum(explode(' ', microtime())) - $oldtime;

				// fetch counts
				$gotrows = $gotcolumns = null;
				if ($result instanceof mysqli_result) {
					$gotrows = $result->num_rows;
					$gotcolumns = $result->field_count;
				}

				$qa_usage->logDatabaseQuery($query, $usedtime, $gotrows, $gotcolumns);
			} else {
				$stmt = $this->execute($query, $params);
			}

			return $stmt;
		} catch (PDOException $ex) {
			$this->failError('query', $ex->getCode(), $ex->getMessage(), $query);
		}
	}

	/**
	 * Lower-level function to prepare and execute a SQL query. Automatically retries if there is a MySQL deadlock
	 * error.
	 * @param  string $query
	 * @param  array  $params
	 * @return PDOStatement
	 */
	protected function execute($query, $params = array())
	{
		$stmt = $this->pdo->prepare($query);

		for ($attempt = 0; $attempt < 100; $attempt++) {
			$success = $stmt->execute($params);

			if ($success === true || $stmt->errorCode() !== '1213') {
				break;
			}

			// deal with InnoDB deadlock errors by waiting 0.01s then retrying
			usleep(10000);
		}

		return $stmt;
	}
}
