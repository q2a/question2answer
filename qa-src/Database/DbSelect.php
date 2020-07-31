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

use Q2A\Storage\CacheFactory;

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

	'arguments' => Substitutions in order for ?s in the query, applied in DbConnection->query()

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

class DbSelect
{
	/** @var DbConnection */
	protected $db;

	/** @var array */
	protected $pendingSelects = [];

	/** @var array */
	protected $pendingResults = [];

	public function __construct(DbConnection $db)
	{
		$this->db = $db;
	}

	/**
	 * Return the data specified by a single $selectspec - see long comment above.
	 * @param $selectspec
	 * @return array|mixed
	 */
	public function singleSelect(array $selectspec)
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

		$result = $this->db->query($query, $params);
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
	public function multiSelect(array $selectspecs)
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
			$subquery = "(SELECT " . $this->db->getPDO()->quote($selectkey) . ' AS selectkey';

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

		$stmt = $this->db->query($query, $queryParams);
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
	 * Return the results of all the SELECT operations specified by the supplied selectspec parameters, while also
	 * performing all pending selects that have not yet been executed. If only one parameter is supplied, return its
	 * result, otherwise return an array of results indexed as per the parameters.
	 * @return mixed
	 */
	public function selectWithPending() // any number of parameters read via func_get_args()
	{
		require_once QA_INCLUDE_DIR . 'app/options.php';

		$selectspecs = func_get_args();
		$singleresult = (count($selectspecs) == 1);
		$outresults = array();

		foreach ($selectspecs as $key => $selectspec) { // can pass null parameters
			if (empty($selectspec)) {
				unset($selectspecs[$key]);
				$outresults[$key] = null;
			}
		}

		foreach ($this->pendingSelects as $pendingid => $selectspec) {
			if (!isset($this->pendingResults[$pendingid])) {
				$selectspecs['pending_' . $pendingid] = $selectspec;
			}
		}

		$outresults = $outresults + $this->multiSelect($selectspecs);

		foreach ($this->pendingSelects as $pendingid => $selectspec) {
			if (!isset($this->pendingResults[$pendingid])) {
				$this->pendingResults[$pendingid] = $outresults['pending_' . $pendingid];
				unset($outresults['pending_' . $pendingid]);
			}
		}

		return $singleresult ? $outresults[0] : $outresults;
	}

	/**
	 * Queue a $selectspec for running later, with $pendingid (used for retrieval)
	 * @param string $pendingid
	 * @param array $selectspec
	 */
	public function queuePending($pendingid, $selectspec)
	{
		$this->pendingSelects[$pendingid] = $selectspec;
	}

	/**
	 * Get the result of the queued SELECT query identified by $pendingid. Run the query if it hasn't run already. If
	 * $selectspec is supplied, it doesn't matter if this hasn't been queued before - it will be queued and run now.
	 * @param string $pendingid
	 * @param array|null $selectspec
	 * @return mixed
	 */
	public function getPendingResult($pendingid, $selectspec = null)
	{
		if (isset($selectspec)) {
			$this->queuePending($pendingid, $selectspec);
		} elseif (!isset($this->pendingSelects[$pendingid])) {
			qa_fatal_error('Pending query was never set up: ' . $pendingid);
		}

		if (!isset($this->pendingResults[$pendingid])) {
			$this->selectWithPending();
		}

		return $this->pendingResults[$pendingid];
	}

	/**
	 * Remove the results of queued SELECT query identified by $pendingid if it has already been run. This means it will
	 * run again if its results are requested via getPendingResult()
	 * @param string $pendingid
	 */
	public function flushPendingResult($pendingid)
	{
		unset($this->pendingResults[$pendingid]);
	}

	/**
	 * Modify a selectspec to count the number of items. This assumes the original selectspec does not have a LIMIT clause.
	 * Currently works with message inbox/outbox functions and user-flags function.
	 * @param array $selectspec
	 * @return array
	 */
	public function selectspecWithCount($selectspec)
	{
		$selectspec['columns'] = array('count' => 'COUNT(*)');
		$selectspec['single'] = true;
		unset($selectspec['arraykey']);

		return $selectspec;
	}


	/**
	 * Post-process $outresult according to $selectspec, applying 'sortasc', 'sortdesc', 'arrayvalue' and 'single'.
	 * @param array $outresult
	 * @param array $selectspec
	 */
	private function formatSelect(array &$outresult, array $selectspec)
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
}
