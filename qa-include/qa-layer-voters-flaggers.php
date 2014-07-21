<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/


	File: qa-include/qa-layer-voters-flaggers.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Theme layer class for viewing voters and flaggers


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

	class qa_html_theme_layer extends qa_html_theme_base {

		private $qa_voters_flaggers_queue=array();
		private $qa_voters_flaggers_cache=array();


	//	Utility functions for this layer

		private function queue_post_voters_flaggers($post)
		{
			if (!qa_user_post_permit_error('permit_view_voters_flaggers', $post)) {
				$postids=array(@$post['postid'], @$post['opostid']); // opostid can be relevant for flags

				foreach ($postids as $postid)
					if (isset($postid) && !isset($this->qa_voters_flaggers_cache[$postid]))
						$this->qa_voters_flaggers_queue[$postid]=true;
			}
		}

		private function queue_raw_posts_voters_flaggers($posts)
		{
			if (is_array($posts))
				foreach ($posts as $post)
					if (isset($post['raw']))
						$this->queue_post_voters_flaggers($post['raw']);
		}

		private function retrieve_queued_voters_flaggers()
		{
			if (count($this->qa_voters_flaggers_queue)) {
				require_once QA_INCLUDE_DIR.'qa-db-votes.php';

				$postids=array_keys($this->qa_voters_flaggers_queue);

				foreach ($postids as $postid)
					$this->qa_voters_flaggers_cache[$postid]=array();

				$newvotersflaggers=qa_db_uservoteflag_posts_get($postids);

				if (QA_FINAL_EXTERNAL_USERS) {
					$keyuserids=array();
					foreach ($newvotersflaggers as $voterflagger)
						$keyuserids[$voterflagger['userid']]=true;

					$useridhandles=qa_get_public_from_userids(array_keys($keyuserids));
					foreach ($newvotersflaggers as $index => $voterflagger)
						$newvotersflaggers[$index]['handle']=@$useridhandles[$voterflagger['userid']];
				}

				foreach ($newvotersflaggers as $voterflagger)
					$this->qa_voters_flaggers_cache[$voterflagger['postid']][]=$voterflagger;

				$this->qa_voters_flaggers_queue=array();
			}
		}

		private function get_post_voters_flaggers($post, $postid)
		{
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';

			if (!isset($this->qa_voters_flaggers_cache[$postid])) {
				$this->queue_post_voters_flaggers($post);
				$this->retrieve_queued_voters_flaggers();
			}

			$votersflaggers=@$this->qa_voters_flaggers_cache[$postid];

			if (isset($votersflaggers))
				qa_sort_by($votersflaggers, 'handle');

			return $votersflaggers;
		}

	//	Returns a list of handles that have flagged the post

		public function get_post_voters_with_flag($post) {
			$voters = array();

			if (isset($post['raw']['opostid']))
				$postid = $post['raw']['opostid'];
			elseif (isset($post['raw']['postid']))
				$postid = $post['raw']['postid'];

			if (isset($postid)) {
				$votersflaggers = $this->get_post_voters_flaggers($post, $postid);

				if (isset($votersflaggers))
					foreach ($votersflaggers as $voterflagger)
						if ($voterflagger['flag'] > 0)
							$voters[] = $voterflagger['handle'];
			}
			return $voters;
		}

	//	Returns a key-value array containing 2 indexed arrays: one with the users who have downvoted the question and other for users who have upvoted it

		public function get_post_voters_with_upvote_or_downvote($post) {
			$voters = array(
				'up' => array(),
				'down' => array(),
			);

			$votersflaggers = $this->get_post_voters_flaggers($post['raw'], @$post['vote_opostid'] ? $post['raw']['opostid'] : $post['raw']['postid']);

			if (isset($votersflaggers))
				foreach ($votersflaggers as $voterflagger)
					if ($voterflagger['vote'] > 0)
						$voters['up'][] = $voterflagger['handle'];
					elseif ($voterflagger['vote'] < 0)
						$voters['down'][] = $voterflagger['handle'];

			return $voters;
		}

	//	Collect up all required postids for the entire page to save DB queries - common case where whole page output

		public function main()
		{
			foreach ($this->content as $key => $part) {
				if (strpos($key, 'q_list')===0)
					$this->queue_raw_posts_voters_flaggers(@$part['qs']);

				elseif (strpos($key, 'q_view')===0) {
					$this->queue_post_voters_flaggers($part['raw']);
					$this->queue_raw_posts_voters_flaggers($part['c_list']['cs']);

				} elseif (strpos($key, 'a_list')===0) {
					if (!empty($part)) {
						$this->queue_raw_posts_voters_flaggers($part['as']);

						foreach ($part['as'] as $a_item)
							$this->queue_raw_posts_voters_flaggers(@$a_item['c_list']['cs']);
					}
				}
			}

			qa_html_theme_base::main();
		}

	//	Other functions which also collect up required postids for lists to save DB queries - helps with widget output and Ajax calls

		public function q_list_items($q_items)
		{
			$this->queue_raw_posts_voters_flaggers($q_items);

			qa_html_theme_base::q_list_items($q_items);
		}

		public function a_list_items($a_items)
		{
			$this->queue_raw_posts_voters_flaggers($a_items);

			qa_html_theme_base::a_list_items($a_items);
		}

		public function c_list_items($c_items)
		{
			$this->queue_raw_posts_voters_flaggers($c_items);

			qa_html_theme_base::c_list_items($c_items);
		}

	//	Actual output of the voters and flaggers

		public function vote_count($post) {
			$voterswithupordown = $this->get_post_voters_with_upvote_or_downvote($post);

			$tooltip = '';

			if (!empty($voterswithupordown['up'])) {
				$tooltip .= '&uarr; ' . qa_html(implode(', ', $voterswithupordown['up']));
				if (!empty($voterswithupordown['down']))
					$tooltip .= "\n\n";
			}
			if (!empty($voterswithupordown['down']))
				$tooltip .= '&darr; ' . qa_html(implode(', ', $voterswithupordown['down']));

			$post['vote_count_tags'] = (isset($post['vote_count_tags']) ? $post['vote_count_tags'] : '') . ' title="' . $tooltip . '"';

			qa_html_theme_base::vote_count($post);
		}

		public function post_meta_flags($post, $class) {
			$voterswithflag = $this->get_post_voters_with_flag($post);

			$tooltip = trim(qa_html(implode(', ', $voterswithflag)));

			if (!empty($voterswithflag))
				$this->output('<span title="&#9873; ' . $tooltip . '">');

			qa_html_theme_base::post_meta_flags($post, $class);

			if (!empty($voterswithflag))
				$this->output('</span>');
		}

}


/*
	Omit PHP closing tag to help avoid accidental output
*/