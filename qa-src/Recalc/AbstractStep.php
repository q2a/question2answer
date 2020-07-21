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

abstract class AbstractStep
{
	/** @var string */
	protected $state;

	/** @var bool */
	protected $isFinalStep = false;

	/**
	 * Initialize a step.
	 * @param State $state
	 */
	public function __construct(State $state)
	{
		require_once QA_INCLUDE_DIR . 'app/format.php'; // for qa_format_number()

		$this->state = $state;
	}

	/**
	 * Execute the step.
	 * @return bool
	 */
	abstract public function doStep();

	/**
	 * Get the current progress.
	 * @return string
	 */
	public function getMessage()
	{
		return '';
	}

	/**
	 * Whether to stop processing.
	 * @return bool
	 */
	public function isFinalStep()
	{
		return $this->isFinalStep;
	}

	/**
	 * Return the translated language ID string replacing the progress and total in it.
	 *
	 * @param string $langId Language string ID that contains 2 placeholders (^1 and ^2).
	 * @param int $progress Amount of processed elements.
	 * @param int $total Total amount of elements.
	 *
	 * @return string Returns the language string ID with their placeholders replaced with
	 * the formatted progress and total numbers.
	 */
	protected function progressLang($langId, $progress, $total)
	{
		return strtr(qa_lang($langId), array(
			'^1' => qa_format_number((int)$progress),
			'^2' => qa_format_number((int)$total),
		));
	}

	/**
	 * Factory method to instantiate the State.
	 * @param State $state
	 * @return AbstractStep|null
	 */
	public static function factory(State $state)
	{
		$class = $state->getOperationClass();
		return $class === null ? null : new $class($state);
	}
}
