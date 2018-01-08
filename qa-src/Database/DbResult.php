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
use PDOStatement;

class DbResult
{
	private $stmt;

	public function __construct(PDOStatement $stmt)
	{
		$this->stmt = $stmt;
	}

	/**
	 * Return the first row from the results as an array of [column name] => [column value].
	 * @param  bool $allowempty If false, throw a fatal error if there is no result.
	 * @return array|null
	 */
	public function fetchNextAssoc($allowempty = false)
	{
		return $this->handleResult($this->stmt->fetch(PDO::FETCH_ASSOC), $allowempty, 'Reading one assoc from invalid result');
	}

	/**
	 * Return the data as an associative array.
	 * @param string $key Unique field to use as the array key for each row.
	 * @param string $value Field to use as the value (for key=>value format instead of the default key=>row)
	 * @return array
	 */
	public function fetchAllAssoc($key = null, $value = null)
	{
		$data = array();

		while (($row = $this->stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
			if (isset($key)) {
				$data[$row[$key]] = isset($value) ? $row[$value] : $row;
			} else {
				$data[] = isset($value) ? $row[$value] : $row;
			}
		}

		return $data;
	}

	/**
	 * Return a specific cell from the results. Typically used with (single-row, single-column) aggregate queries.
	 * @param $col 0-indexed column to select from (defaults to first column).
	 * @param bool $allowempty If false, throw a fatal error if there is no result.
	 * @return array|null
	 */
	public function fetchOneValue($col = 0, $allowempty = false)
	{
		return $this->handleResult($this->stmt->fetchColumn($col), $allowempty, 'Reading one value from empty results');
	}

	/**
	 * Return a numbered array containing the specified column.
	 * @param $col 0-indexed column to select from (defaults to first column).
	 * @return array
	 */
	public function fetchAllValues($col = 0)
	{
		return $this->stmt->fetchAll(PDO::FETCH_COLUMN, $col);
	}

	/**
	 * Return data or optionally fail if no more rows.
	 * @param array $data
	 * @param bool $allowempty
	 * @param string $errorMsg
	 * @return mixed
	 */
	private function handleResult($data, $allowempty, $errorMsg)
	{
		if ($data !== false) {
			return $data;
		}

		if (!$allowempty) {
			qa_fatal_error($errorMsg);
		}

		return null;
	}

	/**
	 * Number of rows found (SELECT queries) or rows affected (UPDATE/INSERT/DELETE queries).
	 * @return int
	 */
	public function rowCount()
	{
		return $this->stmt->rowCount();
	}

	/**
	 * Return true if the number of rows found (SELECT queries) or rows affected (UPDATE/INSERT/DELETE queries) is 0
	 * and false otherwise.
	 * @return int
	 */
	public function isEmpty()
	{
		return $this->stmt->rowCount() == 0;
	}

	/**
	 * Obtain the raw PDOStatement object.
	 * @return PDO
	 */
	public function getPDOStatement()
	{
		return $this->stmt;
	}
}
