<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-recalc.php
	Version: See define()s at top of qa-include/qa-base.php
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
	^sharedevents (all): per-entity event streams (see big comment in qa-db-favorites.php)
	^userevents (all): per-subscriber event streams
	
	[but these are not entirely redundant since they can contain historical information no longer in ^posts]
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'qa-db-recalc.php';
	require_once QA_INCLUDE_DIR.'qa-db-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-db-points.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-db-admin.php';
	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-create.php';
	require_once QA_INCLUDE_DIR.'qa-app-post-update.php';


	function qa_recalc_perform_step(&$state)
/*
	Advance the recalculation operation represented by $state by a single step.
	$state can also be the name of a recalculation operation on its own.
*/
	{
		$continue=false;
		
		@list($operation, $length, $next, $done)=explode("\t", $state);
		
		switch ($operation) {
			case 'doreindexcontent':
				qa_recalc_transition($state, 'doreindexcontent_pagereindex');
				break;
				
			case 'doreindexcontent_pagereindex':
				$pages=qa_db_pages_get_for_reindexing($next, 10);
				
				if (count($pages)) {
					require_once QA_INCLUDE_DIR.'qa-app-format.php';
					
					$lastpageid=max(array_keys($pages));
					
					foreach ($pages as $pageid => $page)
						if (!($page['flags'] & QA_PAGE_FLAGS_EXTERNAL)) {
							$searchmodules=qa_load_modules_with('search', 'unindex_page');
							foreach ($searchmodules as $searchmodule)
								$searchmodule->unindex_page($pageid);
								
							$searchmodules=qa_load_modules_with('search', 'index_page');
							if (count($searchmodules)) {
								$indextext=qa_viewer_text($page['content'], 'html');

								foreach ($searchmodules as $searchmodule)
									$searchmodule->index_page($pageid, $page['tags'], $page['heading'], $page['content'], 'html', $indextext);
							}
						}
						
					$next=1+$lastpageid;
					$done+=count($pages);
					$continue=true;
				
				} else
					qa_recalc_transition($state, 'doreindexcontent_postcount');
				break;
			
			case 'doreindexcontent_postcount':
				qa_db_qcount_update();
				qa_db_acount_update();
				qa_db_ccount_update();

				qa_recalc_transition($state, 'doreindexcontent_postreindex');
				break;
				
			case 'doreindexcontent_postreindex':
				$posts=qa_db_posts_get_for_reindexing($next, 10);
				
				if (count($posts)) {
					require_once QA_INCLUDE_DIR.'qa-app-format.php';

					$lastpostid=max(array_keys($posts));
					
					qa_db_prepare_for_reindexing($next, $lastpostid);
					qa_suspend_update_counts();
		
					foreach ($posts as $postid => $post) {
						qa_post_unindex($postid);
						qa_post_index($postid, $post['type'], $post['questionid'], $post['parentid'], $post['title'], $post['content'],
							$post['format'], qa_viewer_text($post['content'], $post['format']), $post['tags'], $post['categoryid']);
					}
					
					$next=1+$lastpostid;
					$done+=count($posts);
					$continue=true;

				} else {
					qa_db_truncate_indexes($next);
					qa_recalc_transition($state, 'doreindexposts_wordcount');
				}
				break;
				
			case 'doreindexposts_wordcount':
				$wordids=qa_db_words_prepare_for_recounting($next, 1000);
				
				if (count($wordids)) {
					$lastwordid=max($wordids);
					
					qa_db_words_recount($next, $lastwordid);
					
					$next=1+$lastwordid;
					$done+=count($wordids);
					$continue=true;
			
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
				$postids=qa_db_posts_get_for_recounting($next, 1000);
				
				if (count($postids)) {
					$lastpostid=max($postids);
					
					qa_db_posts_votes_recount($next, $lastpostid);
					
					$next=1+$lastpostid;
					$done+=count($postids);
					$continue=true;

				} else
					qa_recalc_transition($state, 'dorecountposts_acount');
				break;
				
			case 'dorecountposts_acount':
				$postids=qa_db_posts_get_for_recounting($next, 1000);
				
				if (count($postids)) {
					$lastpostid=max($postids);
					
					qa_db_posts_answers_recount($next, $lastpostid);
					
					$next=1+$lastpostid;
					$done+=count($postids);
					$continue=true;

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
				$recalccount=10;
				$userids=qa_db_users_get_for_recalc_points($next, $recalccount+1); // get one extra so we know where to start from next
				$gotcount=count($userids);
				$recalccount=min($recalccount, $gotcount); // can't recalc more than we got
				
				if ($recalccount>0) {
					$lastuserid=$userids[$recalccount-1];
					qa_db_users_recalc_points($next, $lastuserid);					
					$done+=$recalccount;
					
				} else
					$lastuserid=$next; // for truncation

				if ($gotcount>$recalccount) { // more left to do
					$next=$userids[$recalccount]; // start next round at first one not recalculated
					$continue=true;

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
				$questionids=qa_db_qs_get_for_event_refilling($next, 1);
				
				if (count($questionids)) {
					require_once QA_INCLUDE_DIR.'qa-app-events.php';
					require_once QA_INCLUDE_DIR.'qa-app-updates.php';
					require_once QA_INCLUDE_DIR.'qa-util-sort.php';
					
					$lastquestionid=max($questionids);
					
					foreach ($questionids as $questionid) {

					//	Retrieve all posts relating to this question

						list($question, $childposts, $achildposts)=qa_db_select_with_pending(
							qa_db_full_post_selectspec(null, $questionid),
							qa_db_full_child_posts_selectspec(null, $questionid),
							qa_db_full_a_child_posts_selectspec(null, $questionid)
						);
						
					//	Merge all posts while preserving keys as postids
						
						$posts=array($questionid => $question);

						foreach ($childposts as $postid => $post)
							$posts[$postid]=$post;

						foreach ($achildposts as $postid => $post)
							$posts[$postid]=$post;
							
					//	Creation and editing of each post
							
						foreach ($posts as $postid => $post) {
							$followonq=($post['basetype']=='Q') && ($postid!=$questionid);
							
							if ($followonq)
								$updatetype=QA_UPDATE_FOLLOWS;
							elseif ( ($post['basetype']=='C') && (@$posts[$post['parentid']]['basetype']=='Q') )
								$updatetype=QA_UPDATE_C_FOR_Q;
							elseif ( ($post['basetype']=='C') && (@$posts[$post['parentid']]['basetype']=='A') )
								$updatetype=QA_UPDATE_C_FOR_A;
							else
								$updatetype=null;
							
							qa_create_event_for_q_user($questionid, $postid, $updatetype, $post['userid'], @$posts[$post['parentid']]['userid'], $post['created']);
							
							if (isset($post['updated']) && !$followonq)
								qa_create_event_for_q_user($questionid, $postid, $post['updatetype'], $post['lastuserid'], $post['userid'], $post['updated']);
						}
						
					//	Tags and categories of question

						qa_create_event_for_tags($question['tags'], $questionid, null, $question['userid'], $question['created']);
						qa_create_event_for_category($question['categoryid'], $questionid, null, $question['userid'], $question['created']);
						
					//	Collect comment threads
						
						$parentidcomments=array();
						
						foreach ($posts as $postid => $post)
							if ($post['basetype']=='C')
								$parentidcomments[$post['parentid']][$postid]=$post;
					
					//	For each comment thread, notify all previous comment authors of each comment in the thread (could get slow)

						foreach ($parentidcomments as $parentid => $comments) {
							$keyuserids=array();
							
							qa_sort_by($comments, 'created');
							
							foreach ($comments as $comment) {
								foreach ($keyuserids as $keyuserid => $dummy)
									if ( ($keyuserid != $comment['userid']) && ($keyuserid != @$posts[$parentid]['userid']) )
										qa_db_event_create_not_entity($keyuserid, $questionid, $comment['postid'], QA_UPDATE_FOLLOWS, $comment['userid'], $comment['created']);

								if (isset($comment['userid']))
									$keyuserids[$comment['userid']]=true;
							}
						}
					}
					
					$next=1+$lastquestionid;
					$done+=count($questionids);
					$continue=true;

				} else
					qa_recalc_transition($state, 'dorefillevents_complete');
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
				$postids=qa_db_posts_get_for_recategorizing($next, 100);
				
				if (count($postids)) {
					$lastpostid=max($postids);
					
					qa_db_posts_recalc_categoryid($next, $lastpostid);
					qa_db_posts_calc_category_path($next, $lastpostid);
					
					$next=1+$lastpostid;
					$done+=count($postids);
					$continue=true;
				
				} else {
					qa_recalc_transition($state, 'dorecalccategories_recount');
				}
				break;
			
			case 'dorecalccategories_recount':
				$categoryids=qa_db_categories_get_for_recalcs($next, 10);
				
				if (count($categoryids)) {
					$lastcategoryid=max($categoryids);
					
					foreach ($categoryids as $categoryid)
						qa_db_ifcategory_qcount_update($categoryid);
					
					$next=1+$lastcategoryid;
					$done+=count($categoryids);
					$continue=true;
				
				} else {
					qa_recalc_transition($state, 'dorecalccategories_backpaths');
				}
				break;
				
			case 'dorecalccategories_backpaths':
				$categoryids=qa_db_categories_get_for_recalcs($next, 10);

				if (count($categoryids)) {
					$lastcategoryid=max($categoryids);
					
					qa_db_categories_recalc_backpaths($next, $lastcategoryid);
					
					$next=1+$lastcategoryid;
					$done+=count($categoryids);
					$continue=true;
				
				} else {
					qa_recalc_transition($state, 'dorecalccategories_complete');
				}
				break;
				
			case 'dodeletehidden':
				qa_recalc_transition($state, 'dodeletehidden_comments');
				break;
				
			case 'dodeletehidden_comments':
				$posts=qa_db_posts_get_for_deleting('C', $next, 1);
				
				if (count($posts)) {
					require_once QA_INCLUDE_DIR.'qa-app-posts.php';
					
					$postid=$posts[0];

					qa_post_delete($postid);
					
					$next=1+$postid;
					$done++;
					$continue=true;
				
				} else
					qa_recalc_transition($state, 'dodeletehidden_answers');
				break;
			
			case 'dodeletehidden_answers':
				$posts=qa_db_posts_get_for_deleting('A', $next, 1);
				
				if (count($posts)) {
					require_once QA_INCLUDE_DIR.'qa-app-posts.php';
					
					$postid=$posts[0];
					
					qa_post_delete($postid);
					
					$next=1+$postid;
					$done++;
					$continue=true;
				
				} else
					qa_recalc_transition($state, 'dodeletehidden_questions');
				break;

			case 'dodeletehidden_questions':
				$posts=qa_db_posts_get_for_deleting('Q', $next, 1);
				
				if (count($posts)) {
					require_once QA_INCLUDE_DIR.'qa-app-posts.php';
					
					$postid=$posts[0];
					
					qa_post_delete($postid);
					
					$next=1+$postid;
					$done++;
					$continue=true;
				
				} else
					qa_recalc_transition($state, 'dodeletehidden_complete');
				break;
				
			case 'doblobstodisk':
				qa_recalc_transition($state, 'doblobstodisk_move');
				break;
				
			case 'doblobstodisk_move':
				$blob=qa_db_get_next_blob_in_db($next);
				
				if (isset($blob)) {
					require_once QA_INCLUDE_DIR.'qa-app-blobs.php';
					require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
					
					if (qa_write_blob_file($blob['blobid'], $blob['content'], $blob['format']))
						qa_db_blob_set_content($blob['blobid'], null);
						
					$next=1+$blob['blobid'];
					$done++;
					$continue=true;
				
				} else
					qa_recalc_transition($state, 'doblobstodisk_complete');
				break;
				
			case 'doblobstodb':
				qa_recalc_transition($state, 'doblobstodb_move');
				break;
			
			case 'doblobstodb_move':
				$blob=qa_db_get_next_blob_on_disk($next);
				
				if (isset($blob)) {
					require_once QA_INCLUDE_DIR.'qa-app-blobs.php';
					require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
					
					$content=qa_read_blob_file($blob['blobid'], $blob['format']);
					qa_db_blob_set_content($blob['blobid'], $content);
					qa_delete_blob_file($blob['blobid'], $blob['format']);
						
					$next=1+$blob['blobid'];
					$done++;
					$continue=true;
				
				} else
					qa_recalc_transition($state, 'doblobstodb_complete');
				break;

			default:
				$state='';
				break;
		}
		
		if ($continue)
			$state=$operation."\t".$length."\t".$next."\t".$done;
		
		return $continue && ($done<$length);
	}
	

	function qa_recalc_transition(&$state, $operation)
/*
	Change the $state to represent the beginning of a new $operation
*/
	{
		$length=qa_recalc_stage_length($operation);
		$next=(QA_FINAL_EXTERNAL_USERS && ($operation=='dorecalcpoints_recalc')) ? '' : 0;
		$done=0;
		
		$state=$operation."\t".$length."\t".$next."\t".$done;
	}

		
	function qa_recalc_stage_length($operation)
/*
	Return how many steps there will be in recalculation $operation
*/
	{
		switch ($operation) {
			case 'doreindexcontent_pagereindex':
				$length=qa_db_count_pages();
				break;
			
			case 'doreindexcontent_postreindex':
				$length=qa_opt('cache_qcount')+qa_opt('cache_acount')+qa_opt('cache_ccount');
				break;
			
			case 'doreindexposts_wordcount':
				$length=qa_db_count_words();
				break;
				
			case 'dorecalcpoints_recalc':
				$length=qa_opt('cache_userpointscount');
				break;
				
			case 'dorecountposts_votecount':
			case 'dorecountposts_acount':
			case 'dorecalccategories_postupdate':
				$length=qa_db_count_posts();
				break;
				
			case 'dorefillevents_refill':
				$length=qa_opt('cache_qcount')+qa_db_count_posts('Q_HIDDEN');
				break;
			
			case 'dorecalccategories_recount':
			case 'dorecalccategories_backpaths':
				$length=qa_db_count_categories();
				break;
			
			case 'dodeletehidden_comments':
				$length=count(qa_db_posts_get_for_deleting('C'));
				break;
				
			case 'dodeletehidden_answers':
				$length=count(qa_db_posts_get_for_deleting('A'));
				break;
				
			case 'dodeletehidden_questions':
				$length=count(qa_db_posts_get_for_deleting('Q'));
				break;
				
			case 'doblobstodisk_move':
				$length=qa_db_count_blobs_in_db();
				break;
			
			case 'doblobstodb_move':
				$length=qa_db_count_blobs_on_disk();
				break;
			
			default:
				$length=0;
				break;
		}
		
		return $length;
	}

	
	function qa_recalc_get_message($state)
/*
	Return a string which gives a user-viewable version of $state
*/
	{
		@list($operation, $length, $next, $done)=explode("\t", $state);
		
		$done=(int)$done;
		$length=(int)$length;
		
		switch ($operation) {
			case 'doreindexcontent_postcount':
			case 'dorecountposts_postcount':
			case 'dorecalccategories_postcount':
			case 'dorefillevents_qcount':
				$message=qa_lang('admin/recalc_posts_count');
				break;
				
			case 'doreindexcontent_pagereindex':
				$message=strtr(qa_lang('admin/reindex_pages_reindexed'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;

			case 'doreindexcontent_postreindex':
				$message=strtr(qa_lang('admin/reindex_posts_reindexed'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'doreindexposts_wordcount':
				$message=strtr(qa_lang('admin/reindex_posts_wordcounted'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorecountposts_votecount':
				$message=strtr(qa_lang('admin/recount_posts_votes_recounted'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorecountposts_acount':
				$message=strtr(qa_lang('admin/recount_posts_as_recounted'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'doreindexposts_complete':
				$message=qa_lang('admin/reindex_posts_complete');
				break;
				
			case 'dorecountposts_complete':
				$message=qa_lang('admin/recount_posts_complete');
				break;
				
			case 'dorecalcpoints_usercount':
				$message=qa_lang('admin/recalc_points_usercount');
				break;
				
			case 'dorecalcpoints_recalc':
				$message=strtr(qa_lang('admin/recalc_points_recalced'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorecalcpoints_complete':
				$message=qa_lang('admin/recalc_points_complete');
				break;
				
			case 'dorefillevents_refill':
				$message=strtr(qa_lang('admin/refill_events_refilled'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorefillevents_complete':
				$message=qa_lang('admin/refill_events_complete');
				break;
				
			case 'dorecalccategories_postupdate':
				$message=strtr(qa_lang('admin/recalc_categories_updated'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorecalccategories_recount':
				$message=strtr(qa_lang('admin/recalc_categories_recounting'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorecalccategories_backpaths':
				$message=strtr(qa_lang('admin/recalc_categories_backpaths'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dorecalccategories_complete':
				$message=qa_lang('admin/recalc_categories_complete');
				break;
				
			case 'dodeletehidden_comments':
				$message=strtr(qa_lang('admin/hidden_comments_deleted'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dodeletehidden_answers':
				$message=strtr(qa_lang('admin/hidden_answers_deleted'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'dodeletehidden_questions':
				$message=strtr(qa_lang('admin/hidden_questions_deleted'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;

			case 'dodeletehidden_complete':
				$message=qa_lang('admin/delete_hidden_complete');
				break;
				
			case 'doblobstodisk_move':
			case 'doblobstodb_move':
				$message=strtr(qa_lang('admin/blobs_move_moved'), array(
					'^1' => number_format($done),
					'^2' => number_format($length)
				));
				break;
				
			case 'doblobstodisk_complete':
			case 'doblobstodb_complete':
				$message=qa_lang('admin/blobs_move_complete');
				break;
			
			default:
				$message='';
				break;
		}
		
		return $message;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/