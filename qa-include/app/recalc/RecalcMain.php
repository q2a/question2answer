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

require_once QA_INCLUDE_DIR . 'app/recalc/RecalcState.php';

class Q2A_App_Recalc_Main
{
	protected $state;

	/**
	 * Initialize the counts of resource usage.
	 */
	public function __construct($state)
	{
		$this->state = new Q2A_App_Recalc_State($state);
	}

	public function getState()
	{
		return $this->state->getState();
	}

	public function performStep()
	{
		if (!$this->state->getOperationClass()) {
			$this->state->setState();
			return false;
		}

		$continue = $this->{$this->state->getOperationClass()}();
		if ($continue) {
			$this->state->updateState();
		}

		return $continue && !$this->state->allDone();
	}

	private function DoReindexContent()
	{
		$this->state->transition('doreindexcontent_pagereindex');
	}

	private function DoReindexContent_PageReindex()
	{
		$pages = qa_db_pages_get_for_reindexing($this->next, 10);

		if (!count($pages)) {
			$this->state->transition('doreindexcontent_postcount');
			return false;
		}
			
		require_once QA_INCLUDE_DIR . 'app/format.php';

		$lastpageid = max(array_keys($pages));

		foreach ($pages as $pageid => $page) {
			if (!($page['flags'] & QA_PAGE_FLAGS_EXTERNAL)) {
				$searchmodules_u = qa_load_modules_with('search', 'unindex_page');
				foreach ($searchmodules_u as $searchmodule) {
					$searchmodule->unindex_page($pageid);
				}

				$searchmodules_i = qa_load_modules_with('search', 'index_page');
				if (count($searchmodules_i)) {
					$indextext = qa_viewer_text($page['content'], 'html');

					foreach ($searchmodules_i as $searchmodule) {
						$searchmodule->index_page($pageid, $page['tags'], $page['heading'], $page['content'], 'html', $indextext);
					}
				}
			}
		}

		$this->next = 1 + $lastpageid;
		$this->done += count($pages);
		return true;
	}

	private function DoReindexContent_PostCount()
	{
		qa_db_qcount_update();
		qa_db_acount_update();
		qa_db_ccount_update();

		$this->state->transition('doreindexcontent_postreindex');
		return false;
	}

	private function DoReindexContent_PostReindex()
	{
		$posts = qa_db_posts_get_for_reindexing($this->next, 10);

		if (!count($posts)) {
			qa_db_truncate_indexes($this->next);
			$this->state->transition('doreindexposts_wordcount');
			return false;
		}

		require_once QA_INCLUDE_DIR . 'app/format.php';

		$lastpostid = max(array_keys($posts));

		qa_db_prepare_for_reindexing($this->next, $lastpostid);
		qa_suspend_update_counts();

		foreach ($posts as $postid => $post) {
			qa_post_unindex($postid);
			qa_post_index($postid, $post['type'], $post['questionid'], $post['parentid'], $post['title'], $post['content'],
				$post['format'], qa_viewer_text($post['content'], $post['format']), $post['tags'], $post['categoryid']);
		}

		$this->next = 1 + $lastpostid;
		$this->done += count($posts);
		return true;
	}

	private function DoReindexPosts_WordCount()
	{
		$wordids = qa_db_words_prepare_for_recounting($this->next, 1000);

		if (!count($wordids)) {
			qa_db_tagcount_update(); // this is quick so just do it here
			$this->state->transition('doreindexposts_complete');
			return false;
		}

		$lastwordid = max($wordids);

		qa_db_words_recount($this->next, $lastwordid);

		$this->next = 1 + $lastwordid;
		$this->done += count($wordids);
		return true;
	}

	private function DoRecountPosts()
	{
		$this->state->transition('dorecountposts_postcount');
		return false;
	}

	private function DoRecountPosts_PostCount()
	{
		qa_db_qcount_update();
		qa_db_acount_update();
		qa_db_ccount_update();
		qa_db_unaqcount_update();
		qa_db_unselqcount_update();

		$this->state->transition('dorecountposts_votecount');
		return false;
	}

	private function DoRecountPosts_VoteCount()
	{
		$postids = qa_db_posts_get_for_recounting($this->next, 1000);

		if (!count($postids)) {
			$this->state->transition('dorecountposts_acount');
			return false;
		}

		$lastpostid = max($postids);

		qa_db_posts_votes_recount($this->next, $lastpostid);

		$this->next = 1 + $lastpostid;
		$this->done += count($postids);
		return true;
	}

	private function DoRecountPosts_Acount()
	{
		$postids = qa_db_posts_get_for_recounting($this->next, 1000);

		if (count($postids)) {
			qa_db_unupaqcount_update();
			$this->state->transition('dorecountposts_complete');
			return false;
		}

		$lastpostid = max($postids);

		qa_db_posts_answers_recount($this->next, $lastpostid);

		$this->next = 1 + $lastpostid;
		$this->done += count($postids);
		return true;
	}

