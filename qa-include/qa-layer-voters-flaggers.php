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
		
		var $qa_voters_flaggers_queue=array();
		var $qa_voters_flaggers_cache=array();
		

	//	Utility functions for this layer
		
		function queue_post_voters_flaggers($post)
		{
			if (!qa_user_post_permit_error('permit_view_voters_flaggers', $post)) {
				$postids=array(@$post['postid'], @$post['opostid']); // opostid can be relevant for flags
			
				foreach ($postids as $postid)
					if (isset($postid) && !isset($this->qa_voters_flaggers_cache[$postid]))
						$this->qa_voters_flaggers_queue[$postid]=true;
			}
		}
				
		function queue_raw_posts_voters_flaggers($posts)
		{
			if (is_array($posts))
				foreach ($posts as $post)
					if (isset($post['raw']))
						$this->queue_post_voters_flaggers($post['raw']);
		}
		
		function retrieve_queued_voters_flaggers()
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
		
		function get_post_voters_flaggers($post, $postid)
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
		

	//	Collect up all required postids for the entire page to save DB queries - common case where whole page output
		
		function main()
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
	
		function q_list_items($q_items)
		{
			$this->queue_raw_posts_voters_flaggers($q_items);
			
			qa_html_theme_base::q_list_items($q_items);
		}
		
		function a_list_items($a_items)
		{
			$this->queue_raw_posts_voters_flaggers($a_items);
			
			qa_html_theme_base::a_list_items($a_items);
		}

		function c_list_items($c_items)
		{
			$this->queue_raw_posts_voters_flaggers($c_items);
			
			qa_html_theme_base::c_list_items($c_items);
		}
		
	
	//	Actual output of the voters and flaggers
	
		function vote_count($post)
		{
			$votersflaggers=$this->get_post_voters_flaggers($post['raw'], @$post['vote_opostid'] ? $post['raw']['opostid'] : $post['raw']['postid']);
			
			$tooltip='';
			
			if (isset($votersflaggers)) {
				$uphandles='';
				$downhandles='';
				
				foreach ($votersflaggers as $voterflagger) {
					if ($voterflagger['vote']>0)
						$uphandles.=(strlen($uphandles) ? ', ' : '').qa_html($voterflagger['handle']);
	
					if ($voterflagger['vote']<0)
						$downhandles.=(strlen($downhandles) ? ', ' : '').qa_html($voterflagger['handle']);
					
					$tooltip=trim((strlen($uphandles) ? ('&uarr; '.$uphandles) : '')."\n\n".(strlen($downhandles) ? ('&darr; '.$downhandles) : ''));
				}
			}
			
			$post['vote_count_tags']=@$post['vote_count_tags'].' title="'.$tooltip.'"';
			
			qa_html_theme_base::vote_count($post);
		}
		
		
		function post_meta_flags($post, $class)
		{
			$postid=@$post['raw']['opostid'];
			if (!isset($postid))
				$postid=@$post['raw']['postid'];
	
			$tooltip='';
			
			if (isset($postid)) {
				$votersflaggers=$this->get_post_voters_flaggers($post, $postid);
				
				if (isset($votersflaggers))
					foreach ($votersflaggers as $voterflagger)
						if ($voterflagger['flag']>0)
							$tooltip.=(strlen($tooltip) ? ', ' : '').qa_html($voterflagger['handle']);
			}
						
			if (strlen($tooltip))
				$this->output('<span title="&#9873; '.$tooltip.'">');
			
			qa_html_theme_base::post_meta_flags($post, $class);
			
			if (strlen($tooltip))
				$this->output('</span>');
		}

	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/