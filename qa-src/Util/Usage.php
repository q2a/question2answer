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

namespace Q2A\Util;

/**
 * Debugging stuff, currently used for tracking resource usage.
 */
class Usage
{
	private $stages;
	private $startUsage;
	private $prevUsage;
	private $databaseUsage;
	private $databaseQueryLog;

	/**
	 * Initialize the counts of resource usage.
	 */
	public function __construct()
	{
		$this->stages = array();
		$this->databaseUsage = array('queries' => 0, 'clock' => 0);
		$this->databaseQueryLog = '';

		$this->prevUsage = $this->startUsage = $this->getCurrent();
	}

	/**
	 * Return an array representing the resource usage as of now.
	 * @return array
	 */
	public function getCurrent()
	{
		$usage = array(
			'files' => count(get_included_files()),
			'queries' => $this->databaseUsage['queries'],
			'ram' => function_exists('memory_get_usage') ? memory_get_usage() : 0,
			'clock' => array_sum(explode(' ', microtime())),
			'mysql' => $this->databaseUsage['clock'],
		);

		if (function_exists('getrusage')) {
			$rusage = getrusage();
			$usage['cpu'] = $rusage["ru_utime.tv_sec"] + $rusage["ru_stime.tv_sec"]
				+ ($rusage["ru_utime.tv_usec"] + $rusage["ru_stime.tv_usec"]) / 1000000;
		} else {
			$usage['cpu'] = 0;
		}

		$usage['other'] = $usage['clock'] - $usage['cpu'] - $usage['mysql'];

		return $usage;
	}

	/**
	 * Mark the beginning of a new stage of script execution and store usages accordingly.
	 * @param string $stage
	 */
	public function mark($stage)
	{
		$usage = $this->getCurrent();
		$this->stages[$stage] = $this->delta($this->prevUsage, $usage);
		$this->prevUsage = $usage;
	}

	/**
	 * Logs query and updates database usage stats.
	 * @param string $query
	 * @param int $usedtime
	 * @param int $gotrows
	 * @param int $gotcolumns
	 */
	public function logDatabaseQuery($query, $usedtime, $gotrows, $gotcolumns)
	{
		$this->databaseUsage['clock'] += $usedtime;

		if (strlen($this->databaseQueryLog) < 1048576) { // don't keep track of big tests
			$rowcolstring = '';
			if (is_numeric($gotrows)) {
				$rowcolstring .= ' - ' . $gotrows . ($gotrows == 1 ? ' row' : ' rows');
			}
			if (is_numeric($gotcolumns)) {
				$rowcolstring .= ' - ' . $gotcolumns . ($gotcolumns == 1 ? ' column' : ' columns');
			}

			$this->databaseQueryLog .= $query . "\n\n" . sprintf('%.2f ms', $usedtime * 1000) . $rowcolstring . "\n\n";
		}

		$this->databaseUsage['queries']++;
	}

	/**
	 * Output an (ugly) block of HTML detailing all resource usage and database queries.
	 */
	public function output()
	{
		$totaldelta = $this->delta($this->startUsage, $this->getCurrent());
		$stages = $this->stages;
		$stages['total'] = $totaldelta;
		?>
		<style>
		.debug-table { border-collapse: collapse; width: auto; margin: 20px auto; }
		.debug-table th, .debug-table td { border: 1px solid #aaa; background-color: #ddd; padding: 5px 10px; }
		.debug-table td { text-align: right; }
		.debug-table th:empty { border: none; background-color: initial; }
		.debug-table .row-heading { font-weight: bold; }

		.debug-table tr:last-child td { background-color: #ccc; border-top-width: 3px; }

		textarea.debug-output { box-sizing: border-box; width: 100%; font: 12px monospace; color: #000; }

		.extra-info { border-collapse: collapse; box-sizing: border-box; width: 100%; }
		.extra-info td.debug-cell-files { width: 30%; padding: 10px 5px 10px 10px; }
		.extra-info td.debug-cell-queries { width: 70%; padding: 10px 10px 10px 5px; }
		.extra-info tr { background-color: #ccc; }
		.extra-info textarea { margin: 0; }
		</style>

		<table class="debug-table">
			<thead>
				<tr>
					<th></th>
					<th colspan="2">Total</th>
					<th colspan="3">PHP</th>
					<th colspan="3">MySQL</th>
					<th colspan="2">Other</th>
					<th colspan="2">RAM</th>
				</tr>
				<tr>
					<th></th>
					<th>Time (ms)</th>
					<th>%</th>
					<th>Time (ms)</th>
					<th>%</th>
					<th>File count</th>
					<th>Time (ms)</th>
					<th>%</th>
					<th>Query count</th>
					<th>Time (ms)</th>
					<th>%</th>
					<th>Amount</th>
					<th>%</th>
				</tr>
			</thead>
		<tbody>
		<?php foreach ($stages as $stage => $stagedelta) : ?>
			<tr>
				<td class="row-heading"><?php echo ucfirst($stage); ?></td>
				<td><?php echo sprintf('%.1f', $stagedelta['clock'] * 1000); ?></td>
				<td><?php echo sprintf('%d%%', $stagedelta['clock'] * 100 / $totaldelta['clock']); ?></td>
				<td><?php echo sprintf('%.1f', $stagedelta['cpu'] * 1000); ?></td>
				<td><?php echo sprintf('%d%%', $stagedelta['cpu'] * 100 / $totaldelta['clock']); ?></td>
				<td><?php echo $stagedelta['files']; ?></td>
				<td><?php echo sprintf('%.1f', $stagedelta['mysql'] * 1000); ?></td>
				<td><?php echo sprintf('%d%%', $stagedelta['mysql'] * 100 / $totaldelta['clock']); ?></td>
				<td><?php echo $stagedelta['queries']; ?></td>
				<td><?php echo sprintf('%.1f', $stagedelta['other'] * 1000); ?></td>
				<td><?php echo sprintf('%d%%', $stagedelta['other'] * 100 / $totaldelta['clock']); ?></td>
				<td><?php echo sprintf('%dk', $stagedelta['ram'] / 1024); ?></td>
				<td><?php echo sprintf('%d%%', $stagedelta['ram'] ? ($stagedelta['ram'] * 100 / $totaldelta['ram']) : 0); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
		</table>

		<table class="extra-info">
		<tbody>
			<tr>
				<td class="debug-cell-files">
					<textarea class="debug-output" cols="40" rows="20"><?php
					foreach (get_included_files() as $file) {
						echo qa_html(implode('/', array_slice(explode('/', $file), -3)))."\n";
					}
					?></textarea>
				</td>
				<td class="debug-cell-queries">
					<textarea class="debug-output" cols="40" rows="20"><?php echo qa_html($this->databaseQueryLog)?></textarea>
				</td>
			</tr>
		</tbody>
		</table>
		<?php
	}


	/**
	 * Return the difference between two resource usage arrays, as an array.
	 * @param array $oldusage
	 * @param array $newusage
	 * @return array
	 */
	private function delta($oldusage, $newusage)
	{
		$delta = array();

		foreach ($newusage as $key => $value) {
			$delta[$key] = max(0, $value - @$oldusage[$key]);
		}

		return $delta;
	}
}
