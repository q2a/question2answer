<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Recalc/State.php
	Description: Class holding state of the current recalculation process.


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

class Q2A_Recalc_State
{
	public $state;
	public $operation;
	public $length;
	public $next;
	public $done;

	private $classes = array(
		'doreindexcontent' => 'ReindexContent',
		'doreindexcontent_pagereindex' => 'ReindexContentPageReindex',
		'doreindexcontent_postcount' => 'ReindexContentPostCount',
		'doreindexcontent_postreindex' => 'ReindexContentPostReindex',
		'doreindexposts_wordcount' => 'ReindexPostsWordCount',
		'dorecountposts' => 'RecountPosts',
		'dorecountposts_postcount' => 'RecountPostsPostCount',
		'dorecountposts_votecount' => 'RecountPostsVoteCount',
		'dorecountposts_acount' => 'RecountPostsACount',
		'dorecalcpoints' => 'RecalcPoints',
		'dorecalcpoints_usercount' => 'RecalcPointsUserCount',
		'dorecalcpoints_recalc' => 'RecalcPointsRecalc',
		'dorefillevents' => 'RefillEvents',
		'dorefillevents_qcount' => 'RefillEventsQCount',
		'dorefillevents_refill' => 'RefillEventsRefill',
		'dorecalccategories' => 'RecalcCategories',
		'dorecalccategories_postcount' => 'RecalcCategoriesPostCount',
		'dorecalccategories_postupdate' => 'RecalcCategoriesPostUpdate',
		'dorecalccategories_recount' => 'RecalcCategoriesRecount',
		'dorecalccategories_backpaths' => 'RecalcCategoriesBackPaths',
		'dodeletehidden' => 'DeleteHidden',
		'dodeletehidden_comments' => 'DeleteHiddenComments',
		'dodeletehidden_answers' => 'DeleteHiddenAnswers',
		'dodeletehidden_questions' => 'DeleteHiddenQuestions',
		'doblobstodisk' => 'BlobsToDisk',
		'doblobstodisk_move' => 'BlobsToDiskMove',
		'doblobstodb' => 'BlobsToDB',
		'doblobstodb_move' => 'BlobsToDBMove',
		'docachetrim' => 'CacheTrim',
		'docacheclear' => 'CacheClear',
		'docachetrim_process' => 'CacheClearProcess',
		'docacheclear_process' => 'CacheClearProcess',

		'doreindexposts_complete' => 'ReindexPostsComplete',
		'dorecountposts_complete' => 'RecountPostsComplete',
		'dorecalcpoints_complete' => 'RecalcPointsComplete',
		'dorefillevents_complete' => 'RefillEventsComplete',
		'dorecalccategories_complete' => 'RecalcCategoriesComplete',
		'dodeletehidden_complete' => 'DeleteHiddenComplete',
		'doblobstodisk_complete' => 'BlobsMoveComplete',
		'doblobstodb_complete' => 'BlobsMoveComplete',
		'docacheclear_complete' => 'CacheClearComplete',
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
		return isset($this->classes[$this->operation]) ? 'Q2A_Recalc_' . $this->classes[$this->operation] : null;
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