	private function DoRecalcPoints()
	{
		$this->state->transition('dorecalcpoints_usercount');
		return false;
	}

	private function DoRecalcPoints_UserCount()
	{
		qa_db_userpointscount_update(); // for progress update - not necessarily accurate
		qa_db_uapprovecount_update(); // needs to be somewhere and this is the most appropriate place
		$this->state->transition('dorecalcpoints_recalc');
		return false;
	}

	private function DoRecalcPoints_Recalc()
	{
		$default_recalccount = 10;
		$userids = qa_db_users_get_for_recalc_points($this->next, $default_recalccount + 1); // get one extra so we know where to start from next
		$gotcount = count($userids);
		$recalccount = min($default_recalccount, $gotcount); // can't recalc more than we got

		if ($recalccount > 0) {
			$lastuserid = $userids[$recalccount - 1];
			qa_db_users_recalc_points($this->next, $lastuserid);
			$this->done += $recalccount;

		} else {
			$lastuserid = $this->next; // for truncation
		}

		if ($gotcount > $recalccount) { // more left to do
			$this->next = $userids[$recalccount]; // start next round at first one not recalculated
			return true;
		} else {
			qa_db_truncate_userpoints($lastuserid);
			qa_db_userpointscount_update(); // quick so just do it here
			$this->state->transition('dorecalcpoints_complete');
			return false;
		}
	}

	private function DoRefillEvents()
	{
		$this->state->transition('dorefillevents_qcount');
		return false;
	}

	private function DoRefillEvents_Qcount()
	{
		qa_db_qcount_update();
		$this->state->transition('dorefillevents_refill');
		return false;
	}

	private function DoRefillEvents_Refill()
	{
		$questionids = qa_db_qs_get_for_event_refilling($this->next, 1);

		if (!count($questionids)) {
			$this->state->transition('dorefillevents_complete');
			return false;
		}

		require_once QA_INCLUDE_DIR . 'app/events.php';
		require_once QA_INCLUDE_DIR . 'app/updates.php';
		require_once QA_INCLUDE_DIR . 'util/sort.php';

		$lastquestionid = max($questionids);

		foreach ($questionids as $questionid) {
			// Retrieve all posts relating to this question

			list($question, $childposts, $achildposts) = qa_db_select_with_pending(
				qa_db_full_post_selectspec(null, $questionid),
				qa_db_full_child_posts_selectspec(null, $questionid),
				qa_db_full_a_child_posts_selectspec(null, $questionid)
			);

			// Merge all posts while preserving keys as postids

			$posts = array($questionid => $question);

			foreach ($childposts as $postid => $post) {
				$posts[$postid] = $post;
			}

			foreach ($achildposts as $postid => $post) {
				$posts[$postid] = $post;
			}

			// Creation and editing of each post

			foreach ($posts as $postid => $post) {
				$followonq = ($post['basetype'] == 'Q') && ($postid != $questionid);

				if ($followonq) {
					$updatetype = QA_UPDATE_FOLLOWS;
				} elseif ($post['basetype'] == 'C' && @$posts[$post['parentid']]['basetype'] == 'Q') {
					$updatetype = QA_UPDATE_C_FOR_Q;
				} elseif ($post['basetype'] == 'C' && @$posts[$post['parentid']]['basetype'] == 'A') {
					$updatetype = QA_UPDATE_C_FOR_A;
				} else {
					$updatetype = null;
				}

				qa_create_event_for_q_user($questionid, $postid, $updatetype, $post['userid'], @$posts[$post['parentid']]['userid'], $post['created']);

				if (isset($post['updated']) && !$followonq) {
					qa_create_event_for_q_user($questionid, $postid, $post['updatetype'], $post['lastuserid'], $post['userid'], $post['updated']);
				}
			}

			// Tags and categories of question

			qa_create_event_for_tags($question['tags'], $questionid, null, $question['userid'], $question['created']);
			qa_create_event_for_category($question['categoryid'], $questionid, null, $question['userid'], $question['created']);

			// Collect comment threads

			$parentidcomments = array();

			foreach ($posts as $postid => $post) {
				if ($post['basetype'] == 'C') {
					$parentidcomments[$post['parentid']][$postid] = $post;
				}
			}

			// For each comment thread, notify all previous comment authors of each comment in the thread (could get slow)

			foreach ($parentidcomments as $parentid => $comments) {
				$keyuserids = array();

				qa_sort_by($comments, 'created');

				foreach ($comments as $comment) {
					foreach ($keyuserids as $keyuserid => $dummy) {
						if ($keyuserid != $comment['userid'] && $keyuserid != @$posts[$parentid]['userid']) {
							qa_db_event_create_not_entity($keyuserid, $questionid, $comment['postid'], QA_UPDATE_FOLLOWS, $comment['userid'], $comment['created']);
						}
					}

					if (isset($comment['userid'])) {
						$keyuserids[$comment['userid']] = true;
					}
				}
			}
		}

		$this->next = 1 + $lastquestionid;
		$this->done += count($questionids);
		return true;
	}

