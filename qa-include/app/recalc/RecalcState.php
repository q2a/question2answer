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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

class Q2A_App_Recalc_State
{
	public $state;
	public $operation;
	public $length;
	public $next;
	public $done;

	private $classes = array(
		'doreindexcontent' => 'DoReindexContent',
		'doreindexcontent_pagereindex' => 'DoReindexContent_PageReindex',
		'doreindexcontent_postcount' => 'DoReindexContent_PostCount',
		'doreindexcontent_postreindex' => 'DoReindexContent_PostReindex',
		'doreindexposts_wordcount' => 'DoReindexPosts_WordCount',
		'dorecountposts' => 'DoRecountPosts',
		'dorecountposts_postcount' => 'DoRecountPosts_PostCount',
		'dorecountposts_votecount' => 'DoRecountPosts_VoteCount',
		'dorecountposts_acount' => 'DoRecountPosts_Acount',
		'dorecalcpoints' => 'DoRecalcPoints',
		'dorecalcpoints_usercount' => 'DoRecalcPoints_UserCount',
		'dorecalcpoints_recalc' => 'DoRecalcPoints_Recalc',
		'dorefillevents' => 'DoRefillEvents',
		'dorefillevents_qcount' => 'DoRefillEvents_Qcount',
		'dorefillevents_refill' => 'DoRefillEvents_Refill',
		'dorecalccategories' => 'DoRecalcCategories',
		'dorecalccategories_postcount' => 'DoRecalcCategories_PostCount',
		'dorecalccategories_postupdate' => 'DoRecalcCategories_PostUpdate',
		'dorecalccategories_recount' => 'DoRecalcCategories_Recount',
		'dorecalccategories_backpaths' => 'DoRecalcCategories_BackPaths',
		'dodeletehidden' => 'DoDeleteHidden',
		'dodeletehidden_comments' => 'DoDeleteHidden_Comments',
		'dodeletehidden_answers' => 'DoDeleteHidden_Answers',
		'dodeletehidden_questions' => 'Dodeletehidden_questions',
		'doblobstodisk' => 'DoBlobsToDisk',
		'doblobstodisk_move' => 'DoBlobsToDisk_Move',
		'doblobstodb' => 'DoBlobsToDB',
		'doblobstodb_move' => 'DoBlobsToDB_Move',
		'docachetrim' => 'DoCacheTrim',
		'docacheclear' => 'DoCacheClear',
		'docachetrim_process' => 'DoCacheClear_Process',
		'docacheclear_process' => 'DoCacheClear_Process'
	);

	/**
	 * Initialize the counts of resource usage.
	 */
	public function __construct($state)
	{
		$this->setState($state);
	}

	public function getState()
	{
		return $this->state;
	}

	public function setState($state = '')
	{
		$this->state = $state;
		list($this->operation, $this->length, $this->next, $this->done) = explode("\t", $state . "\t\t\t\t");
	}

	public function updateState()
	{
		$this->state = $this->operation . "\t" . $this->length . "\t" . $this->next . "\t" . $this->done;
	}

	public function getOperationClass()
	{
		return isset($this->classes[$this->operation]) ? $this->classes[$this->operation] : null;
	}

	public function allDone()
	{
		return $this->done >= $this->length;
	}

	/**
	 * Change the $state to represent the beginning of a new $operation
	 * @param $newOperation
	 */
	public function transition($newOperation)
	{
		$this->operation = $newOperation;
		$this->length = $this->stageLength();
		$this->next = (QA_FINAL_EXTERNAL_USERS && ($newOperation == 'dorecalcpoints_recalc')) ? '' : 0;
		$this->done = 0;

		$this->updateState();
	}

	/**
	 * Return how many steps there will be in recalculation $operation
	 * @return int
	 */
	private function stageLength()
	{
		switch ($this->operation) {
			case 'doreindexcontent_pagereindex':
				return qa_db_count_pages();

			case 'doreindexcontent_postreindex':
				return qa_opt('cache_qcount') + qa_opt('cache_acount') + qa_opt('cache_ccount');

			case 'doreindexposts_wordcount':
				return qa_db_count_words();

			case 'dorecalcpoints_recalc':
				return qa_opt('cache_userpointscount');

			case 'dorecountposts_votecount':
			case 'dorecountposts_acount':
			case 'dorecalccategories_postupdate':
				return qa_db_count_posts();

			case 'dorefillevents_refill':
				return qa_opt('cache_qcount') + qa_db_count_posts('Q_HIDDEN');

			case 'dorecalccategories_recount':
			case 'dorecalccategories_backpaths':
				return qa_db_count_categories();

			case 'dodeletehidden_comments':
				return count(qa_db_posts_get_for_deleting('C'));

			case 'dodeletehidden_answers':
				return count(qa_db_posts_get_for_deleting('A'));

			case 'dodeletehidden_questions':
				return count(qa_db_posts_get_for_deleting('Q'));

			case 'doblobstodisk_move':
				return qa_db_count_blobs_in_db();

			case 'doblobstodb_move':
				return qa_db_count_blobs_on_disk();

			case 'docachetrim_process':
			case 'docacheclear_process':
				$cacheDriver = Q2A_Storage_CacheFactory::getCacheDriver();
				$cacheStats = $cacheDriver->getStats();
				return $cacheStats['files'];
		}

		return 0;
	}
}
