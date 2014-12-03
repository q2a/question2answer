<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Util/Usage.php
	Description: Debugging stuff, currently used for tracking resource usage


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

class Q2A_Util_Usage
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
		$this->databaseUsage = array('queries'=>0, 'clock'=>0);
		$this->databaseQueryLog = '';

		$this->prevUsage = $this->startUsage = $this->getCurrent();
	}

	/**
	 * Return an array representing the resource usage as of now.
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
		}
		else
			$usage['cpu'] = 0;

		$usage['other'] = $usage['clock'] - $usage['cpu'] - $usage['mysql'];

		return $usage;
	}

	/**
	 * Mark the beginning of a new stage of script execution and store usages accordingly.
	 */
	public function mark($stage)
	{
		$usage = $this->getCurrent();
		$this->stages[$stage] = $this->delta($this->prevUsage, $usage);
		$this->prevUsage = $usage;
	}

	/**
	 * Logs query and updates database usage stats.
	 */
	public function logDatabaseQuery($query, $usedtime, $gotrows, $gotcolumns)
	{
		$this->databaseUsage['clock'] += $usedtime;

		if (strlen($this->databaseQueryLog) < 1048576) { // don't keep track of big tests
			$rowcolstring = '';
			if (is_numeric($gotrows))
				$rowcolstring .= ' - ' . $gotrows . ($gotrows == 1 ? ' row' : ' rows');
			if (is_numeric($gotcolumns))
				$rowcolstring .= ' - ' . $gotcolumns . ($gotcolumns == 1 ? ' column' : ' columns');

			$this->databaseQueryLog .= $query . "\n\n" . sprintf('%.2f ms', $usedtime*1000) . $rowcolstring . "\n\n";
		}

		$this->databaseUsage['queries']++;
	}

	/**
	 * Output an (ugly) block of HTML detailing all resource usage and database queries.
	 */
	public function output()
	{
		$totaldelta = $this->delta($this->startUsage, $this->getCurrent());
?>
		<style>
		.debug-table { border-collapse: collapse; box-sizing: border-box; width: 100%; margin: 20px auto; }
		.debug-table tr { background-color: #ccc; }
		.debug-table td { padding: 10px; }

		td.debug-cell-files { width: 30%; padding-right: 5px; }
		td.debug-cell-queries { width: 70%; padding-left: 5px; }

		textarea.debug-output { box-sizing: border-box; width: 100%; font: 12px monospace; color: #000; }
		</style>

		<table class="debug-table">
		<tbody>
			<tr>
				<td colspan="2"><?php
					echo $this->line('Total', $totaldelta, $totaldelta) . "<br>\n";
					foreach ($this->stages as $stage => $stagedelta)
						echo '<br>' . $this->line(ucfirst($stage), $stagedelta, $totaldelta) . "\n";
				?></td>
			</tr>
			<tr>
				<td class="debug-cell-files">
					<textarea class="debug-output" cols="40" rows="20"><?php
						foreach (get_included_files() as $file)
							echo qa_html(implode('/', array_slice(explode('/', $file), -3)))."\n";
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
	 */
	private function delta($oldusage, $newusage)
	{
		$delta = array();

		foreach ($newusage as $key => $value)
			$delta[$key] = max(0, $value-@$oldusage[$key]);

		return $delta;
	}

	/**
	 * Return HTML to represent the resource $usage, showing appropriate proportions of $totalusage.
	 */
	private function line($stage, $usage, $totalusage)
	{
		return sprintf(
			"%s &ndash; <b>%.1fms</b> (%d%%) &ndash; PHP %.1fms (%d%%), MySQL %.1fms (%d%%), Other %.1fms (%d%%) &ndash; %d PHP %s, %d DB %s, %dk RAM (%d%%)",
			$stage,
			$usage['clock'] * 1000,
			$usage['clock'] * 100 / $totalusage['clock'],
			$usage['cpu'] * 1000,
			$usage['cpu'] * 100 / $totalusage['clock'],
			$usage['mysql'] * 1000,
			$usage['mysql'] * 100 / $totalusage['clock'],
			$usage['other'] * 1000,
			$usage['other'] * 100 / $totalusage['clock'],
			$usage['files'],
			$usage['files'] == 1 ? 'file' : 'files',
			$usage['queries'],
			$usage['queries'] == 1 ? 'query' : 'queries',
			$usage['ram'] / 1024,
			$usage['ram'] ? ($usage['ram'] * 100 / $totalusage['ram']) : 0
		);
	}
}
