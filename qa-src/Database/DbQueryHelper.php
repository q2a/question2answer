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

use Q2A\Database\Exceptions\SelectSpecException;

/**
 * Various database utility functions.
 */
class DbQueryHelper
{
	/**
	 * Substitute ^ in a SQL query with the configured table prefix.
	 * @param string $query
	 * @return string
	 */
	public function applyTableSub($query)
	{
		return preg_replace_callback('/\^([A-Za-z_0-9]+)/', function ($matches) {
			return $this->addTablePrefix($matches[1]);
		}, $query);
	}

	/**
	 * Return the full name (with prefix) of a database table identifier.
	 * @param string $rawName
	 * @return string
	 */
	public function addTablePrefix($rawName)
	{
		$prefix = QA_MYSQL_TABLE_PREFIX;

		if (defined('QA_MYSQL_USERS_PREFIX')) {
			switch (strtolower($rawName)) {
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

		return $prefix . $rawName;
	}

	/**
	 * Substitute single '?' in a SQL query with multiple '?' for array parameters, and flatten parameter list accordingly.
	 * @throws SelectSpecException
	 * @param string $query
	 * @param array $params
	 * @return array Query and flattened parameter list
	 */
	public function expandParameters($query, array $params = [])
	{
		// handle old-style placeholders
		$query = str_replace(['#', '$'], '?', $query);

		$numParams = count($params);
		$explodedQuery = explode('?', $query);

		if ($numParams !== count($explodedQuery) - 1) {
			throw new SelectSpecException('The number of parameters and placeholders do not match');
		}
		if (empty($params)) {
			return [$query, $params];
		}

		$outQuery = '';
		$outParams = [];
		// use array_values to ensure consistent indexing
		foreach (array_values($params) as $i => $param) {
			$outQuery .= $explodedQuery[$i];
			if (is_array($param)) {
				$subArray = array_values($param);

				if (is_array($subArray[0])) {
					$this->handleInsertValuesQuery($subArray, $outQuery, $outParams);
				} else {
					$this->handleWhereInQuery($subArray, $outQuery, $outParams);
				}
			} else {
				$this->handleStandardQuery($param, $outQuery, $outParams);
			}
		}

		$outQuery .= $explodedQuery[$numParams];

		return [$outQuery, $outParams];
	}

	/**
	 * Basic query with individual parameters.
	 * @param array $param
	 * @param string $outQuery
	 * @param array $outParams
	 */
	private function handleStandardQuery($param, &$outQuery, array &$outParams)
	{
		$outQuery .= '?';
		$outParams[] = $param;
	}

	/**
	 * WHERE..IN query.
	 * @param array $params
	 * @param string $outQuery
	 * @param array $outParams
	 */
	private function handleWhereInQuery(array $params, &$outQuery, array &$outParams)
	{
		$outQuery .= $this->repeatStringWithSeparators('?', count($params));
		$outParams = array_merge($outParams, $params);
	}

	/**
	 * INSERT INTO..VALUES query for inserting multiple rows.
	 * If the first subparam is an array, the rest of the parameter groups should have the same
	 * amount of elements, i.e. the output should be '(?, ?), (?, ?)' rather than '(?), (?, ?)'.
	 * @throws SelectSpecException
	 * @param array $subArray
	 * @param string $outQuery
	 * @param array $outParams
	 */
	private function handleInsertValuesQuery(array $subArray, &$outQuery, array &$outParams)
	{
		$subArrayCount = count($subArray[0]);

		foreach ($subArray as $subArrayParam) {
			if (!is_array($subArrayParam) || count($subArrayParam) !== $subArrayCount) {
				throw new SelectSpecException('All parameter groups must have the same amount of parameters');
			}

			$outParams = array_merge($outParams, $subArrayParam);
		}

		$outQuery .= $this->repeatStringWithSeparators(
			'(' . $this->repeatStringWithSeparators('?', $subArrayCount) . ')',
			count($subArray)
		);
	}

	/**
	 * Repeat a string a given amount of times separating each of the instances with ', '.
	 * @param string $string
	 * @param int $amount
	 * @return string
	 */
	private function repeatStringWithSeparators($string, $amount)
	{
		return $amount == 1
			? $string
			: str_repeat($string . ', ', $amount - 1) . $string;
	}
}
