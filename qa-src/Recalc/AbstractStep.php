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

namespace Q2A\Recalc;

require_once QA_INCLUDE_DIR . 'app/format.php';        //required for qa_number_format()

abstract class AbstractStep
{
	protected $state;
	protected $isFinalStep = false;

	public function __construct(State $state)
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

	public static function factory(State $state)
	{
		$class = $state->getOperationClass();
		return $class === null ? null : new $class($state);
	}
}
