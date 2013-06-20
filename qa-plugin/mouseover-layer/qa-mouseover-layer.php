<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/mouseover-layer/qa-mouseover-layer.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Theme layer class for mouseover layer plugin


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
		
		function q_list($q_list)
		{
			if (count(@$q_list['qs']) && qa_opt('mouseover_content_on')) { // first check it is not an empty list and the feature is turned on

			//	Collect the question ids of all items in the question list (so we can do this in one DB query)
	
				$postids=array();
				foreach ($q_list['qs'] as $question)
					if (isset($question['raw']['postid']))
						$postids[]=$question['raw']['postid'];
				
				if (count($postids)) {

				//	Retrieve the content for these questions from the database and put into an array
				
					$result=qa_db_query_sub('SELECT postid, content, format FROM ^posts WHERE postid IN (#)', $postids);
					$postinfo=qa_db_read_all_assoc($result, 'postid');
					
				//	Get the regular expression fragment to use for blocked words and the maximum length of content to show
					
					$blockwordspreg=qa_get_block_words_preg();
					$maxlength=qa_opt('mouseover_content_max_len');
					
				//	Now add the popup to the title for each question
			
					foreach ($q_list['qs'] as $index => $question) {
						$thispost=@$postinfo[$question['raw']['postid']];
						
						if (isset($thispost)) {
							$text=qa_viewer_text($thispost['content'], $thispost['format'], array('blockwordspreg' => $blockwordspreg));
							$text=qa_shorten_string_line($text, $maxlength);
							$q_list['qs'][$index]['title']='<span title="'.qa_html($text).'">'.@$question['title'].'</span>';
						}
					}
				}
			}
			
			qa_html_theme_base::q_list($q_list); // call back through to the default function
		}

	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/