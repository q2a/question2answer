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
use Q2A\Database\Exceptions\ReadingFromEmptyResultException;

/**
 * Thin wrapper around the PDOStatement class which returns results in a variety of formats.
 */
class DbResult
{
	private $stmt;

	public function __construct(PDOStatement $stmt)
	{
		$this->stmt = $stmt;
	}

	/**
	 * Return the first row from the results as an array of [column name] => 'column value'. If no value is fetched
	 * from the database, return null.
	 * @return array|null
	 */
	public function fetchNextAssoc()
	{
		$data = $this->stmt->fetch(PDO::FETCH_ASSOC);

		return $data === false ? null : $data;
	}

	/**
	 * Return the first row from the results as an array of [column name] => [column value]. If no value is fetched
	 * from the database, throw an exception.
	 * @return array|null
	 * @throws ReadingFromEmptyResultException
	 */
	public function fetchNextAssocOrFail()
	{
		return $this->getResultOrFail($this->stmt->fetch(PDO::FETCH_ASSOC));
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
	 * Return a specific cell from the results. Typically used with (single-row, single-column) aggregate queries. If
	 * no value is fetched from the database, return null.
	 * @param $col 0-indexed column to select from (defaults to first column).
	 * @return string
	 */
	public function fetchOneValue($col = 0)
	{
		$data = $this->stmt->fetchColumn($col);

		return $data === false ? null : $data;
	}

	/**
	 * Return a specific cell from the results. Typically used with (single-row, single-column) aggregate queries. If
	 * no value is fetched from the database, throw an exception.
	 * @param $col 0-indexed column to select from (defaults to first column).
	 * @return string
	 * @throws ReadingFromEmptyResultException
	 */
	public function fetchOneValueOrFail($col = 0)
	{
		return $this->getResultOrFail($this->stmt->fetchColumn($col));
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
	 * Return data or optionally throw an exception, if data is false (empty).
	 * @param mixed $data
	 * @return mixed
	 * @throws ReadingFromEmptyResultException
	 */
	private function getResultOrFail($data)
	{
		if ($data === false) {
			throw new ReadingFromEmptyResultException('Reading one value from empty results');
		}

		return $data;
	}

	/**
	 * Number of rows found (SELECT queries) or rows affected (UPDATE/INSERT/DELETE queries).
	 * @return int
	 */
	public function affectedRows()
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
		return $this->affectedRows() === 0;
	}

	/**
	 * Obtain the raw PDOStatement object.
	 * @return PDOStatement
	 */
	public function getPDOStatement()
	{
		return $this->stmt;
	}
}