	private function DoRecalcCategories()
	{
		$this->state->transition('dorecalccategories_postcount');
		return false;
	}

	private function DoRecalcCategories_PostCount()
	{
		qa_db_acount_update();
		qa_db_ccount_update();

		$this->state->transition('dorecalccategories_postupdate');
		return false;
	}

	private function DoRecalcCategories_PostUpdate()
	{
		$postids = qa_db_posts_get_for_recategorizing($this->next, 100);

		if (!count($postids)) {
			$this->state->transition('dorecalccategories_recount');
			return false;
		}

		$lastpostid = max($postids);

		qa_db_posts_recalc_categoryid($this->next, $lastpostid);
		qa_db_posts_calc_category_path($this->next, $lastpostid);

		$this->next = 1 + $lastpostid;
		$this->done += count($postids);
		return true;
	}

	private function DoRecalcCategories_Recount()
	{
		$categoryids = qa_db_categories_get_for_recalcs($this->next, 10);

		if (!count($categoryids)) {
			$this->state->transition('dorecalccategories_backpaths');
			return false;
		}
		$lastcategoryid = max($categoryids);

		foreach ($categoryids as $categoryid) {
			qa_db_ifcategory_qcount_update($categoryid);
		}

		$this->next = 1 + $lastcategoryid;
		$this->done += count($categoryids);
		return true;
	}

	private function DoRecalcCategories_BackPaths()
	{
		$categoryids = qa_db_categories_get_for_recalcs($this->next, 10);

		if (!count($categoryids)) {
			$this->state->transition('dorecalccategories_complete');
			return false;
		}
		$lastcategoryid = max($categoryids);

		qa_db_categories_recalc_backpaths($this->next, $lastcategoryid);

		$this->next = 1 + $lastcategoryid;
		$this->done += count($categoryids);
		return true;
	}

	private function DoDeleteHidden()
	{
		$this->state->transition('dodeletehidden_comments');
		return false;
	}

	private function DoDeleteHidden_Comments()
	{
		$posts = qa_db_posts_get_for_deleting('C', $this->next, 1);

		if (!count($posts)) {
			$this->state->transition('dodeletehidden_answers');
			return false;
		}

		require_once QA_INCLUDE_DIR . 'app/posts.php';

		$postid = $posts[0];
		qa_post_delete($postid);

		$this->next = 1 + $postid;
		$this->done++;
		return true;
	}

	private function DoDeleteHidden_Answers()
	{
		$posts = qa_db_posts_get_for_deleting('A', $this->next, 1);

		if (!count($posts)) {
			$this->state->transition('dodeletehidden_questions');
			return false;
		}
			
		require_once QA_INCLUDE_DIR . 'app/posts.php';

		$postid = $posts[0];
		qa_post_delete($postid);

		$this->next = 1 + $postid;
		$this->done++;
		return true;
	}

	private function Dodeletehidden_questions()
	{
		$posts = qa_db_posts_get_for_deleting('Q', $this->next, 1);

		if (!count($posts)) {
			$this->state->transition('dodeletehidden_complete');
			return false;
		}

		require_once QA_INCLUDE_DIR . 'app/posts.php';

		$postid = $posts[0];
		qa_post_delete($postid);

		$this->next = 1 + $postid;
		$this->done++;
		return true;
	}

	private function DoBlobsToDisk()
	{
		$this->state->transition('doblobstodisk_move');
		return false;
	}

	private function DoBlobsToDisk_Move()
	{
		$blob = qa_db_get_next_blob_in_db($this->next);

		if (!isset($blob)) {
			$this->state->transition('doblobstodisk_complete');
			return false;
		}

		require_once QA_INCLUDE_DIR . 'app/blobs.php';
		require_once QA_INCLUDE_DIR . 'db/blobs.php';

		if (qa_write_blob_file($blob['blobid'], $blob['content'], $blob['format'])) {
			qa_db_blob_set_content($blob['blobid'], null);
		}

		$this->next = 1 + $blob['blobid'];
		$this->done++;
		return true;
	}

	private function DoBlobsToDB()
	{
		$this->state->transition('doblobstodb_move');
		return false;
	}

