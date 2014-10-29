<?php
/**
 * @deprecated This file is deprecated from Q2A 1.7; use Q2A_Util_Usage class (Q2A/Util/Usage.php) instead.
 *
 * The functions in this file are maintained for backwards compatibility, but simply call through to the
 * new class where applicable.
 */

function qa_usage_init()
{
	// should already be initialised in qa-base.php
	global $qa_usage;
	if (empty($qa_usage))
		$qa_usage = new Q2A_Util_Usage;
}

function qa_usage_get()
{
	global $qa_usage;
	return $qa_usage->getCurrent();
}

function qa_usage_delta($oldusage, $newusage)
{
	// equivalent function is now private
	return array();
}

function qa_usage_mark($stage)
{
	global $qa_usage;
	return $qa_usage->mark($stage);
}

function qa_usage_line($stage, $usage, $totalusage)
{
	// equivalent function is now private
	return '';
}

function qa_usage_output()
{
	global $qa_usage;
	return $qa_usage->output();
}
