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

	public function fetchNextAssoc()
	{
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function fetchAllAssoc()
	{
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Return a specific cell from the results. Typically used with (single-row, single-column) aggregate queries.
	 * @param $col 0-indexed column to select from (defaults to first column).
	 * @param bool $allowempty If false, throw a fatal error if there is no result.
	 * @return mixed|null
	 */
	public function fetchOneValue($col = 0, $allowempty = false)
	{
		$data = $this->stmt->fetchColumn($col);

		if ($data !== false) {
			return $data;
		}

		if (!$allowempty) {
			qa_fatal_error('Reading one value from empty results');
		}

		return null;
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
	 * Number of rows found (SELECT queries) or rows affected (UPDATE/INSERT/DELETE queries).
	 * @return int
	 */
	public function rowCount()
	{
		return $this->stmt->rowCount();
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