	private function DoBlobsToDB_Move()
	{
		$blob = qa_db_get_next_blob_on_disk($this->next);

		if (!isset($blob)) {
			$this->state->transition('doblobstodb_complete');
			return false;
		}
		require_once QA_INCLUDE_DIR . 'app/blobs.php';
		require_once QA_INCLUDE_DIR . 'db/blobs.php';

		$content = qa_read_blob_file($blob['blobid'], $blob['format']);
		qa_db_blob_set_content($blob['blobid'], $content);
		qa_delete_blob_file($blob['blobid'], $blob['format']);

		$this->next = 1 + $blob['blobid'];
		$this->done++;
		return true;
	}

	private function DoCacheTrim()
	{
		$this->state->transition('docachetrim_process');
		return false;
	}

	private function DoCacheClear()
	{
		$this->state->transition('docacheclear_process');
		return false;
	}

	private function DoCacheTrim_Process()
	{
		return $this->Docacheclear_process();
	}

	private function DoCacheClear_Process()
	{
		$cacheDriver = Q2A_Storage_CacheFactory::getCacheDriver();
		$cacheStats = $cacheDriver->getStats();
		$limit = min($cacheStats['files'], 20);

		if (!($cacheStats['files'] > 0 && $this->next <= $this->length)) {
			$this->state->transition('docacheclear_complete');
			return false;
		}
			
		$deleted = $cacheDriver->clear($limit, $this->next, ($this->state->operation === 'docachetrim_process'));
		$this->done += $deleted;
		$this->next += $limit - $deleted; // skip files that weren't deleted on next iteration
		return true;
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

		$this->done = (int) $this->done;
		$this->length = (int) $this->length;

		switch ($this->state->operation) {
			case 'doreindexcontent_postcount':
			case 'dorecountposts_postcount':
			case 'dorecalccategories_postcount':
			case 'dorefillevents_qcount':
				$message = qa_lang('admin/recalc_posts_count');
				break;

			case 'doreindexcontent_pagereindex':
				$message = $this->progressLang('admin/reindex_pages_reindexed', $this->done, $this->length);
				break;

			case 'doreindexcontent_postreindex':
				$message = $this->progressLang('admin/reindex_posts_reindexed', $this->done, $this->length);
				break;

			case 'doreindexposts_complete':
				$message = qa_lang('admin/reindex_posts_complete');
				break;

			case 'doreindexposts_wordcount':
				$message = $this->progressLang('admin/reindex_posts_wordcounted', $this->done, $this->length);
				break;

			case 'dorecountposts_votecount':
				$message = $this->progressLang('admin/recount_posts_votes_recounted', $this->done, $this->length);
				break;

			case 'dorecountposts_acount':
				$message = $this->progressLang('admin/recount_posts_as_recounted', $this->done, $this->length);
				break;

			case 'dorecountposts_complete':
				$message = qa_lang('admin/recount_posts_complete');
				break;

			case 'dorecalcpoints_usercount':
				$message = qa_lang('admin/recalc_points_usercount');
				break;

			case 'dorecalcpoints_recalc':
				$message = $this->progressLang('admin/recalc_points_recalced', $this->done, $this->length);
				break;

			case 'dorecalcpoints_complete':
				$message = qa_lang('admin/recalc_points_complete');
				break;

			case 'dorefillevents_refill':
				$message = $this->progressLang('admin/refill_events_refilled', $this->done, $this->length);
				break;

			case 'dorefillevents_complete':
				$message = qa_lang('admin/refill_events_complete');
				break;

			case 'dorecalccategories_postupdate':
				$message = $this->progressLang('admin/recalc_categories_updated', $this->done, $this->length);
				break;

			case 'dorecalccategories_recount':
				$message = $this->progressLang('admin/recalc_categories_recounting', $this->done, $this->length);
				break;

			case 'dorecalccategories_backpaths':
				$message = $this->progressLang('admin/recalc_categories_backpaths', $this->done, $this->length);
				break;

			case 'dorecalccategories_complete':
				$message = qa_lang('admin/recalc_categories_complete');
				break;

			case 'dodeletehidden_comments':
				$message = $this->progressLang('admin/hidden_comments_deleted', $this->done, $this->length);
				break;

			case 'dodeletehidden_answers':
				$message = $this->progressLang('admin/hidden_answers_deleted', $this->done, $this->length);
				break;

			case 'dodeletehidden_questions':
				$message = $this->progressLang('admin/hidden_questions_deleted', $this->done, $this->length);
				break;

			case 'dodeletehidden_complete':
				$message = qa_lang('admin/delete_hidden_complete');
				break;

			case 'doblobstodisk_move':
			case 'doblobstodb_move':
				$message = $this->progressLang('admin/blobs_move_moved', $this->done, $this->length);
				break;

			case 'doblobstodisk_complete':
			case 'doblobstodb_complete':
				$message = qa_lang('admin/blobs_move_complete');
				break;

			case 'docachetrim_process':
			case 'docacheclear_process':
				$message = $this->progressLang('admin/caching_delete_progress', $this->done, $this->length);
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
