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

namespace Q2A\Recalc;

class RecalcMain
{
	protected $state;

	/**
	 * Initialize the counts of resource usage.
	 * @param string $state
	 */
	public function __construct($state)
	{
		require_once QA_INCLUDE_DIR . 'db/recalc.php';
		require_once QA_INCLUDE_DIR . 'db/post-create.php';
		require_once QA_INCLUDE_DIR . 'db/points.php';
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'db/admin.php';
		require_once QA_INCLUDE_DIR . 'db/users.php';
		require_once QA_INCLUDE_DIR . 'app/options.php';
		require_once QA_INCLUDE_DIR . 'app/post-create.php';
		require_once QA_INCLUDE_DIR . 'app/post-update.php';

		$this->state = new \Q2A\Recalc\State($state);
	}

	/**
	 * Get the state.
	 * @return string
	 */
	public function getState()
	{
		return $this->state->getState();
	}

	/**
	 * Do the recalculation.
	 * @return bool
	 */
	public function performStep()
	{
		$step = AbstractStep::factory($this->state);

		if (!$step || $step->isFinalStep()) {
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
	 * Return a string which gives a user-viewable version of $state.
	 * @return string
	 */
	public function getMessage()
	{
		$step = AbstractStep::factory($this->state);
		return $step ? $step->getMessage() : '';
	}
}
