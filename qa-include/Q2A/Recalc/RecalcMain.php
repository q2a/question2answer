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

/*
	A full list of redundant (non-normal) information in the database that can be recalculated:

	Recalculated in doreindexcontent:
	================================
	^titlewords (all): index of words in titles of posts
	^contentwords (all): index of words in content of posts
	^tagwords (all): index of words in tags of posts (a tag can contain multiple words)
	^posttags (all): index tags of posts
	^words (all): list of words used for indexes
	^options (title=cache_*): cached values for various things (e.g. counting questions)

	Recalculated in dorecountposts:
	==============================
	^posts (upvotes, downvotes, netvotes, hotness, acount, amaxvotes, flagcount): number of votes, hotness, answers, answer votes, flags

	Recalculated in dorecalcpoints:
	===============================
	^userpoints (all except bonus): points calculation for all users
	^options (title=cache_userpointscount):

	Recalculated in dorecalccategories:
	===================================
	^posts (categoryid): assign to answers and comments based on their antecedent question
	^posts (catidpath1, catidpath2, catidpath3): hierarchical path to category ids (requires QA_CATEGORY_DEPTH=4)
	^categories (qcount): number of (visible) questions in each category
	^categories (backpath): full (backwards) path of slugs to that category

	Recalculated in dorebuildupdates:
	=================================
	^sharedevents (all): per-entity event streams (see big comment in /qa-include/db/favorites.php)
	^userevents (all): per-subscriber event streams

	[but these are not entirely redundant since they can contain historical information no longer in ^posts]
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'db/recalc.php';
require_once QA_INCLUDE_DIR . 'db/post-create.php';
require_once QA_INCLUDE_DIR . 'db/points.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'db/admin.php';
require_once QA_INCLUDE_DIR . 'db/users.php';
require_once QA_INCLUDE_DIR . 'app/options.php';
require_once QA_INCLUDE_DIR . 'app/post-create.php';
require_once QA_INCLUDE_DIR . 'app/post-update.php';

class Q2A_Recalc_RecalcMain
{
	protected $state;

	/**
	 * Initialize the counts of resource usage.
	 */
	public function __construct($state)
	{
		$this->state = new Q2A_Recalc_State($state);
	}

	public function getState()
	{
		return $this->state->getState();
	}

	public function performStep()
	{
		$step = Q2A_Recalc_AbstractStep::factory($this->state);

		if (!$step) {
			$this->state->setState();
			return false;
		}

		$continue = $step->doStep();
		if ($continue) {
			$this->state->updateState();
		}

		return $continue && !$this->state->allDone();
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
	private function progressLang($langId, $progress, $total)
	{
		return strtr(qa_lang($langId), array(
			'^1' => qa_format_number($progress),
			'^2' => qa_format_number($total),
		));
	}

	/**
	 * Return a string which gives a user-viewable version of $state
	 * @return string
	 */
	public function getMessage()
	{
		require_once QA_INCLUDE_DIR . 'app/format.php';

		$this->state->done = (int) $this->state->done;
		$this->state->length = (int) $this->state->length;

		switch ($this->state->operation) {
			case 'doreindexcontent_postcount':
			case 'dorecountposts_postcount':
			case 'dorecalccategories_postcount':
			case 'dorefillevents_qcount':
				$message = qa_lang('admin/recalc_posts_count');
				break;

			case 'doreindexcontent_pagereindex':
				$message = $this->progressLang('admin/reindex_pages_reindexed', $this->state->done, $this->state->length);
				break;

			case 'doreindexcontent_postreindex':
				$message = $this->progressLang('admin/reindex_posts_reindexed', $this->state->done, $this->state->length);
				break;

			case 'doreindexposts_complete':
				$message = qa_lang('admin/reindex_posts_complete');
				break;

			case 'doreindexposts_wordcount':
				$message = $this->progressLang('admin/reindex_posts_wordcounted', $this->state->done, $this->state->length);
				break;

			case 'dorecountposts_votecount':
				$message = $this->progressLang('admin/recount_posts_votes_recounted', $this->state->done, $this->state->length);
				break;

			case 'dorecountposts_acount':
				$message = $this->progressLang('admin/recount_posts_as_recounted', $this->state->done, $this->state->length);
				break;

			case 'dorecountposts_complete':
				$message = qa_lang('admin/recount_posts_complete');
				break;

			case 'dorecalcpoints_usercount':
				$message = qa_lang('admin/recalc_points_usercount');
				break;

			case 'dorecalcpoints_recalc':
				$message = $this->progressLang('admin/recalc_points_recalced', $this->state->done, $this->state->length);
				break;

			case 'dorecalcpoints_complete':
				$message = qa_lang('admin/recalc_points_complete');
				break;

			case 'dorefillevents_refill':
				$message = $this->progressLang('admin/refill_events_refilled', $this->state->done, $this->state->length);
				break;

			case 'dorefillevents_complete':
				$message = qa_lang('admin/refill_events_complete');
				break;

			case 'dorecalccategories_postupdate':
				$message = $this->progressLang('admin/recalc_categories_updated', $this->state->done, $this->state->length);
				break;

			case 'dorecalccategories_recount':
				$message = $this->progressLang('admin/recalc_categories_recounting', $this->state->done, $this->state->length);
				break;

			case 'dorecalccategories_backpaths':
				$message = $this->progressLang('admin/recalc_categories_backpaths', $this->state->done, $this->state->length);
				break;

			case 'dorecalccategories_complete':
				$message = qa_lang('admin/recalc_categories_complete');
				break;

			case 'dodeletehidden_comments':
				$message = $this->progressLang('admin/hidden_comments_deleted', $this->state->done, $this->state->length);
				break;

			case 'dodeletehidden_answers':
				$message = $this->progressLang('admin/hidden_answers_deleted', $this->state->done, $this->state->length);
				break;

			case 'dodeletehidden_questions':
				$message = $this->progressLang('admin/hidden_questions_deleted', $this->state->done, $this->state->length);
				break;

			case 'dodeletehidden_complete':
				$message = qa_lang('admin/delete_hidden_complete');
				break;

			case 'doblobstodisk_move':
			case 'doblobstodb_move':
				$message = $this->progressLang('admin/blobs_move_moved', $this->state->done, $this->state->length);
				break;

			case 'doblobstodisk_complete':
			case 'doblobstodb_complete':
				$message = qa_lang('admin/blobs_move_complete');
				break;

			case 'docachetrim_process':
			case 'docacheclear_process':
				$message = $this->progressLang('admin/caching_delete_progress', $this->state->done, $this->state->length);
				break;

			case 'docacheclear_complete':
				$message = qa_lang('admin/caching_delete_complete');
				break;

			default:
				$message = '';
				break;
		}

		return $message;
	}
}
