<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Managing database recalculations (clean-up operations) and status messages


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

if (defined('QA_DEBUG_PERFORMANCE') && QA_DEBUG_PERFORMANCE) {
	trigger_error('Included file ' . basename(__FILE__) . ' is deprecated');
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


/**
 * Advance the recalculation operation represented by $state by a single step.
 * $state can also be the name of a recalculation operation on its own.
 * @param string $state
 * @return bool
 */
function qa_recalc_perform_step(&$state)
{
	$continue = false;

	@list($operation, $length, $next, $done) = explode("\t", $state);

	switch ($operation) {
		case 'doreindexcontent':
			qa_recalc_transition($state, 'doreindexcontent_pagereindex');
			break;

		case 'doreindexcontent_pagereindex':
			$pages = qa_db_pages_get_for_reindexing($next, 10);

			if (count($pages)) {
				require_once QA_INCLUDE_DIR . 'app/format.php';

				$lastpageid = max(array_keys($pages));

				foreach ($pages as $pageid => $page) {
					if (!($page['flags'] & QA_PAGE_FLAGS_EXTERNAL)) {
						$searchmodules = qa_load_modules_with('search', 'unindex_page');
						foreach ($searchmodules as $searchmodule) {
							$searchmodule->unindex_page($pageid);
						}

						$searchmodules = qa_load_modules_with('search', 'index_page');
						if (count($searchmodules)) {
							$indextext = qa_viewer_text($page['content'], 'html');

							foreach ($searchmodules as $searchmodule)
								$searchmodule->index_page($pageid, $page['tags'], $page['heading'], $page['content'], 'html', $indextext);
						}
					}
				}

				$next = 1 + $lastpageid;
				$done += count($pages);
				$continue = true;

			} else {
				qa_recalc_transition($state, 'doreindexcontent_postcount');
			}
			break;

		case 'doreindexcontent_postcount':
			qa_db_qcount_update();
			qa_db_acount_update();
			qa_db_ccount_update();

			qa_recalc_transition($state, 'doreindexcontent_postreindex');
			break;

		case 'doreindexcontent_postreindex':
			$posts = qa_db_posts_get_for_reindexing($next, 10);

			if (count($posts)) {
				require_once QA_INCLUDE_DIR . 'app/format.php';

				$lastpostid = max(array_keys($posts));

				qa_db_prepare_for_reindexing($next, $lastpostid);
				qa_suspend_update_counts();

				foreach ($posts as $postid => $post) {
					qa_post_unindex($postid);
					qa_post_index($postid, $post['type'], $post['questionid'], $post['parentid'], $post['title'], $post['content'],
						$post['format'], qa_viewer_text($post['content'], $post['format']), $post['tags'], $post['categoryid']);
				}

				$next = 1 + $lastpostid;
				$done += count($posts);
				$continue = true;

			} else {
				qa_db_truncate_indexes($next);
				qa_recalc_transition($state, 'doreindexposts_wordcount');
			}
			break;

		case 'doreindexposts_wordcount':
			$wordids = qa_db_words_prepare_for_recounting($next, 1000);

			if (count($wordids)) {
				$lastwordid = max($wordids);

				qa_db_words_recount($next, $lastwordid);

				$next = 1 + $lastwordid;
				$done += count($wordids);
				$continue = true;

			} else {
				qa_db_tagcount_update(); // this is quick so just do it here
				qa_recalc_transition($state, 'doreindexposts_complete');
			}
			break;

		case 'dorecountposts':
			qa_recalc_transition($state, 'dorecountposts_postcount');
			break;

		case 'dorecountposts_postcount':
			qa_db_qcount_update();
			qa_db_acount_update();
			qa_db_ccount_update();
			qa_db_unaqcount_update();
			qa_db_unselqcount_update();

			qa_recalc_transition($state, 'dorecountposts_votecount');
			break;

		case 'dorecountposts_votecount':
			$postids = qa_db_posts_get_for_recounting($next, 1000);

			if (count($postids)) {
				$lastpostid = max($postids);

				qa_db_posts_votes_recount($next, $lastpostid);

				$next = 1 + $lastpostid;
				$done += count($postids);
				$continue = true;

			} else {
				qa_recalc_transition($state, 'dorecountposts_acount');
			}
			break;

		case 'dorecountposts_acount':
			$postids = qa_db_posts_get_for_recounting($next, 1000);

			if (count($postids)) {
				$lastpostid = max($postids);

				qa_db_posts_answers_recount($next, $lastpostid);

				$next = 1 + $lastpostid;
				$done += count($postids);
				$continue = true;

			} else {
				qa_db_unupaqcount_update();
				qa_recalc_transition($state, 'dorecountposts_complete');
			}
			break;

		case 'dorecalcpoints':
			qa_recalc_transition($state, 'dorecalcpoints_usercount');
			break;

		case 'dorecalcpoints_usercount':
			qa_db_userpointscount_update(); // for progress update - not necessarily accurate
			qa_db_uapprovecount_update(); // needs to be somewhere and this is the most appropriate place
			qa_recalc_transition($state, 'dorecalcpoints_recalc');
			break;

		case 'dorecalcpoints_recalc':
			$recalccount = 10;
			$userids = qa_db_users_get_for_recalc_points($next, $recalccount + 1); // get one extra so we know where to start from next
			$gotcount = count($userids);
			$recalccount = min($recalccount, $gotcount); // can't recalc more than we got

			if ($recalccount > 0) {
				$lastuserid = $userids[$recalccount - 1];
				qa_db_users_recalc_points($next, $lastuserid);
				$done += $recalccount;

			} else {
				$lastuserid = $next; // for truncation
			}

			if ($gotcount > $recalccount) { // more left to do
				$next = $userids[$recalccount]; // start next round at first one not recalculated
				$continue = true;
			} else {
				qa_db_truncate_userpoints($lastuserid);
				qa_db_userpointscount_update(); // quick so just do it here
				qa_recalc_transition($state, 'dorecalcpoints_complete');
			}
			break;

		case 'dorefillevents':
			qa_recalc_transition($state, 'dorefillevents_qcount');
			break;

		case 'dorefillevents_qcount':
			qa_db_qcount_update();
			qa_recalc_transition($state, 'dorefillevents_refill');
			break;

		case 'dorefillevents_refill':
			$questionids = qa_db_qs_get_for_event_refilling($next, 1);

			if (count($questionids)) {
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

				$next = 1 + $lastquestionid;
				$done += count($questionids);
				$continue = true;

			} else {
				qa_recalc_transition($state, 'dorefillevents_complete');
			}
			break;

		case 'dorecalccategories':
			qa_recalc_transition($state, 'dorecalccategories_postcount');
			break;

		case 'dorecalccategories_postcount':
			qa_db_acount_update();
			qa_db_ccount_update();

			qa_recalc_transition($state, 'dorecalccategories_postupdate');
			break;

		case 'dorecalccategories_postupdate':
			$postids = qa_db_posts_get_for_recategorizing($next, 100);

			if (count($postids)) {
				$lastpostid = max($postids);

				qa_db_posts_recalc_categoryid($next, $lastpostid);
				qa_db_posts_calc_category_path($next, $lastpostid);

				$next = 1 + $lastpostid;
				$done += count($postids);
				$continue = true;
			} else {
				qa_recalc_transition($state, 'dorecalccategories_recount');
			}
			break;

		case 'dorecalccategories_recount':
			$categoryids = qa_db_categories_get_for_recalcs($next, 10);

			if (count($categoryids)) {
				$lastcategoryid = max($categoryids);

				foreach ($categoryids as $categoryid) {
					qa_db_ifcategory_qcount_update($categoryid);
				}

				$next = 1 + $lastcategoryid;
				$done += count($categoryids);
				$continue = true;
			} else {
				qa_recalc_transition($state, 'dorecalccategories_backpaths');
			}
			break;

		case 'dorecalccategories_backpaths':
			$categoryids = qa_db_categories_get_for_recalcs($next, 10);

			if (count($categoryids)) {
				$lastcategoryid = max($categoryids);

				qa_db_categories_recalc_backpaths($next, $lastcategoryid);

				$next = 1 + $lastcategoryid;
				$done += count($categoryids);
				$continue = true;

			} else {
				qa_recalc_transition($state, 'dorecalccategories_complete');
			}
			break;

		case 'dodeletehidden':
			qa_recalc_transition($state, 'dodeletehidden_comments');
			break;

		case 'dodeletehidden_comments':
			$posts = qa_db_posts_get_for_deleting('C', $next, 1);

			if (count($posts)) {
				require_once QA_INCLUDE_DIR . 'app/posts.php';

				$postid = $posts[0];
				qa_post_delete($postid);

				$next = 1 + $postid;
				$done++;
				$continue = true;
			} else {
				qa_recalc_transition($state, 'dodeletehidden_answers');
			}
			break;

		case 'dodeletehidden_answers':
			$posts = qa_db_posts_get_for_deleting('A', $next, 1);

			if (count($posts)) {
				require_once QA_INCLUDE_DIR . 'app/posts.php';

				$postid = $posts[0];
				qa_post_delete($postid);

				$next = 1 + $postid;
				$done++;
				$continue = true;

			} else {
				qa_recalc_transition($state, 'dodeletehidden_questions');
			}
			break;

		case 'dodeletehidden_questions':
			$posts = qa_db_posts_get_for_deleting('Q', $next, 1);

			if (count($posts)) {
				require_once QA_INCLUDE_DIR . 'app/posts.php';

				$postid = $posts[0];
				qa_post_delete($postid);

				$next = 1 + $postid;
				$done++;
				$continue = true;

			} else {
				qa_recalc_transition($state, 'dodeletehidden_complete');
			}
			break;

		case 'doblobstodisk':
			qa_recalc_transition($state, 'doblobstodisk_move');
			break;

		case 'doblobstodisk_move':
			$blob = qa_db_get_next_blob_in_db($next);

			if (isset($blob)) {
				require_once QA_INCLUDE_DIR . 'app/blobs.php';
				require_once QA_INCLUDE_DIR . 'db/blobs.php';

				if (qa_write_blob_file($blob['blobid'], $blob['content'], $blob['format'])) {
					qa_db_blob_set_content($blob['blobid'], null);
				}

				$next = 1 + $blob['blobid'];
				$done++;
				$continue = true;
			} else {
				qa_recalc_transition($state, 'doblobstodisk_complete');
			}
			break;

		case 'doblobstodb':
			qa_recalc_transition($state, 'doblobstodb_move');
			break;

		case 'doblobstodb_move':
			$blob = qa_db_get_next_blob_on_disk($next);

			if (isset($blob)) {
				require_once QA_INCLUDE_DIR . 'app/blobs.php';
				require_once QA_INCLUDE_DIR . 'db/blobs.php';

				$content = qa_read_blob_file($blob['blobid'], $blob['format']);
				qa_db_blob_set_content($blob['blobid'], $content);
				qa_delete_blob_file($blob['blobid'], $blob['format']);

				$next = 1 + $blob['blobid'];
				$done++;
				$continue = true;
			} else {
				qa_recalc_transition($state, 'doblobstodb_complete');
			}
			break;

		case 'docachetrim':
			qa_recalc_transition($state, 'docachetrim_process');
			break;
		case 'docacheclear':
			qa_recalc_transition($state, 'docacheclear_process');
			break;

		case 'docachetrim_process':
		case 'docacheclear_process':
			$cacheDriver = \Q2A\Storage\CacheFactory::getCacheDriver();
			$cacheStats = $cacheDriver->getStats();
			$limit = min($cacheStats['files'], 500);

			if ($cacheStats['files'] > 0 && $next <= $length) {
				$deleted = $cacheDriver->clear($limit, $next, ($operation === 'docachetrim_process'));
				$done += $deleted;
				$next += $limit - $deleted; // skip files that weren't deleted on next iteration
				$continue = true;
			} else {
				qa_recalc_transition($state, 'docacheclear_complete');
			}
			break;

		default:
			$state = '';
			break;
	}

	if ($continue) {
		$state = $operation . "\t" . $length . "\t" . $next . "\t" . $done;
	}

	return $continue && $done < $length;
}


/**
 * Change the $state to represent the beginning of a new $operation
 * @param string $state
 * @param string $operation
 */
function qa_recalc_transition(&$state, $operation)
{
	$length = qa_recalc_stage_length($operation);
	$next = (QA_FINAL_EXTERNAL_USERS && ($operation == 'dorecalcpoints_recalc')) ? '' : 0;
	$done = 0;

	$state = $operation . "\t" . $length . "\t" . $next . "\t" . $done;
}


/**
 * Return how many steps there will be in recalculation $operation
 * @param string $operation
 * @return int
 */
function qa_recalc_stage_length($operation)
{
	switch ($operation) {
		case 'doreindexcontent_pagereindex':
			$length = qa_db_count_pages();
			break;

		case 'doreindexcontent_postreindex':
			$length = qa_opt('cache_qcount') + qa_opt('cache_acount') + qa_opt('cache_ccount');
			break;

		case 'doreindexposts_wordcount':
			$length = qa_db_count_words();
			break;

		case 'dorecalcpoints_recalc':
			$length = qa_opt('cache_userpointscount');
			break;

		case 'dorecountposts_votecount':
		case 'dorecountposts_acount':
		case 'dorecalccategories_postupdate':
			$length = qa_db_count_posts();
			break;

		case 'dorefillevents_refill':
			$length = qa_opt('cache_qcount') + qa_db_count_posts('Q_HIDDEN');
			break;

		case 'dorecalccategories_recount':
		case 'dorecalccategories_backpaths':
			$length = qa_db_count_categories();
			break;

		case 'dodeletehidden_comments':
			$length = count(qa_db_posts_get_for_deleting('C'));
			break;

		case 'dodeletehidden_answers':
			$length = count(qa_db_posts_get_for_deleting('A'));
			break;

		case 'dodeletehidden_questions':
			$length = count(qa_db_posts_get_for_deleting('Q'));
			break;

		case 'doblobstodisk_move':
			$length = qa_db_count_blobs_in_db();
			break;

		case 'doblobstodb_move':
			$length = qa_db_count_blobs_on_disk();
			break;

		case 'docachetrim_process':
		case 'docacheclear_process':
			$cacheDriver = \Q2A\Storage\CacheFactory::getCacheDriver();
			$cacheStats = $cacheDriver->getStats();
			$length = $cacheStats['files'];
			break;

		default:
			$length = 0;
			break;
	}

	return $length;
}


/**
 * Return the translated language ID string replacing the progress and total in it.
 * @param string $langId Language string ID that contains 2 placeholders (^1 and ^2)
 * @param int $progress Amount of processed elements
 * @param int $total Total amount of elements
 * @return string Returns the language string ID with their placeholders replaced with
 * the formatted progress and total numbers
 */
function qa_recalc_progress_lang($langId, $progress, $total)
{
	return strtr(qa_lang($langId), array(
		'^1' => qa_format_number($progress),
		'^2' => qa_format_number($total),
	));
}


/**
 * Return a string which gives a user-viewable version of $state
 * @param string $state
 * @return string
 */
function qa_recalc_get_message($state)
{
	require_once QA_INCLUDE_DIR . 'app/format.php';

	@list($operation, $length, $next, $done) = explode("\t", $state);

	$done = (int) $done;
	$length = (int) $length;

	switch ($operation) {
		case 'doreindexcontent_postcount':
		case 'dorecountposts_postcount':
		case 'dorecalccategories_postcount':
		case 'dorefillevents_qcount':
			$message = qa_lang('admin/recalc_posts_count');
			break;

		case 'doreindexcontent_pagereindex':
			$message = qa_recalc_progress_lang('admin/reindex_pages_reindexed', $done, $length);
			break;

		case 'doreindexcontent_postreindex':
			$message = qa_recalc_progress_lang('admin/reindex_posts_reindexed', $done, $length);
			break;

		case 'doreindexposts_complete':
			$message = qa_lang('admin/reindex_posts_complete');
			break;

		case 'doreindexposts_wordcount':
			$message = qa_recalc_progress_lang('admin/reindex_posts_wordcounted', $done, $length);
			break;

		case 'dorecountposts_votecount':
			$message = qa_recalc_progress_lang('admin/recount_posts_votes_recounted', $done, $length);
			break;

		case 'dorecountposts_acount':
			$message = qa_recalc_progress_lang('admin/recount_posts_as_recounted', $done, $length);
			break;

		case 'dorecountposts_complete':
			$message = qa_lang('admin/recount_posts_complete');
			break;

		case 'dorecalcpoints_usercount':
			$message = qa_lang('admin/recalc_points_usercount');
			break;

		case 'dorecalcpoints_recalc':
			$message = qa_recalc_progress_lang('admin/recalc_points_recalced', $done, $length);
			break;

		case 'dorecalcpoints_complete':
			$message = qa_lang('admin/recalc_points_complete');
			break;

		case 'dorefillevents_refill':
			$message = qa_recalc_progress_lang('admin/refill_events_refilled', $done, $length);
			break;

		case 'dorefillevents_complete':
			$message = qa_lang('admin/refill_events_complete');
			break;

		case 'dorecalccategories_postupdate':
			$message = qa_recalc_progress_lang('admin/recalc_categories_updated', $done, $length);
			break;

		case 'dorecalccategories_recount':
			$message = qa_recalc_progress_lang('admin/recalc_categories_recounting', $done, $length);
			break;

		case 'dorecalccategories_backpaths':
			$message = qa_recalc_progress_lang('admin/recalc_categories_backpaths', $done, $length);
			break;

		case 'dorecalccategories_complete':
			$message = qa_lang('admin/recalc_categories_complete');
			break;

		case 'dodeletehidden_comments':
			$message = qa_recalc_progress_lang('admin/hidden_comments_deleted', $done, $length);
			break;

		case 'dodeletehidden_answers':
			$message = qa_recalc_progress_lang('admin/hidden_answers_deleted', $done, $length);
			break;

		case 'dodeletehidden_questions':
			$message = qa_recalc_progress_lang('admin/hidden_questions_deleted', $done, $length);
			break;

		case 'dodeletehidden_complete':
			$message = qa_lang('admin/delete_hidden_complete');
			break;

		case 'doblobstodisk_move':
		case 'doblobstodb_move':
			$message = qa_recalc_progress_lang('admin/blobs_move_moved', $done, $length);
			break;

		case 'doblobstodisk_complete':
		case 'doblobstodb_complete':
			$message = qa_lang('admin/blobs_move_complete');
			break;

		case 'docachetrim_process':
		case 'docacheclear_process':
			$message = qa_recalc_progress_lang('admin/caching_delete_progress', $done, $length);
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
