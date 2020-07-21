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

class State
{
	public $state;
	public $operation;
	public $length;
	public $next;
	public $done;

	private $classes = array(
		'doreindexcontent' => '\Q2A\Recalc\ReindexContent',
		'doreindexcontent_pagereindex' => '\Q2A\Recalc\ReindexContentPageReindex',
		'doreindexcontent_postcount' => '\Q2A\Recalc\ReindexContentPostCount',
		'doreindexcontent_postreindex' => '\Q2A\Recalc\ReindexContentPostReindex',
		'doreindexposts_wordcount' => '\Q2A\Recalc\ReindexPostsWordCount',
		'dorecountposts' => '\Q2A\Recalc\RecountPosts',
		'dorecountposts_postcount' => '\Q2A\Recalc\RecountPostsPostCount',
		'dorecountposts_votecount' => '\Q2A\Recalc\RecountPostsVoteCount',
		'dorecountposts_acount' => '\Q2A\Recalc\RecountPostsACount',
		'dorecalcpoints' => '\Q2A\Recalc\RecalcPoints',
		'dorecalcpoints_usercount' => '\Q2A\Recalc\RecalcPointsUserCount',
		'dorecalcpoints_recalc' => '\Q2A\Recalc\RecalcPointsRecalc',
		'dorefillevents' => '\Q2A\Recalc\RefillEvents',
		'dorefillevents_qcount' => '\Q2A\Recalc\RefillEventsQCount',
		'dorefillevents_refill' => '\Q2A\Recalc\RefillEventsRefill',
		'dorecalccategories' => '\Q2A\Recalc\RecalcCategories',
		'dorecalccategories_postcount' => '\Q2A\Recalc\RecalcCategoriesPostCount',
		'dorecalccategories_postupdate' => '\Q2A\Recalc\RecalcCategoriesPostUpdate',
		'dorecalccategories_recount' => '\Q2A\Recalc\RecalcCategoriesRecount',
		'dorecalccategories_backpaths' => '\Q2A\Recalc\RecalcCategoriesBackPaths',
		'dodeletehidden' => '\Q2A\Recalc\DeleteHidden',
		'dodeletehidden_comments' => '\Q2A\Recalc\DeleteHiddenComments',
		'dodeletehidden_answers' => '\Q2A\Recalc\DeleteHiddenAnswers',
		'dodeletehidden_questions' => '\Q2A\Recalc\DeleteHiddenQuestions',
		'doblobstodisk' => '\Q2A\Recalc\BlobsToDisk',
		'doblobstodisk_move' => '\Q2A\Recalc\BlobsToDiskMove',
		'doblobstodb' => '\Q2A\Recalc\BlobsToDB',
		'doblobstodb_move' => '\Q2A\Recalc\BlobsToDBMove',
		'docachetrim' => '\Q2A\Recalc\CacheTrim',
		'docacheclear' => '\Q2A\Recalc\CacheClear',
		'docachetrim_process' => '\Q2A\Recalc\CacheClearProcess',
		'docacheclear_process' => '\Q2A\Recalc\CacheClearProcess',

		'doreindexposts_complete' => '\Q2A\Recalc\ReindexPostsComplete',
		'dorecountposts_complete' => '\Q2A\Recalc\RecountPostsComplete',
		'dorecalcpoints_complete' => '\Q2A\Recalc\RecalcPointsComplete',
		'dorefillevents_complete' => '\Q2A\Recalc\RefillEventsComplete',
		'dorecalccategories_complete' => '\Q2A\Recalc\RecalcCategoriesComplete',
		'dodeletehidden_complete' => '\Q2A\Recalc\DeleteHiddenComplete',
		'doblobstodisk_complete' => '\Q2A\Recalc\BlobsMoveComplete',
		'doblobstodb_complete' => '\Q2A\Recalc\BlobsMoveComplete',
		'docacheclear_complete' => '\Q2A\Recalc\CacheClearComplete',
	);

	/**
	 * Initialize the counts of resource usage.
	 * @param string $state
	 */
	public function __construct($state)
	{
		$this->setState($state);
	}

	/**
	 * Get the state.
	 * @return string
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Set the state.
	 * @param string $state
	 * @return void
	 */
	public function setState($state = '')
	{
		$this->state = $state;
		list($this->operation, $this->length, $this->next, $this->done) = explode("\t", $state . "\t\t\t\t");
	}

	/**
	 * Update the state.
	 * @return void
	 */
	public function updateState()
	{
		$this->state = $this->operation . "\t" . $this->length . "\t" . $this->next . "\t" . $this->done;
	}

	/**
	 * Get the class that will handle this operation.
	 * @return string|null
	 */
	public function getOperationClass()
	{
		return isset($this->classes[$this->operation]) ? $this->classes[$this->operation] : null;
	}

	/**
	 * Whether all steps are completed.
	 * @return bool
	 */
	public function allDone()
	{
		return $this->done >= $this->length;
	}

	/**
	 * Change the $state to represent the beginning of a new $operation.
	 * @param string $newOperation
	 * @return void
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
	 * Return how many steps there will be in recalculation operation.
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
				$cacheDriver = \Q2A\Storage\CacheFactory::getCacheDriver();
				$cacheStats = $cacheDriver->getStats();
				return $cacheStats['files'];
		}

		return 0;
	}
}
