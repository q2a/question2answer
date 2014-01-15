<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-format.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Common functions for creating theme-ready structures from data


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
		header('Location: ../');
		exit;
	}

	define('QA_PAGE_FLAGS_EXTERNAL', 1);
	define('QA_PAGE_FLAGS_NEW_WINDOW', 2);

	
	function qa_time_to_string($seconds)
/*
	Return textual representation of $seconds
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$seconds=max($seconds, 1);
		
		$scales=array(
			31557600 => array( 'main/1_year'   , 'main/x_years'   ),
			 2629800 => array( 'main/1_month'  , 'main/x_months'  ),
			  604800 => array( 'main/1_week'   , 'main/x_weeks'   ),
			   86400 => array( 'main/1_day'    , 'main/x_days'    ),
			    3600 => array( 'main/1_hour'   , 'main/x_hours'   ),
			      60 => array( 'main/1_minute' , 'main/x_minutes' ),
			       1 => array( 'main/1_second' , 'main/x_seconds' ),
		);
		
		foreach ($scales as $scale => $phrases)
			if ($seconds>=$scale) {
				$count=floor($seconds/$scale);
			
				if ($count==1)
					$string=qa_lang($phrases[0]);
				else
					$string=qa_lang_sub($phrases[1], $count);
					
				break;
			}
			
		return $string;
	}
	
	
	function qa_post_is_by_user($post, $userid, $cookieid)
/*
	Check if $post is by user $userid, or if post is anonymous and $userid not specified, then
	check if $post is by the anonymous user identified by $cookieid
*/
	{
		// In theory we should only test against NULL here, i.e. use isset($post['userid'])
		// but the risk of doing so is so high (if a bug creeps in that allows userid=0)
		// that I'm doing a tougher test. This will break under a zero user or cookie id.
		
		if (@$post['userid'] || $userid)
			return @$post['userid']==$userid;
		elseif (@$post['cookieid'])
			return strcmp($post['cookieid'], $cookieid)==0;
		
		return false;
	}

	
	function qa_userids_handles_html($useridhandles, $microformats=false)
/*
	Return array which maps the ['userid'] and/or ['lastuserid'] in each element of
	$useridhandles to its HTML representation. For internal user management, corresponding
	['handle'] and/or ['lasthandle'] are required in each element.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		if (QA_FINAL_EXTERNAL_USERS) {
			$keyuserids=array();
	
			foreach ($useridhandles as $useridhandle) {
				if (isset($useridhandle['userid']))
					$keyuserids[$useridhandle['userid']]=true;

				if (isset($useridhandle['lastuserid']))
					$keyuserids[$useridhandle['lastuserid']]=true;
			}
	
			if (count($keyuserids))
				return qa_get_users_html(array_keys($keyuserids), true, qa_path_to_root(), $microformats);
			else
				return array();
		
		} else {
			$usershtml=array();
			$favoritemap=qa_get_favorite_non_qs_map();

			foreach ($useridhandles as $useridhandle) {
				if (isset($useridhandle['userid']) && $useridhandle['handle'])
					$usershtml[$useridhandle['userid']]=qa_get_one_user_html($useridhandle['handle'], $microformats, @$favoritemap['user'][$useridhandle['userid']]);

				if (isset($useridhandle['lastuserid']) && $useridhandle['lasthandle'])
					$usershtml[$useridhandle['lastuserid']]=qa_get_one_user_html($useridhandle['lasthandle'], $microformats, @$favoritemap['user'][$useridhandle['lastuserid']]);
			}
		
			return $usershtml;
		}
	}
	
	
	function qa_get_favorite_non_qs_map()
/*
	Get an array listing all of the logged in user's favorite items, except their favorited questions (these are excluded because
	users tend to favorite many more questions than other things.) The top-level array can contain three keys - 'user' for favorited
	users, 'tag' for tags, 'category' for categories. The next level down has the identifier for each favorited entity in the *key*
	of the array, and true for its value. If no user is logged in the empty array is returned. The result is cached for future calls.
*/
	{
		global $qa_favorite_non_qs_map;
		
		if (!isset($qa_favorite_non_qs_map)) {
			$qa_favorite_non_qs_map=array();
			$loginuserid=qa_get_logged_in_userid();
			
			if (isset($loginuserid)) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
			
				$favoritenonqs=qa_db_get_pending_result('favoritenonqs', qa_db_user_favorite_non_qs_selectspec($loginuserid));
				
				foreach ($favoritenonqs as $favorite)
					switch ($favorite['type']) {
						case QA_ENTITY_USER:
							$qa_favorite_non_qs_map['user'][$favorite['userid']]=true;
							break;
						
						case QA_ENTITY_TAG:
							$qa_favorite_non_qs_map['tag'][qa_strtolower($favorite['tags'])]=true;
							break;
						
						case QA_ENTITY_CATEGORY:
							$qa_favorite_non_qs_map['category'][$favorite['categorybackpath']]=true;
							break;
					}
			}
		}

		return $qa_favorite_non_qs_map;
	}

	
	function qa_tag_html($tag, $microformats=false, $favorited=false)
/*
	Convert textual $tag to HTML representation, with microformats if $microformats is true. Set $favorited to true to show the tag as favorited.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return '<a href="'.qa_path_html('tag/'.$tag).'"'.($microformats ? ' rel="tag"' : '').' class="qa-tag-link'.
			($favorited ? ' qa-tag-favorited' : '').'">'.qa_html($tag).'</a>';
	}

	
	function qa_category_path($navcategories, $categoryid)
/*
	Given $navcategories retrieved for $categoryid from the database (using qa_db_category_nav_selectspec(...)),
	return an array of elements from $navcategories for the hierarchy down to $categoryid.
*/
	{
		$upcategories=array();
		
		for ($upcategory=@$navcategories[$categoryid]; isset($upcategory); $upcategory=@$navcategories[$upcategory['parentid']])
			$upcategories[$upcategory['categoryid']]=$upcategory;
			
		return array_reverse($upcategories, true);
	}
	

	function qa_category_path_html($navcategories, $categoryid)
/*
	Given $navcategories retrieved for $categoryid from the database (using qa_db_category_nav_selectspec(...)),
	return some HTML that shows the category hierarchy down to $categoryid.
*/
	{
		$categories=qa_category_path($navcategories, $categoryid);
		
		$html='';
		foreach ($categories as $category)
			$html.=(strlen($html) ? ' / ' : '').qa_html($category['title']);
			
		return $html;
	}
	
	
	function qa_category_path_request($navcategories, $categoryid)
/*
	Given $navcategories retrieved for $categoryid from the database (using qa_db_category_nav_selectspec(...)),
	return a Q2A request string that represents the category hierarchy down to $categoryid.
*/
	{
		$categories=qa_category_path($navcategories, $categoryid);

		$request='';
		foreach ($categories as $category)
			$request.=(strlen($request) ? '/' : '').$category['tags'];
			
		return $request;
	}
	
	
	function qa_ip_anchor_html($ip, $anchorhtml=null)
