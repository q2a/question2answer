<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-widget-related-qs.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Widget module class for related questions


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

	class qa_related_qs {
		
		var $voteformcode;
		
		
		function allow_template($template)
		{
			return ($template=='question');
		}

		
		function allow_region($region)
		{
			return ($region=='side') || ($region=='main') || ($region=='full');
		}

		
		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			
			if (@$qa_content['q_view']['raw']['type']!='Q') // question might not be visible, etc...
				return;
				
			$questionid=$qa_content['q_view']['raw']['postid'];
			
			$userid=qa_get_logged_in_userid();
			$cookieid=qa_cookie_get();
			
			$questions=qa_db_single_select(qa_db_related_qs_selectspec($userid, $questionid, qa_opt('page_size_related_qs')));
				
			$minscore=qa_match_to_min_score(qa_opt('match_related_qs'));
			
			foreach ($questions as $key => $question)
				if ($question['score']<$minscore) 
					unset($questions[$key]);

			$titlehtml=qa_lang_html(count($questions) ? 'main/related_qs_title' : 'main/no_related_qs_title');
			
			if ($region=='side') {
				$themeobject->output(
					'<div class="qa-related-qs">',
					'<h2 style="margin-top:0; padding-top:0;">',
					$titlehtml,
					'</h2>'
				);
				
				$themeobject->output('<ul class="qa-related-q-list">');

				foreach ($questions as $question)
					$themeobject->output('<li class="qa-related-q-item"><a href="'.qa_q_path_html($question['postid'], $question['title']).'">'.qa_html($question['title']).'</a></li>');

				$themeobject->output(
					'</ul>',
					'</div>'
				);

			} else {
				$themeobject->output(
					'<h2>',
					$titlehtml,
					'</h2>'
				);

				$q_list=array(
					'form' => array(
						'tags' => 'method="post" action="'.qa_self_html().'"',

						'hidden' => array(
							'code' => qa_get_form_security_code('vote'),
						),
					),
					
					'qs' => array(),
				);
				
				$defaults=qa_post_html_defaults('Q');
				$usershtml=qa_userids_handles_html($questions);
				
				foreach ($questions as $question)
					$q_list['qs'][]=qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, qa_post_html_options($question, $defaults));

				$themeobject->q_list_and_form($q_list);
			}
		}

	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/