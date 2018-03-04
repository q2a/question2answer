<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Recalc/AbstractStep.php
	Description: Base class for step classes in the recal processes.


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'app/format.php';		//required for qa_number_format()

abstract class Q2A_Recalc_AbstractStep
{
	protected $state;
	protected $isFinalStep = false;

	public function __construct(Q2A_Recalc_State $state)
	{
		$this->state = $state;
	}

	abstract public function doStep();

	public function getMessage()
	{
		return '';
	}

	public function isFinalStep()
	{
		return $this->isFinalStep;
	}

	/**
	 * Return the translated language ID string replacing the progress and total in it.
	 * @access private
	 * @param string $langId Language string ID that contains 2 placeholders (^1 and ^2)
	 * @param int $progress Amount of processed elements
	 * @param int $total Total amount of elements
	 *
	 * @return string Returns the language string ID with their placeholders replaced with
	 * the formatted progress and total numbers
	 */
	protected function progressLang($langId, $progress, $total)
	{
		return strtr(qa_lang($langId), array(
			'^1' => qa_format_number((int)$progress),
			'^2' => qa_format_number((int)$total),
		));
	}

	public static function factory(Q2A_Recalc_State $state)
	{
		$class = $state->getOperationClass();
		if (class_exists($class)) {
			return new $class($state);
		}
		return null;
	}
}