/*
	Return HTML to use for $ip address, which links to appropriate page with $anchorhtml
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if (!strlen($anchorhtml))
			$anchorhtml=qa_html($ip);
		
		return '<a href="'.qa_path_html('ip/'.$ip).'" title="'.qa_lang_html_sub('main/ip_address_x', qa_html($ip)).'" class="qa-ip-link">'.$anchorhtml.'</a>';
	}
	
	
	function qa_post_html_fields($post, $userid, $cookieid, $usershtml, $dummy, $options=array())
/*
	Given $post retrieved from database, return array of mostly HTML to be passed to theme layer.
	$userid and $cookieid refer to the user *viewing* the page.
	$usershtml is an array of [user id] => [HTML representation of user] built ahead of time.
	$dummy is a placeholder (used to be $categories parameter but that's no longer needed)
	$options is an array which sets what is displayed (see qa_post_html_defaults() in qa-app-options.php)
	If something is missing from $post (e.g. ['content']), correponding HTML also omitted.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		if (isset($options['blockwordspreg']))
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$fields=array('raw' => $post);
		
	//	Useful stuff used throughout function

		$postid=$post['postid'];
		$isquestion=($post['basetype']=='Q');
		$isanswer=($post['basetype']=='A');
		$isbyuser=qa_post_is_by_user($post, $userid, $cookieid);
		$anchor=urlencode(qa_anchor($post['basetype'], $postid));
		$elementid=isset($options['elementid']) ? $options['elementid'] : $anchor;
		$microformats=@$options['microformats'];
		$isselected=@$options['isselected'];
		$favoritedview=@$options['favoritedview'];
		$favoritemap=$favoritedview ? qa_get_favorite_non_qs_map() : array();
		
	//	High level information

		$fields['hidden']=@$post['hidden'];
		$fields['tags']='id="'.qa_html($elementid).'"';
		
		$fields['classes']=($isquestion && $favoritedview && @$post['userfavoriteq']) ? 'qa-q-favorited' : '';
		if ($isquestion && isset($post['closedbyid']))
			$fields['classes']=ltrim($fields['classes'].' qa-q-closed');
	
		if ($microformats)
			$fields['classes'].=' hentry '.($isquestion ? 'question' : ($isanswer ? ($isselected ? 'answer answer-selected' : 'answer') : 'comment'));

	//	Question-specific stuff (title, URL, tags, answer count, category)
	
		if ($isquestion) {
			if (isset($post['title'])) {
				$fields['url']=qa_q_path_html($postid, $post['title']);
				
				if (isset($options['blockwordspreg']))
					$post['title']=qa_block_words_replace($post['title'], $options['blockwordspreg']);
				
				$fields['title']=qa_html($post['title']);
				if ($microformats)
					$fields['title']='<span class="entry-title">'.$fields['title'].'</span>';
					
				/*if (isset($post['score'])) // useful for setting match thresholds
					$fields['title'].=' <small>('.$post['score'].')</small>';*/
			}
				
			if (@$options['tagsview'] && isset($post['tags'])) {
				$fields['q_tags']=array();
				
				$tags=qa_tagstring_to_tags($post['tags']);
				foreach ($tags as $tag) {
					if (isset($options['blockwordspreg']) && count(qa_block_words_match_all($tag, $options['blockwordspreg']))) // skip censored tags
						continue;
				
					$fields['q_tags'][]=qa_tag_html($tag, $microformats, @$favoritemap['tag'][qa_strtolower($tag)]);
				}
			}
		
			if (@$options['answersview'] && isset($post['acount'])) {
				$fields['answers_raw']=$post['acount'];
				
				$fields['answers']=($post['acount']==1) ? qa_lang_html_sub_split('main/1_answer', '1', '1')
					: qa_lang_html_sub_split('main/x_answers', number_format($post['acount']));
					
				$fields['answer_selected']=isset($post['selchildid']);
			}
			
			if (@$options['viewsview'] && isset($post['views'])) {
				$fields['views_raw']=$post['views'];
				
				$fields['views']=($post['views']==1) ? qa_lang_html_sub_split('main/1_view', '1', '1') :
					qa_lang_html_sub_split('main/x_views', number_format($post['views']));
			}

			if (@$options['categoryview'] && isset($post['categoryname']) && isset($post['categorybackpath'])) {
				$favoriteclass='';
				
				if (count(@$favoritemap['category'])) {
					if (@$favoritemap['category'][$post['categorybackpath']])
						$favoriteclass=' qa-cat-favorited';

					else
						foreach ($favoritemap['category'] as $categorybackpath => $dummy)
							if (substr('/'.$post['categorybackpath'], -strlen($categorybackpath))==$categorybackpath)
								$favoriteclass=' qa-cat-parent-favorited';
				}
				
				$fields['where']=qa_lang_html_sub_split('main/in_category_x',
					'<a href="'.qa_path_html(@$options['categorypathprefix'].implode('/', array_reverse(explode('/', $post['categorybackpath'])))).
					'" class="qa-category-link'.$favoriteclass.'">'.qa_html($post['categoryname']).'</a>');
			}
		}
		
	//	Answer-specific stuff (selection)
		
		if ($isanswer) {
			$fields['selected']=$isselected;
			
			if ($isselected)
				$fields['select_text']=qa_lang_html('question/select_text');
		}

	//	Post content
		
		if (@$options['contentview'] && !empty($post['content'])) {
			$viewer=qa_load_viewer($post['content'], $post['format']);
			
			$fields['content']=$viewer->get_html($post['content'], $post['format'], array(
				'blockwordspreg' => @$options['blockwordspreg'],
				'showurllinks' => @$options['showurllinks'],
				'linksnewwindow' => @$options['linksnewwindow'],
			));
			
			if ($microformats)
				$fields['content']='<div class="entry-content">'.$fields['content'].'</div>';
			
			$fields['content']='<a name="'.qa_html($postid).'"></a>'.$fields['content'];
				// this is for backwards compatibility with any existing links using the old style of anchor
				// that contained the post id only (changed to be valid under W3C specifications)
		}
		
	//	Voting stuff
			
		if (@$options['voteview']) {
			$voteview=$options['voteview'];
		
		//	Calculate raw values and pass through
			
			if (@$options['ovoteview'] && isset($post['opostid'])) {
				$upvotes=(int)@$post['oupvotes'];
				$downvotes=(int)@$post['odownvotes'];
				$fields['vote_opostid']=true; // for voters/flaggers layer
			} else {
				$upvotes=(int)@$post['upvotes'];
				$downvotes=(int)@$post['downvotes'];
			}

			$netvotes=(int)($upvotes-$downvotes);
			
			$fields['upvotes_raw']=$upvotes;
			$fields['downvotes_raw']=$downvotes;
			$fields['netvotes_raw']=$netvotes;

		//	Create HTML versions...
			
			$upvoteshtml=qa_html($upvotes);
			$downvoteshtml=qa_html($downvotes);

			if ($netvotes>=1)
				$netvoteshtml='+'.qa_html($netvotes);
			elseif ($netvotes<=-1)
				$netvoteshtml='&ndash;'.qa_html(-$netvotes);
			else
				$netvoteshtml='0';
				
		//	...with microformats if appropriate

			if ($microformats) {
				$netvoteshtml.='<span class="votes-up"><span class="value-title" title="'.$upvoteshtml.'"></span></span>'.
					'<span class="votes-down"><span class="value-title" title="'.$downvoteshtml.'"></span></span>';
				$upvoteshtml='<span class="votes-up">'.$upvoteshtml.'</span>';
				$downvoteshtml='<span class="votes-down">'.$downvoteshtml.'</span>';
			}
			
		//	Pass information on vote viewing
		
		//	$voteview will be one of:
		//	updown, updown-disabled-page, updown-disabled-level, updown-uponly-level, updown-disabled-approve, updown-uponly-approve
		//	net, net-disabled-page, net-disabled-level, net-uponly-level, net-disabled-approve, net-uponly-approve
				
			$fields['vote_view']=(substr($voteview, 0, 6)=='updown') ? 'updown' : 'net';
			
			$fields['vote_on_page']=strpos($voteview, '-disabled-page') ? 'disabled' : 'enabled';
			
			$fields['upvotes_view']=($upvotes==1) ? qa_lang_html_sub_split('main/1_liked', $upvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_liked', $upvoteshtml);
	
			$fields['downvotes_view']=($downvotes==1) ? qa_lang_html_sub_split('main/1_disliked', $downvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_disliked', $downvoteshtml);
			
			$fields['netvotes_view']=(abs($netvotes)==1) ? qa_lang_html_sub_split('main/1_vote', $netvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_votes', $netvoteshtml);
		
		//	Voting buttons
			
			$fields['vote_tags']='id="voting_'.qa_html($postid).'"';
			$onclick='onclick="return qa_vote_click(this);"';
			
			if ($fields['hidden']) {
				$fields['vote_state']='disabled';
				$fields['vote_up_tags']='title="'.qa_lang_html($isanswer ? 'main/vote_disabled_hidden_a' : 'main/vote_disabled_hidden_q').'"';
				$fields['vote_down_tags']=$fields['vote_up_tags'];
			
			} elseif ($isbyuser) {
				$fields['vote_state']='disabled';
				$fields['vote_up_tags']='title="'.qa_lang_html($isanswer ? 'main/vote_disabled_my_a' : 'main/vote_disabled_my_q').'"';
				$fields['vote_down_tags']=$fields['vote_up_tags'];
				
			} elseif (strpos($voteview, '-disabled-')) {
				$fields['vote_state']=(@$post['uservote']>0) ? 'voted_up_disabled' : ((@$post['uservote']<0) ? 'voted_down_disabled' : 'disabled');
				
				if (strpos($voteview, '-disabled-page'))
					$fields['vote_up_tags']='title="'.qa_lang_html('main/vote_disabled_q_page_only').'"';
				elseif (strpos($voteview, '-disabled-approve'))
					$fields['vote_up_tags']='title="'.qa_lang_html('main/vote_disabled_approve').'"';
				else
					$fields['vote_up_tags']='title="'.qa_lang_html('main/vote_disabled_level').'"';
					
				$fields['vote_down_tags']=$fields['vote_up_tags'];

			} elseif (@$post['uservote']>0) {
				$fields['vote_state']='voted_up';
				$fields['vote_up_tags']='title="'.qa_lang_html('main/voted_up_popup').'" name="'.qa_html('vote_'.$postid.'_0_'.$elementid).'" '.$onclick;
				$fields['vote_down_tags']=' ';

			} elseif (@$post['uservote']<0) {
				$fields['vote_state']='voted_down';
				$fields['vote_up_tags']=' ';
				$fields['vote_down_tags']='title="'.qa_lang_html('main/voted_down_popup').'" name="'.qa_html('vote_'.$postid.'_0_'.$elementid).'" '.$onclick;
				
			} else {
				$fields['vote_up_tags']='title="'.qa_lang_html('main/vote_up_popup').'" name="'.qa_html('vote_'.$postid.'_1_'.$elementid).'" '.$onclick;
				
				if (strpos($voteview, '-uponly-level')) {
					$fields['vote_state']='up_only';
					$fields['vote_down_tags']='title="'.qa_lang_html('main/vote_disabled_down').'"';
				
				} elseif (strpos($voteview, '-uponly-approve')) {
					$fields['vote_state']='up_only';
					$fields['vote_down_tags']='title="'.qa_lang_html('main/vote_disabled_down_approve').'"';
				
				} else {
					$fields['vote_state']='enabled';
					$fields['vote_down_tags']='title="'.qa_lang_html('main/vote_down_popup').'" name="'.qa_html('vote_'.$postid.'_-1_'.$elementid).'" '.$onclick;
				}
			}
		}
		
	//	Flag count
	
		if (@$options['flagsview'] && @$post['flagcount'])
			$fields['flags']=($post['flagcount']==1) ? qa_lang_html_sub_split('main/1_flag', '1', '1')
				: qa_lang_html_sub_split('main/x_flags', $post['flagcount']);
	
	//	Created when and by whom
		
		$fields['meta_order']=qa_lang_html('main/meta_order'); // sets ordering of meta elements which can be language-specific
		
		if (@$options['whatview'] ) {
			$fields['what']=qa_lang_html($isquestion ? 'main/asked' : ($isanswer ? 'main/answered' : 'main/commented'));
				
			if (@$options['whatlink'] && strlen(@$options['q_request']))
				$fields['what_url']=($post['basetype']=='Q') ? qa_path_html($options['q_request'])
					: qa_path_html($options['q_request'], array('show' => $postid), null, null, qa_anchor($post['basetype'], $postid));
		}
		
		if (isset($post['created']) && @$options['whenview']) {
			$fields['when']=qa_when_to_html($post['created'], @$options['fulldatedays']);
			
			if ($microformats)
				$fields['when']['data']='<span class="published"><span class="value-title" title="'.gmdate('Y-m-d\TH:i:sO', $post['created']).'"></span>'.$fields['when']['data'].'</span>';
		}
		
		if (@$options['whoview']) {
			$fields['who']=qa_who_to_html($isbyuser, @$post['userid'], $usershtml, @$options['ipview'] ? @$post['createip'] : null, $microformats, $post['name']);
			
			if (isset($post['points'])) {
				if (@$options['pointsview'])
					$fields['who']['points']=($post['points']==1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
						: qa_lang_html_sub_split('main/x_points', qa_html(number_format($post['points'])));
				
				if (isset($options['pointstitle']))
					$fields['who']['title']=qa_get_points_title_html($post['points'], $options['pointstitle']);
			}
				
			if (isset($post['level']))
				$fields['who']['level']=qa_html(qa_user_level_string($post['level']));
		}

		if (@$options['avatarsize']>0) {
			if (QA_FINAL_EXTERNAL_USERS)
				$fields['avatar']=qa_get_external_avatar_html($post['userid'], $options['avatarsize'], false);
			else
				$fields['avatar']=qa_get_user_avatar_html(@$post['flags'], @$post['email'], @$post['handle'],
					@$post['avatarblobid'], @$post['avatarwidth'], @$post['avatarheight'], $options['avatarsize']);
		}

	//	Updated when and by whom
		
		if (
			@$options['updateview'] && isset($post['updated']) &&
			(($post['updatetype']!=QA_UPDATE_SELECTED) || $isselected) && // only show selected change if it's still selected
			( // otherwise check if one of these conditions is fulfilled...
				(!isset($post['created'])) || // ... we didn't show the created time (should never happen in practice)
				($post['hidden'] && ($post['updatetype']==QA_UPDATE_VISIBLE)) || // ... the post was hidden as the last action
				(isset($post['closedbyid']) && ($post['updatetype']==QA_UPDATE_CLOSED)) || // ... the post was closed as the last action
				(abs($post['updated']-$post['created'])>300) || // ... or over 5 minutes passed between create and update times
				($post['lastuserid']!=$post['userid']) // ... or it was updated by a different user
			)
		) {
			switch ($post['updatetype']) {
				case QA_UPDATE_TYPE:
				case QA_UPDATE_PARENT:
					$langstring='main/moved';
					break;
					
				case QA_UPDATE_CATEGORY:
					$langstring='main/recategorized';
					break;

				case QA_UPDATE_VISIBLE:
					$langstring=$post['hidden'] ? 'main/hidden' : 'main/reshown';
					break;
					
				case QA_UPDATE_CLOSED:
					$langstring=isset($post['closedbyid']) ? 'main/closed' : 'main/reopened';
					break;
					
				case QA_UPDATE_TAGS:
					$langstring='main/retagged';
					break;
				
				case QA_UPDATE_SELECTED:
					$langstring='main/selected';
					break;
				
				default:
					$langstring='main/edited';
					break;
			}
			
			$fields['what_2']=qa_lang_html($langstring);
			
			if (@$options['whenview']) {
				$fields['when_2']=qa_when_to_html($post['updated'], @$options['fulldatedays']);
				
				if ($microformats)
					$fields['when_2']['data']='<span class="updated"><span class="value-title" title="'.gmdate('Y-m-d\TH:i:sO', $post['updated']).'"></span>'.$fields['when_2']['data'].'</span>';
			}
			
			if (isset($post['lastuserid']) && @$options['whoview'])
				$fields['who_2']=qa_who_to_html(isset($userid) && ($post['lastuserid']==$userid), $post['lastuserid'], $usershtml, @$options['ipview'] ? $post['lastip'] : null, false);
		}
		
	//	That's it!

		return $fields;
	}
	

	function qa_message_html_fields($message, $options=array())
/*
	Given $message retrieved from database, return an array of mostly HTML to be passed to theme layer.
	Pass viewing options in $options (see qa_message_html_defaults() in qa-app-options.php)
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$fields=array('raw' => $message);
		$fields['tags']='id="m'.qa_html($message['messageid']).'"';
		
	//	Message content
		
		$viewer=qa_load_viewer($message['content'], $message['format']);
		
		$fields['content']=$viewer->get_html($message['content'], $message['format'], array(
			'blockwordspreg' => @$options['blockwordspreg'],
			'showurllinks' => @$options['showurllinks'],
			'linksnewwindow' => @$options['linksnewwindow'],
		));
		
	//	Set ordering of meta elements which can be language-specific
		
		$fields['meta_order']=qa_lang_html('main/meta_order'); 
		
		$fields['what']=qa_lang_html('main/written');

	//	When it was written
	
		if (@$options['whenview'])
			$fields['when']=qa_when_to_html($message['created'], @$options['fulldatedays']);

	//	Who wrote it, and their avatar
	
		if (@$options['whoview'])
			$fields['who']=qa_lang_html_sub_split('main/by_x', qa_get_one_user_html($message['fromhandle'], false));

		if (@$options['avatarsize']>0)
			$fields['avatar']=qa_get_user_avatar_html(@$message['fromflags'], @$message['fromemail'], @$message['fromhandle'],
				@$message['fromavatarblobid'], @$message['fromavatarwidth'], @$message['fromavatarheight'], $options['avatarsize']);
				
	//	That's it!
	
		return $fields;
	}
	
	
	function qa_who_to_html($isbyuser, $postuserid, $usershtml, $ip=null, $microformats=false, $name=null)
/*
	Return array of split HTML (prefix, data, suffix) to represent author of post
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if (isset($postuserid) && isset($usershtml[$postuserid])) {
			$whohtml=$usershtml[$postuserid];
			if ($microformats)
				$whohtml='<span class="vcard author">'.$whohtml.'</span>';

		} else {
			if (strlen($name))
				$whohtml=qa_html($name);
			elseif ($isbyuser)
				$whohtml=qa_lang_html('main/me');
			else
				$whohtml=qa_lang_html('main/anonymous');
			
			if (isset($ip))
				$whohtml=qa_ip_anchor_html($ip, $whohtml);
		}
			
		return qa_lang_html_sub_split('main/by_x', $whohtml);
	}
	
	
	function qa_when_to_html($timestamp, $fulldatedays)
/*
	Return array of split HTML (prefix, data, suffix) to represent unix $timestamp, with the full date shown if it's
	more than $fulldatedays ago
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$interval=qa_opt('db_time')-$timestamp;
		
		if ( ($interval<0) || (isset($fulldatedays) && ($interval>(86400*$fulldatedays))) ) { // full style date
			$stampyear=date('Y', $timestamp);
			$thisyear=date('Y', qa_opt('db_time'));
			
			return array(
				'data' => qa_html(strtr(qa_lang(($stampyear==$thisyear) ? 'main/date_format_this_year' : 'main/date_format_other_years'), array(
					'^day' => date((qa_lang('main/date_day_min_digits')==2) ? 'd' : 'j', $timestamp),
					'^month' => qa_lang('main/date_month_'.date('n', $timestamp)),
					'^year' => date((qa_lang('main/date_year_digits')==2) ? 'y' : 'Y', $timestamp),
				))),
			);

		} else // ago-style date
			return qa_lang_html_sub_split('main/x_ago', qa_html(qa_time_to_string($interval)));
	}

	
	function qa_other_to_q_html_fields($question, $userid, $cookieid, $usershtml, $dummy, $options)
/*
	Return array of mostly HTML to be passed to theme layer, to *link* to an answer, comment or edit on
	$question, as retrieved from database, with fields prefixed 'o' for the answer, comment or edit.
	$userid, $cookieid, $usershtml, $options are passed through to qa_post_html_fields(). If $question['opersonal']
	is set and true then the item is displayed with its personal relevance to the user (for user updates page).
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-app-updates.php';
		
		$fields=qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, $options);
		
		switch ($question['obasetype'].'-'.@$question['oupdatetype']) { 
			case 'Q-':
				$langstring='main/asked';
				break;
			
			case 'Q-'.QA_UPDATE_VISIBLE:
				if (@$question['opersonal'])
					$langstring=$question['hidden'] ? 'misc/your_q_hidden' : 'misc/your_q_reshown';
				else
					$langstring=$question['hidden'] ? 'main/hidden' : 'main/reshown';
				break;
				
			case 'Q-'.QA_UPDATE_CLOSED:
				if (@$question['opersonal'])
					$langstring=isset($question['closedbyid']) ? 'misc/your_q_closed' : 'misc/your_q_reopened';
				else
					$langstring=isset($question['closedbyid']) ? 'main/closed' : 'main/reopened';
				break;
				
			case 'Q-'.QA_UPDATE_TAGS:
				$langstring=@$question['opersonal'] ? 'misc/your_q_retagged' : 'main/retagged';
				break;
				
			case 'Q-'.QA_UPDATE_CATEGORY:
				$langstring=@$question['opersonal'] ? 'misc/your_q_recategorized' : 'main/recategorized';
				break;

			case 'A-':
				$langstring=@$question['opersonal'] ? 'misc/your_q_answered' : 'main/answered';
				break;
			
			case 'A-'.QA_UPDATE_SELECTED:
				$langstring=@$question['opersonal'] ? 'misc/your_a_selected' : 'main/answer_selected';
				break;
			
			case 'A-'.QA_UPDATE_VISIBLE:
				if (@$question['opersonal'])
					$langstring=$question['ohidden'] ? 'misc/your_a_hidden' : 'misc/your_a_reshown';
				else
					$langstring=$question['ohidden'] ? 'main/hidden' : 'main/answer_reshown';
				break;
				
			case 'A-'.QA_UPDATE_CONTENT:
				$langstring=@$question['opersonal'] ? 'misc/your_a_edited' : 'main/answer_edited';
				break;
				
			case 'Q-'.QA_UPDATE_FOLLOWS:
				$langstring=@$question['opersonal'] ? 'misc/your_a_questioned' : 'main/asked_related_q';
				break;
			
			case 'C-':
				$langstring='main/commented';
				break;
				
			case 'C-'.QA_UPDATE_C_FOR_Q:
				$langstring=@$question['opersonal'] ? 'misc/your_q_commented' : 'main/commented';
				break;
			
			case 'C-'.QA_UPDATE_C_FOR_A:
				$langstring=@$question['opersonal'] ? 'misc/your_a_commented' : 'main/commented';
				break;
			
			case 'C-'.QA_UPDATE_FOLLOWS:
				$langstring=@$question['opersonal'] ? 'misc/your_c_followed' : 'main/commented';
				break;
			
			case 'C-'.QA_UPDATE_TYPE:
				$langstring=@$question['opersonal'] ? 'misc/your_c_moved' : 'main/comment_moved';
				break;
				
			case 'C-'.QA_UPDATE_VISIBLE:
				if (@$question['opersonal'])
					$langstring=$question['ohidden'] ? 'misc/your_c_hidden' : 'misc/your_c_reshown';
				else
					$langstring=$question['ohidden'] ? 'main/hidden' : 'main/comment_reshown';
				break;
				
			case 'C-'.QA_UPDATE_CONTENT:
				$langstring=@$question['opersonal'] ? 'misc/your_c_edited' : 'main/comment_edited';
				break;
			
			case 'Q-'.QA_UPDATE_CONTENT:
			default:
				$langstring=@$question['opersonal'] ? 'misc/your_q_edited' : 'main/edited';
				break;
		}
		
		$fields['what']=qa_lang_html($langstring);
		
		if (@$question['opersonal'])
			$fields['what_your']=true;
			
		if ( ($question['obasetype']!='Q') || (@$question['oupdatetype']==QA_UPDATE_FOLLOWS) )
			$fields['what_url']=qa_q_path_html($question['postid'], $question['title'], false, $question['obasetype'], $question['opostid']);

		if (@$options['contentview'] && !empty($question['ocontent'])) {
			$viewer=qa_load_viewer($question['ocontent'], $question['oformat']);
			
			$fields['content']=$viewer->get_html($question['ocontent'], $question['oformat'], array(
				'blockwordspreg' => @$options['blockwordspreg'],
				'showurllinks' => @$options['showurllinks'],
				'linksnewwindow' => @$options['linksnewwindow'],
			));
		}
		
		if (@$options['whenview'])
			$fields['when']=qa_when_to_html($question['otime'], @$options['fulldatedays']);
		
		if (@$options['whoview']) {
			$isbyuser=qa_post_is_by_user(array('userid' => $question['ouserid'], 'cookieid' => @$question['ocookieid']), $userid, $cookieid);
		
			$fields['who']=qa_who_to_html($isbyuser, $question['ouserid'], $usershtml, @$options['ipview'] ? @$question['oip'] : null, false, @$question['oname']);
	
			if (isset($question['opoints'])) {
				if (@$options['pointsview'])
					$fields['who']['points']=($question['opoints']==1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
						: qa_lang_html_sub_split('main/x_points', qa_html(number_format($question['opoints'])));
						
				if (isset($options['pointstitle']))
					$fields['who']['title']=qa_get_points_title_html($question['opoints'], $options['pointstitle']);
			}

			if (isset($question['olevel']))
				$fields['who']['level']=qa_html(qa_user_level_string($question['olevel']));
		}
		
		unset($fields['flags']);
		if (@$options['flagsview'] && @$question['oflagcount'])
			$fields['flags']=($question['oflagcount']==1) ? qa_lang_html_sub_split('main/1_flag', '1', '1')
				: qa_lang_html_sub_split('main/x_flags', $question['oflagcount']);

		unset($fields['avatar']);
		if (@$options['avatarsize']>0) {
			if (QA_FINAL_EXTERNAL_USERS)
				$fields['avatar']=qa_get_external_avatar_html($question['ouserid'], $options['avatarsize'], false);
			else
				$fields['avatar']=qa_get_user_avatar_html($question['oflags'], $question['oemail'], $question['ohandle'],
					$question['oavatarblobid'], $question['oavatarwidth'], $question['oavatarheight'], $options['avatarsize']);
		}
		
		return $fields;
	}
	
	
	function qa_any_to_q_html_fields($question, $userid, $cookieid, $usershtml, $dummy, $options)
/*
	Based on the elements in $question, return HTML to be passed to theme layer to link
	to the question, or to an associated answer, comment or edit.
*/
	{
		if (isset($question['opostid']))
			$fields=qa_other_to_q_html_fields($question, $userid, $cookieid, $usershtml, null, $options);
		else
			$fields=qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, $options);

		return $fields;
	}
	

	function qa_any_sort_by_date($questions)
/*
	Each element in $questions represents a question and optional associated answer, comment or edit, as retrieved from database.
	Return it sorted by the date appropriate for each element, without removing duplicate references to the same question.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-util-sort.php';
		
		foreach ($questions as $key => $question) // collect information about action referenced by each $question
			$questions[$key]['sort']=-(isset($question['opostid']) ? $question['otime'] : $question['created']);
		
		qa_sort_by($questions, 'sort');
		
		return $questions;
	}
	
	
	function qa_any_sort_and_dedupe($questions)
/*
	Each element in $questions represents a question and optional associated answer, comment or edit, as retrieved from database.
	Return it sorted by the date appropriate for each element, and keep only the first item related to each question.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-util-sort.php';
		
		foreach ($questions as $key => $question) { // collect information about action referenced by each $question
			if (isset($question['opostid'])) {
				$questions[$key]['_time']=$question['otime'];
				$questions[$key]['_type']=$question['obasetype'];
				$questions[$key]['_userid']=@$question['ouserid'];
			} else {
				$questions[$key]['_time']=$question['created'];
				$questions[$key]['_type']='Q';
				$questions[$key]['_userid']=$question['userid'];
			}

			$questions[$key]['sort']=-$questions[$key]['_time'];
		}
		
		qa_sort_by($questions, 'sort');
		
		$keepquestions=array(); // now remove duplicate references to same question
		foreach ($questions as $question) { // going in order from most recent to oldest
			$laterquestion=@$keepquestions[$question['postid']];
			
			if (
				(!isset($laterquestion)) // keep this reference if there is no more recent one
			|| // or ...
				(
					(@$laterquestion['oupdatetype']) && // the more recent reference was an edit
					(!@$question['oupdatetype']) && // this is not an edit
					($laterquestion['_type']==$question['_type']) && // the same part (Q/A/C) is referenced here 
					($laterquestion['_userid']==$question['_userid']) && // the same user made the later edit
					(abs($laterquestion['_time']-$question['_time'])<300) // the edit was within 5 minutes of creation
				)
			|| // or ...
				(
					(@$question['opersonal']) && // this question (in an update list) is personal to the user
					(!@$laterquestion['opersonal']) && // the other one was not personal
					(abs($laterquestion['_time']-$question['_time'])<300) // the two events were within 5 minutes of each other
				)
			)
				$keepquestions[$question['postid']]=$question;
		}
				
		return $keepquestions;
	}

	
	function qa_any_get_userids_handles($questions)
/*
	Each element in $questions represents a question and optional associated answer, comment or edit, as retrieved from database.
	Return an array of elements (userid,handle) for the appropriate user for each element.
*/
	{
		$userids_handles=array();
		
		foreach ($questions as $question)
			if (isset($question['opostid']))
				$userids_handles[]=array(
					'userid' => @$question['ouserid'],
					'handle' => @$question['ohandle'],
				);
			
			else
				$userids_handles[]=array(
					'userid' => @$question['userid'],
					'handle' => @$question['handle'],
				);
			
		return $userids_handles;
	}
	
	
	function qa_html_convert_urls($html, $newwindow=false)
/*
	Return $html with any URLs converted into links (with nofollow and in a new window if $newwindow)
	URL regular expressions can get crazy: http://internet.ls-la.net/folklore/url-regexpr.html
	So this is something quick and dirty that should do the trick in most cases
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return substr(preg_replace('/([^A-Za-z0-9])((http|https|ftp):\/\/([^\s&<>\(\)\[\]"\'\.])+\.([^\s&<>\(\)\[\]"\']|&amp;)+)/i', '\1<a href="\2" rel="nofollow"'.($newwindow ? ' target="_blank"' : '').'>\2</a>', ' '.$html.' '), 1, -1);
	}

	
	function qa_url_to_html_link($url, $newwindow=false)
/*
	Return HTML representation of $url (if it appears to be an URL), linked with nofollow and in a new window if $newwindow
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if (is_numeric(strpos($url, '.'))) {
			$linkurl=$url;
			if (!is_numeric(strpos($linkurl, ':/')))
				$linkurl='http://'.$linkurl;
				
			return '<a href="'.qa_html($linkurl).'" rel="nofollow"'.($newwindow ? ' target="_blank"' : '').'>'.qa_html($url).'</a>';
		
		} else
			return qa_html($url);
	}

	
	function qa_insert_login_links($htmlmessage, $topage=null, $params=null)
/*
	Return $htmlmessage with ^1...^6 substituted for links to log in or register or confirm email and come back to $topage with $params
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		$userlinks=qa_get_login_links(qa_path_to_root(), isset($topage) ? qa_path($topage, $params, '') : null);
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => empty($userlinks['login']) ? '' : '<a href="'.qa_html($userlinks['login']).'">',
				'^2' => empty($userlinks['login']) ? '' : '</a>',
				'^3' => empty($userlinks['register']) ? '' : '<a href="'.qa_html($userlinks['register']).'">',
				'^4' => empty($userlinks['register']) ? '' : '</a>',
				'^5' => empty($userlinks['confirm']) ? '' : '<a href="'.qa_html($userlinks['confirm']).'">',
				'^6' => empty($userlinks['confirm']) ? '' : '</a>',
			)
		);
	}

	
	function qa_html_page_links($request, $start, $pagesize, $count, $prevnext, $params=array(), $hasmore=false, $anchor=null)
/*
	Return structure to pass through to theme layer to show linked page numbers for $request.
	Q2A uses offset-based paging, i.e. pages are referenced in the URL by a 'start' parameter.
	$start is current offset, there are $pagesize items per page and $count items in total
	(unless $hasmore is true in which case there are at least $count items).
	Show links to $prevnext pages before and after this one and include $params in the URLs.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$thispage=1+floor($start/$pagesize);
		$lastpage=ceil(min($count, 1+QA_MAX_LIMIT_START)/$pagesize);
		
		if (($thispage>1) || ($lastpage>$thispage)) {
			$links=array('label' => qa_lang_html('main/page_label'), 'items' => array());
			
			$keypages[1]=true;
			
			for ($page=max(2, min($thispage, $lastpage)-$prevnext); $page<=min($thispage+$prevnext, $lastpage); $page++)
				$keypages[$page]=true;
				
			$keypages[$lastpage]=true;
			
			if ($thispage>1)
				$links['items'][]=array(
					'type' => 'prev',
					'label' => qa_lang_html('main/page_prev'),
					'page' => $thispage-1,
					'ellipsis' => false,
				);
				
			foreach (array_keys($keypages) as $page)
				$links['items'][]=array(
					'type' => ($page==$thispage) ? 'this' : 'jump',
					'label' => $page,
					'page' => $page,
					'ellipsis' => (($page<$lastpage) || $hasmore) && (!isset($keypages[$page+1])),
				);
				
			if ($thispage<$lastpage)
				$links['items'][]=array(
					'type' => 'next',
					'label' => qa_lang_html('main/page_next'),
					'page' => $thispage+1,
					'ellipsis' => false,
				);
				
			foreach ($links['items'] as $key => $link)
				if ($link['page']!=$thispage) {
					$params['start']=$pagesize*($link['page']-1);
					$links['items'][$key]['url']=qa_path_html($request, $params, null, null, $anchor);
				}
				
		} else
			$links=null;
		
		return $links;
	}

	
	function qa_html_suggest_qs_tags($usingtags=false, $categoryrequest=null)
/*
	Return HTML that suggests browsing all questions (in the category specified by $categoryrequest, if
	it's not null) and also popular tags if $usingtags is true
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$hascategory=strlen($categoryrequest);
		
		$htmlmessage=$hascategory ? qa_lang_html('main/suggest_category_qs') :
			($usingtags ? qa_lang_html('main/suggest_qs_tags') : qa_lang_html('main/suggest_qs'));
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => '<a href="'.qa_path_html('questions'.($hascategory ? ('/'.$categoryrequest) : '')).'">',
				'^2' => '</a>',
				'^3' => '<a href="'.qa_path_html('tags').'">',
				'^4' => '</a>',
			)
		);
	}

	
	function qa_html_suggest_ask($categoryid=null)
/*
	Return HTML that suggest getting things started by asking a question, in $categoryid if not null
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$htmlmessage=qa_lang_html('main/suggest_ask');
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => '<a href="'.qa_path_html('ask', strlen($categoryid) ? array('cat' => $categoryid) : null).'">',
				'^2' => '</a>',
			)
		);
	}
	
	
	function qa_category_navigation($categories, $selectedid=null, $pathprefix='', $showqcount=true, $pathparams=null)
/*
	Return the navigation structure for the category hierarchical menu, with $selectedid selected,
	and links beginning with $pathprefix, and showing question counts if $showqcount
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$parentcategories=array();
		
		foreach ($categories as $category)
			$parentcategories[$category['parentid']][]=$category;
			
		$selecteds=qa_category_path($categories, $selectedid);
		$favoritemap=qa_get_favorite_non_qs_map();
			
		return qa_category_navigation_sub($parentcategories, null, $selecteds, $pathprefix, $showqcount, $pathparams, $favoritemap);
	}
	
	
	function qa_category_navigation_sub($parentcategories, $parentid, $selecteds, $pathprefix, $showqcount, $pathparams, $favoritemap=null)
/*
	Recursion function used by qa_category_navigation(...) to build hierarchical category menu.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$navigation=array();
		
		if (!isset($parentid))
			$navigation['all']=array(
				'url' => qa_path_html($pathprefix, $pathparams),
				'label' => qa_lang_html('main/all_categories'),
				'selected' => !count($selecteds),
				'categoryid' => null,
			);
		
		if (isset($parentcategories[$parentid]))
			foreach ($parentcategories[$parentid] as $category)
				$navigation[qa_html($category['tags'])]=array(
					'url' => qa_path_html($pathprefix.$category['tags'], $pathparams),
					'label' => qa_html($category['title']),
					'popup' => qa_html(@$category['content']),
					'selected' => isset($selecteds[$category['categoryid']]),
					'note' => $showqcount ? ('('.qa_html(number_format($category['qcount'])).')') : null,
					'subnav' => qa_category_navigation_sub($parentcategories, $category['categoryid'], $selecteds,
						$pathprefix.$category['tags'].'/', $showqcount, $pathparams, $favoritemap),
					'categoryid' => $category['categoryid'],
					'favorited' => @$favoritemap['category'][$category['backpath']],
				);
		
		return $navigation;
	}
	
	
	function qa_users_sub_navigation()
/*
	Return the sub navigation structure for user listing pages
*/
	{
		if ((!QA_FINAL_EXTERNAL_USERS) && (qa_get_logged_in_level()>=QA_USER_LEVEL_MODERATOR)) {
			return array(
				'users$' => array(
					'url' => qa_path_html('users'),
					'label' => qa_lang_html('main/highest_users'),
				),
	
				'users/special' => array(
					'label' => qa_lang('users/special_users'),
					'url' => qa_path_html('users/special'),
				),
	
				'users/blocked' => array(
					'label' => qa_lang('users/blocked_users'),
					'url' => qa_path_html('users/blocked'),
				),
			);
			
		} else
			return null;
	}
	
	
	function qa_user_sub_navigation($handle, $selected, $ismyuser=false)
/*
	Return the sub navigation structure for navigating between the different pages relating to a user
*/
	{
		$navigation=array(
			'profile' => array(
				'label' => qa_lang_html_sub('profile/user_x', qa_html($handle)),
				'url' => qa_path_html('user/'.$handle),
			),
			
			'account' => array(
				'label' => qa_lang_html('misc/nav_my_details'),
				'url' => qa_path_html('account'),
			),
			
			'favorites' => array(
				'label' => qa_lang_html('misc/nav_my_favorites'),
				'url' => qa_path_html('favorites'),
			),
			
			'wall' => array(
				'label' => qa_lang_html('misc/nav_user_wall'),
				'url' => qa_path_html('user/'.$handle.'/wall'),
			),
			
			'activity' => array(
				'label' => qa_lang_html('misc/nav_user_activity'),
				'url' => qa_path_html('user/'.$handle.'/activity'),
			),
			
			'questions' => array(
				'label' => qa_lang_html('misc/nav_user_qs'),
				'url' => qa_path_html('user/'.$handle.'/questions'),
			),
			
			'answers' => array(
				'label' => qa_lang_html('misc/nav_user_as'),
				'url' => qa_path_html('user/'.$handle.'/answers'),
			),
		);
		
		if (isset($navigation[$selected]))
			$navigation[$selected]['selected']=true;
			
		if (QA_FINAL_EXTERNAL_USERS || !qa_opt('allow_user_walls'))
			unset($navigation['wall']);
			
		if (QA_FINAL_EXTERNAL_USERS || !$ismyuser)
			unset($navigation['account']);
			
		if (!$ismyuser)
			unset($navigation['favorites']);
		
		return $navigation;
	}
	
	
	function qa_account_sub_navigation()
/*
	Return the sub navigation structure for user account pages.
	RETIRED FROM USE IN Q2A 1.6.3 BUT RETAINED FOR BACKWARDS COMPATIBILITY
*/
	{
		return array(
			'account' => array(
				'label' => qa_lang_html('misc/nav_my_details'),
				'url' => qa_path_html('account'),
			),
			
			'favorites' => array(
				'label' => qa_lang_html('misc/nav_my_favorites'),
				'url' => qa_path_html('favorites'),
			),
		);
	}
	
	
	function qa_custom_page_url($page)
/*
	Return the url for $page retrieved from the database
*/
	{
		return ($page['flags'] & QA_PAGE_FLAGS_EXTERNAL)
			? (is_numeric(strpos($page['tags'], '://')) ? $page['tags'] : qa_path_to_root().$page['tags'])
			: qa_path($page['tags']);
	}
	
	
	function qa_navigation_add_page(&$navigation, $page)
/*
	Add an element to the $navigation array corresponding to $page retrieved from the database
*/
	{
		if (
			(!qa_permit_value_error($page['permit'], qa_get_logged_in_userid(), qa_get_logged_in_level(), qa_get_logged_in_flags())) || !isset($page['permit'])
		) {
			$url=qa_custom_page_url($page);
			
			$navigation[($page['flags'] & QA_PAGE_FLAGS_EXTERNAL) ? ('custom-'.$page['pageid']) : ($page['tags'].'$')]=array(
				'url' => qa_html($url),
				'label' => qa_html($page['title']),
				'opposite' => ($page['nav']=='O'),
				'target' => ($page['flags'] & QA_PAGE_FLAGS_NEW_WINDOW) ? '_blank' : null,
				'selected' => ($page['flags'] & QA_PAGE_FLAGS_EXTERNAL) && ( ($url==qa_path(qa_request())) || ($url==qa_self_html()) ),
			);
		}
	}


	function qa_match_to_min_score($match)
/*
	Convert an admin option for matching into a threshold for the score given by database search
*/
	{
		return 10-2*$match;
	}

	
	function qa_set_display_rules(&$qa_content, $effects)
/*
	For each [target] => [source] in $effects, set up $qa_content so that the visibility of the DOM element ID
	target is equal to the checked state or boolean-casted value of the DOM element ID source. Each source can
	also combine multiple DOM IDs using JavaScript(=PHP) operators. This is twisted but rather convenient.
*/
	{
		$function='qa_display_rule_'.count(@$qa_content['script_lines']);
		
		$keysourceids=array();
		
		foreach ($effects as $target => $sources)
			if (preg_match_all('/[A-Za-z_][A-Za-z0-9_]*/', $sources, $matches)) // element names must be legal JS variable names
				foreach ($matches[0] as $element)
					$keysourceids[$element]=true;
		
		$funcscript=array("function ".$function."(first) {"); // build the Javascripts
		$loadscript=array();
		
		foreach ($keysourceids as $key => $dummy) {
			$funcscript[]="\tvar e=document.getElementById(".qa_js($key).");";
			$funcscript[]="\tvar ".$key."=e && (e.checked || (e.options && e.options[e.selectedIndex].value));";
			$loadscript[]="var e=document.getElementById(".qa_js($key).");";
			$loadscript[]="if (e) {";
			$loadscript[]="\t".$key."_oldonclick=e.onclick;";
			$loadscript[]="\te.onclick=function() {";
			$loadscript[]="\t\t".$function."(false);";
			$loadscript[]="\t\tif (typeof ".$key."_oldonclick=='function')";
			$loadscript[]="\t\t\t".$key."_oldonclick();";
			$loadscript[]="\t};";
			$loadscript[]="}";
		}
			
		foreach ($effects as $target => $sources) {
			$funcscript[]="\tvar e=document.getElementById(".qa_js($target).");";
			$funcscript[]="\tif (e) { var d=(".$sources."); if (first || (e.nodeName=='SPAN')) { e.style.display=d ? '' : 'none'; } else { if (d) { $(e).fadeIn(); } else { $(e).fadeOut(); } } }";
		}
		
		$funcscript[]="}";
		$loadscript[]=$function."(true);";
		
		$qa_content['script_lines'][]=$funcscript;
		$qa_content['script_onloads'][]=$loadscript;
	}

	
	function qa_set_up_tag_field(&$qa_content, &$field, $fieldname, $tags, $exampletags, $completetags, $maxtags)
/*
	Set up $qa_content and $field (with HTML name $fieldname) for tag auto-completion, where
	$exampletags are suggestions and $completetags are simply the most popular ones. Show up to $maxtags.
*/
	{
		$template='<a href="#" class="qa-tag-link" onclick="return qa_tag_click(this);">^</a>';

		$qa_content['script_rel'][]='qa-content/qa-ask.js?'.QA_VERSION;
		$qa_content['script_var']['qa_tag_template']=$template;
		$qa_content['script_var']['qa_tag_onlycomma']=(int)qa_opt('tag_separator_comma');
		$qa_content['script_var']['qa_tags_examples']=qa_html(implode(',', $exampletags));
		$qa_content['script_var']['qa_tags_complete']=qa_html(implode(',', $completetags));
		$qa_content['script_var']['qa_tags_max']=(int)$maxtags;
		
		$separatorcomma=qa_opt('tag_separator_comma');
		
		$field['label']=qa_lang_html($separatorcomma ? 'question/q_tags_comma_label' : 'question/q_tags_label');
		$field['value']=qa_html(implode($separatorcomma ? ', ' : ' ', $tags));
		$field['tags']='name="'.$fieldname.'" id="tags" autocomplete="off" onkeyup="qa_tag_hints();" onmouseup="qa_tag_hints();"';
		
		$sdn=' style="display:none;"';
		
		$field['note']=
			'<span id="tag_examples_title"'.(count($exampletags) ? '' : $sdn).'>'.qa_lang_html('question/example_tags').'</span>'.
			'<span id="tag_complete_title"'.$sdn.'>'.qa_lang_html('question/matching_tags').'</span><span id="tag_hints">';

		foreach ($exampletags as $tag)
			$field['note'].=str_replace('^', qa_html($tag), $template).' ';

		$field['note'].='</span>';
		$field['note_force']=true;
	}
	
	
	function qa_get_tags_field_value($fieldname)
/*
	Get a list of user-entered tags submitted from a field that was created with qa_set_up_tag_field(...)
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$text=qa_post_text($fieldname);
		
		if (qa_opt('tag_separator_comma'))
			return array_unique(preg_split('/\s*,\s*/', trim(qa_strtolower(strtr($text, '/', ' '))), -1, PREG_SPLIT_NO_EMPTY));
		else
			return array_unique(qa_string_to_words($text, true, false, false, false));
	}
	
	
	function qa_set_up_category_field(&$qa_content, &$field, $fieldname, $navcategories, $categoryid, $allownone, $allownosub, $maxdepth=null, $excludecategoryid=null)
/*
	Set up $qa_content and $field (with HTML name $fieldname) for hierarchical category navigation, with the initial value
	set to $categoryid (and $navcategories retrieved for $categoryid using qa_db_category_nav_selectspec(...)).
	If $allownone is true, it will allow selection of no category. If $allownosub is true, it will allow a category to be
	selected without selecting a subcategory within. Set $maxdepth to the maximum depth of category that can be selected
	(or null for no maximum) and $excludecategoryid to a category that should not be included.
*/
	{
		$pathcategories=qa_category_path($navcategories, $categoryid);

		$startpath='';
		foreach ($pathcategories as $category)
			$startpath.='/'.$category['categoryid'];
		
		if (!isset($maxdepth))
			$maxdepth=QA_CATEGORY_DEPTH;
		$maxdepth=min(QA_CATEGORY_DEPTH, $maxdepth);

		$qa_content['script_rel'][]='qa-content/qa-ask.js?'.QA_VERSION;
		$qa_content['script_onloads'][]='qa_category_select('.qa_js($fieldname).', '.qa_js($startpath).');';
		
		$qa_content['script_var']['qa_cat_exclude']=$excludecategoryid;	
		$qa_content['script_var']['qa_cat_allownone']=(int)$allownone;
		$qa_content['script_var']['qa_cat_allownosub']=(int)$allownosub;
		$qa_content['script_var']['qa_cat_maxdepth']=$maxdepth;

		$field['type']='select';
		$field['tags']='name="'.$fieldname.'_0" id="'.$fieldname.'_0" onchange="qa_category_select('.qa_js($fieldname).');"';
		$field['options']=array();
		
		// create the menu that will be shown if Javascript is disabled
		
		if ($allownone)
			$field['options']['']=qa_lang_html('main/no_category'); // this is also copied to first menu created by Javascript
		
		$keycategoryids=array();
		
		if ($allownosub) {
			$category=@$navcategories[$categoryid];
			$upcategory=$category;

			while (true) { // first get supercategories
				$upcategory=@$navcategories[$upcategory['parentid']];
				
				if (!isset($upcategory))
					break;
				
				$keycategoryids[$upcategory['categoryid']]=true;
			}
			
			$keycategoryids=array_reverse($keycategoryids, true);

			$depth=count($keycategoryids); // number of levels above
			
			if (isset($category)) {
				$depth++; // to count category itself
				
				foreach ($navcategories as $navcategory) // now get siblings and self
					if (!strcmp($navcategory['parentid'], $category['parentid']))
						$keycategoryids[$navcategory['categoryid']]=true;
			}
	
			if ($depth<$maxdepth)
				foreach ($navcategories as $navcategory) // now get children, if not too deep
					if (!strcmp($navcategory['parentid'], $categoryid))
						$keycategoryids[$navcategory['categoryid']]=true;

		} else {
			$haschildren=false;
			
			foreach ($navcategories as $navcategory) // check if it has any children
				if (!strcmp($navcategory['parentid'], $categoryid))
					$haschildren=true;
			
			if (!$haschildren)
				$keycategoryids[$categoryid]=true; // show this category if it has no children
		}
		
		foreach ($keycategoryids as $keycategoryid => $dummy)
			if (strcmp($keycategoryid, $excludecategoryid))
				$field['options'][$keycategoryid]=qa_category_path_html($navcategories, $keycategoryid);
			
		$field['value']=@$field['options'][$categoryid];
		$field['note']='<div id="'.$fieldname.'_note"><noscript style="color:red;">'.qa_lang_html('question/category_js_note').'</noscript></div>';
	}
	
	
	function qa_get_category_field_value($fieldname)
/*
	Get the user-entered category id submitted from a field that was created with qa_set_up_category_field(...)
*/
	{
		for ($level=QA_CATEGORY_DEPTH; $level>=1; $level--) {
			$levelid=qa_post_text($fieldname.'_'.$level);
			if (strlen($levelid))
				return $levelid;
		}
		
		if (!isset($levelid)) { // no Javascript-generated menu was present so take original menu
			$levelid=qa_post_text($fieldname.'_0');
			if (strlen($levelid))
				return $levelid;
		}
		
		return null;
	}
	
	
	function qa_set_up_name_field(&$qa_content, &$fields, $inname, $fieldprefix='')
/*
	Set up $qa_content and add to $fields to allow the user to enter their name for a post if they are not logged in
	$inname is from previous submission/validation. Pass $fieldprefix to add a prefix to the form field name used.
*/
	{
		$fields['name']=array(
			'label' => qa_lang_html('question/anon_name_label'),
			'tags' => 'name="'.$fieldprefix.'name"',
			'value' => qa_html($inname),
		);
	}

	
	function qa_set_up_notify_fields(&$qa_content, &$fields, $basetype, $login_email, $innotify, $inemail, $errors_email, $fieldprefix='')
/*
	Set up $qa_content and add to $fields to allow user to set if they want to be notified regarding their post.
	$basetype is 'Q', 'A' or 'C' for question, answer or comment. $login_email is the email of logged in user,
	or null if this is an anonymous post. $innotify, $inemail and $errors_email are from previous submission/validation.
	Pass $fieldprefix to add a prefix to the form field names and IDs used.
*/
	{
		$fields['notify']=array(
			'tags' => 'name="'.$fieldprefix.'notify"',
			'type' => 'checkbox',
			'value' => qa_html($innotify),
		);

		switch ($basetype) {
			case 'Q':
				$labelaskemail=qa_lang_html('question/q_notify_email');
				$labelonly=qa_lang_html('question/q_notify_label');
				$labelgotemail=qa_lang_html('question/q_notify_x_label');
				break;
				
			case 'A':
				$labelaskemail=qa_lang_html('question/a_notify_email');
				$labelonly=qa_lang_html('question/a_notify_label');
				$labelgotemail=qa_lang_html('question/a_notify_x_label');
				break;
				
			case 'C':
				$labelaskemail=qa_lang_html('question/c_notify_email');
				$labelonly=qa_lang_html('question/c_notify_label');
				$labelgotemail=qa_lang_html('question/c_notify_x_label');
				break;
		}
			
		if (empty($login_email)) {
			$fields['notify']['label']=
				'<span id="'.$fieldprefix.'email_shown">'.$labelaskemail.'</span>'.
				'<span id="'.$fieldprefix.'email_hidden" style="display:none;">'.$labelonly.'</span>';
			
			$fields['notify']['tags'].=' id="'.$fieldprefix.'notify" onclick="if (document.getElementById(\''.$fieldprefix.'notify\').checked) document.getElementById(\''.$fieldprefix.'email\').focus();"';
			$fields['notify']['tight']=true;
			
			$fields['email']=array(
				'id' => $fieldprefix.'email_display',
				'tags' => 'name="'.$fieldprefix.'email" id="'.$fieldprefix.'email"',
				'value' => qa_html($inemail),
				'note' => qa_lang_html('question/notify_email_note'),
				'error' => qa_html($errors_email),
			);
			
			qa_set_display_rules($qa_content, array(
				$fieldprefix.'email_display' => $fieldprefix.'notify',
				$fieldprefix.'email_shown' => $fieldprefix.'notify',
				$fieldprefix.'email_hidden' => '!'.$fieldprefix.'notify',
			));
		
		} else {
			$fields['notify']['label']=str_replace('^', qa_html($login_email), $labelgotemail);
		}
	}

	
	function qa_get_site_theme()
/*
	Return the theme that should be used for displaying the page
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return qa_opt(qa_is_mobile_probably() ? 'site_theme_mobile' : 'site_theme');
	}
	
	
	function qa_load_theme_class($theme, $template, $content, $request)
/*
	Return the initialized class for $theme (or the default if it's gone), passing $template, $content and $request.
	Also applies any registered plugin layers.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_layers;
		
	//	First load the default class
		
		require_once QA_INCLUDE_DIR.'qa-theme-base.php';
		
		$classname='qa_html_theme_base';
		
	//	Then load the selected theme if valid, otherwise load the Classic theme
	
		if (!file_exists(QA_THEME_DIR.$theme.'/qa-styles.css'))
			$theme='Classic';

		$themeroothtml=qa_html(qa_path_to_root().'qa-theme/'.$theme.'/');
		
		if (file_exists(QA_THEME_DIR.$theme.'/qa-theme.php')) {
			require_once QA_THEME_DIR.$theme.'/qa-theme.php';
	
			if (class_exists('qa_html_theme'))
				$classname='qa_html_theme';
		}
	
	//	Create the list of layers to load
	
		$loadlayers=$qa_layers;
		
		if (!qa_user_maximum_permit_error('permit_view_voters_flaggers'))
			$loadlayers[]=array(
				'directory' => QA_INCLUDE_DIR,
				'include' => 'qa-layer-voters-flaggers.php',
				'urltoroot' => null,
			);

	//	Then load any theme layers using some class-munging magic (substitute class names)
	
		$layerindex=0;
		
		foreach ($loadlayers as $layer) {
			$filename=$layer['directory'].$layer['include'];
			$layerphp=file_get_contents($filename);
			
			if (strlen($layerphp)) {
				$newclassname='qa_layer_'.(++$layerindex).'_from_'.preg_replace('/[^A-Za-z0-9_]+/', '_', basename($layer['include']));
					// include file name in layer class name to make debugging easier if there is an error
					
				if (preg_match('/\s+class\s+qa_html_theme_layer\s+extends\s+qa_html_theme_base\s+/im', $layerphp)!=1)
					qa_fatal_error('Class for layer must be declared as "class qa_html_theme_layer extends qa_html_theme_base" in '.$layer['directory'].$layer['include']);
				
				$searchwordreplace=array(
					'qa_html_theme_layer' => $newclassname,
					'qa_html_theme_base' => $classname,
					'QA_HTML_THEME_LAYER_DIRECTORY' => "'".$layer['directory']."'",
					'QA_HTML_THEME_LAYER_URLTOROOT' => "'".qa_path_to_root().$layer['urltoroot']."'",
				);
				
				foreach ($searchwordreplace as $searchword => $replace)
					if (preg_match_all('/\W('.preg_quote($searchword, '/').')\W/im', $layerphp, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
						$searchmatches=array_reverse($matches[1]); // don't use preg_replace due to complication of escaping replacement phrase
						
						foreach ($searchmatches as $searchmatch)
							$layerphp=substr_replace($layerphp, $replace, $searchmatch[1], strlen($searchmatch[0]));
					}
				
			//	echo '<pre style="text-align:left;">'.htmlspecialchars($layerphp).'</pre>'; // to debug munged code
				
				qa_eval_from_file($layerphp, $filename);
				
				$classname=$newclassname;
			}
		}
		
	//	Finally, instantiate the object
			
		$themeclass=new $classname($template, $content, $themeroothtml, $request);
		
		return $themeclass;
	}
	
	
	function qa_load_editor($content, $format, &$editorname)
/*
	Return an instantiation of the appropriate editor module class, given $content in $format
	Pass the preferred module name in $editorname, on return it will contain the name of the module used.
*/
	{
		$maxeditor=qa_load_module('editor', $editorname); // take preferred one first
		
		if (isset($maxeditor) && method_exists($maxeditor, 'calc_quality')) {
			$maxquality=$maxeditor->calc_quality($content, $format);		
			if ($maxquality>=0.5)
				return $maxeditor;

		} else
			$maxquality=0;
		
		$editormodules=qa_load_modules_with('editor', 'calc_quality');
		foreach ($editormodules as $tryname => $tryeditor) {
			$tryquality=$tryeditor->calc_quality($content, $format);
			
			if ($tryquality>$maxquality) {
				$maxeditor=$tryeditor;
				$maxquality=$tryquality;
				$editorname=$tryname;
			}
		}
				
		return $maxeditor;
	}
	
	
	function qa_editor_load_field($editor, &$qa_content, $content, $format, $fieldname, $rows, $focusnow=false, $loadnow=true)
/*
	Return a form field from the $editor module while making necessary modifications to $qa_content. The parameters
	$content, $format, $fieldname, $rows and $focusnow are passed through to the module's get_field() method. ($focusnow
	is deprecated as a parameter to get_field() but it's still passed through for old editor modules.) Based on
	$focusnow and $loadnow, also add the editor's load and/or focus scripts to $qa_content's onload handlers.
*/
	{
		if (!isset($editor))
			qa_fatal_error('No editor found for format: '.$format);
		
		$field=$editor->get_field($qa_content, $content, $format, $fieldname, $rows, $focusnow);
		
		$onloads=array();

		if ($loadnow && method_exists($editor, 'load_script'))
			$onloads[]=$editor->load_script($fieldname);
		
		if ($focusnow && method_exists($editor, 'focus_script'))
			$onloads[]=$editor->focus_script($fieldname);
			
		if (count($onloads))
			$qa_content['script_onloads'][]=$onloads;
			
		return $field;
	}
	
	
	function qa_load_viewer($content, $format)
/*
	Return an instantiation of the appropriate viewer module class, given $content in $format
*/
	{
		$maxviewer=null;
		$maxquality=0;
		
		$viewermodules=qa_load_modules_with('viewer', 'calc_quality');
		
		foreach ($viewermodules as $tryviewer) {
			$tryquality=$tryviewer->calc_quality($content, $format);
			
			if ($tryquality>$maxquality) {
				$maxviewer=$tryviewer;
				$maxquality=$tryquality;
			}
		}
		
		return $maxviewer;
	}
	
	
	function qa_viewer_text($content, $format, $options=array())
/*
	Return the plain text rendering of $content in $format, passing $options to the appropriate module
*/
	{
		$viewer=qa_load_viewer($content, $format);
		return $viewer->get_text($content, $format, $options);
	}
	
	
	function qa_viewer_html($content, $format, $options=array())
/*
	Return the HTML rendering of $content in $format, passing $options to the appropriate module
*/
	{
		$viewer=qa_load_viewer($content, $format);
		return $viewer->get_html($content, $format, $options);
	}
	
	
	function qa_get_post_content($editorfield, $contentfield, &$ineditor, &$incontent, &$informat, &$intext)
/*
	Retrieve the POST from an editor module's HTML field named $contentfield, where the editor's name was in HTML field $editorfield
	Assigns the module's output to $incontent and $informat, editor's name in $ineditor, text rendering of content in $intext
*/
	{
		$ineditor=qa_post_text($editorfield);

		$editor=qa_load_module('editor', $ineditor);
		$readdata=$editor->read_post($contentfield);
		$incontent=$readdata['content'];
		$informat=$readdata['format'];
		$intext=qa_viewer_text($incontent, $informat);
	}
	
	
	function qa_update_post_text(&$fields, $oldfields)
/*
	Check if any of the 'content', 'format' or 'text' elements have changed between $oldfields and $fields
	If so, recalculate $fields['text'] based on $fields['content'] and $fields['format']
*/
	{
		if (
			strcmp($oldfields['content'], $fields['content']) ||
			strcmp($oldfields['format'], $fields['format']) ||
			strcmp($oldfields['text'], $fields['text'])
		)
			$fields['text']=qa_viewer_text($fields['content'], $fields['format']);
	}
	
	
	function qa_get_avatar_blob_html($blobid, $width, $height, $size, $padding=false)
/*
	Return the <img...> HTML to display avatar $blobid whose stored size is $width and $height
	Constrain the image to $size (width AND height) and pad it to that size if $padding is true
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-util-image.php';
		
		if (strlen($blobid) && ($size>0)) {
			qa_image_constrain($width, $height, $size);
			
			$html='<img src="'.qa_path_html('image', array('qa_blobid' => $blobid, 'qa_size' => $size), null, QA_URL_FORMAT_PARAMS).
				'"'.(($width && $height) ? (' width="'.$width.'" height="'.$height.'"') : '').' class="qa-avatar-image" alt=""/>';
				
			if ($padding && $width && $height) {
				$padleft=floor(($size-$width)/2);
				$padright=$size-$width-$padleft;
				$padtop=floor(($size-$height)/2);
				$padbottom=$size-$height-$padtop;
				$html='<span style="display:inline-block; padding:'.$padtop.'px '.$padright.'px '.$padbottom.'px '.$padleft.'px;">'.$html.'</span>';
			}
		
			return $html;

		} else
			return null;
	}
	
	
	function qa_get_gravatar_html($email, $size)
/*
	Return the <img...> HTML to display the Gravatar for $email, constrained to $size
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if ($size>0)
			return '<img src="'.(qa_is_https_probably() ? 'https' : 'http').
				'://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?s='.(int)$size.
				'" width="'.(int)$size.'" height="'.(int)$size.'" class="qa-avatar-image" alt=""/>';
		else
			return null;
	}
	
	
	function qa_get_points_title_html($userpoints, $pointstitle)
/*
	Retrieve the appropriate user title from $pointstitle for a user with $userpoints points, or null if none
*/
	{
		foreach ($pointstitle as $points => $title)
			if ($userpoints>=$points)
				return $title;
				
		return null;
	}
	

	function qa_notice_form($noticeid, $content, $rawnotice=null)
/*
	Return an form to add to the $qa_content['notices'] array for displaying a user notice with id $noticeid
	and $content. Pass the raw database information for the notice in $rawnotice.
*/
	{
		$elementid='notice_'.$noticeid;
		
		return array(
			'id' => qa_html($elementid),
			'raw' => $rawnotice,
			'form_tags' => 'method="post" action="'.qa_self_html().'"',
			'form_hidden' => array('code' => qa_get_form_security_code('notice-'.$noticeid)),
			'close_tags' => 'name="'.qa_html($elementid).'" onclick="return qa_notice_click(this);"',
			'content' => $content,
		);
	}
	
	
	function qa_favorite_form($entitytype, $entityid, $favorite, $title)
/*
	Return a form to set in $qa_content['favorite'] for the favoriting button for entity $entitytype with $entityid.
	Set $favorite to whether the entity is currently a favorite and a description title for the button in $title.
*/
	{
		return array(
			'form_tags' => 'method="post" action="'.qa_self_html().'"',
			'form_hidden' => array('code' => qa_get_form_security_code('favorite-'.$entitytype.'-'.$entityid)),
			'favorite_tags' => 'id="favoriting"',
			($favorite ? 'favorite_remove_tags' : 'favorite_add_tags') =>
				'title="'.qa_html($title).'" name="'.qa_html('favorite_'.$entitytype.'_'.$entityid.'_'.(int)!$favorite).'" onclick="return qa_favorite_click(this);"',
		);
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/