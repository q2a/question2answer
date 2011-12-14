<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-user.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Controller for user profile page


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-updates.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	
	$handle=qa_request_part(1);
	if (!strlen($handle))
		qa_redirect('users');


//	Get the HTML to display for the handle, and if we're using external users, determine the userid 

	if (QA_FINAL_EXTERNAL_USERS) {
		$publictouserid=qa_get_userids_from_public(array($handle));
		$userid=@$publictouserid[$handle];
		
		if (!isset($userid))
			return include QA_INCLUDE_DIR.'qa-page-not-found.php';
		
		$usershtml=qa_get_users_html(array($userid), false, qa_path_to_root(), true);
		$userhtml=@$usershtml[$userid];

	} else
		$userhtml=qa_html($handle);

	
//	Find the user profile and questions and answers for this handle
	
	$loginuserid=qa_get_logged_in_userid();
	$identifier=QA_FINAL_EXTERNAL_USERS ? $userid : $handle;

	@list($useraccount, $userprofile, $userfields, $userpoints, $userrank, $questions, $answerqs, $commentqs, $editqs, $favorite)=
		qa_db_select_with_pending(
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_account_selectspec($handle, false),
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_profile_selectspec($handle, false),
			QA_FINAL_EXTERNAL_USERS ? null : qa_db_userfields_selectspec(),
			qa_db_user_points_selectspec($identifier),
			qa_db_user_rank_selectspec($identifier),
			qa_db_user_recent_qs_selectspec($loginuserid, $identifier, qa_opt_if_loaded('page_size_user_posts')),
			qa_db_user_recent_a_qs_selectspec($loginuserid, $identifier),
			qa_db_user_recent_c_qs_selectspec($loginuserid, $identifier),
			qa_db_user_recent_edit_qs_selectspec($loginuserid, $identifier),
			(isset($loginuserid) && !QA_FINAL_EXTERNAL_USERS) ? qa_db_is_favorite_selectspec($loginuserid, QA_ENTITY_USER, $handle) : null
		);
	

//	Check the user exists and work out what can and can't be set (if not using single sign-on)
	
	$loginlevel=qa_get_logged_in_level();

	if (!QA_FINAL_EXTERNAL_USERS) { // if we're using integrated user management, we can know and show more
		if ((!is_array($userpoints)) && !is_array($useraccount))
			return include QA_INCLUDE_DIR.'qa-page-not-found.php';
	
		$userid=$useraccount['userid'];

		$fieldseditable=false;
		$maxlevelassign=null;
		
		if (
			$loginuserid &&
			($loginuserid!=$userid) &&
			(($loginlevel>=QA_USER_LEVEL_SUPER) || ($loginlevel>$useraccount['level'])) &&
			(!qa_user_permit_error())
		) { // can't change self - or someone on your level (or higher, obviously) unless you're a super admin
		
			if ($loginlevel>=QA_USER_LEVEL_SUPER)
				$maxlevelassign=QA_USER_LEVEL_SUPER;

			elseif ($loginlevel>=QA_USER_LEVEL_ADMIN)
				$maxlevelassign=QA_USER_LEVEL_MODERATOR;

			elseif ($loginlevel>=QA_USER_LEVEL_MODERATOR)
				$maxlevelassign=QA_USER_LEVEL_EXPERT;
				
			if ($loginlevel>=QA_USER_LEVEL_ADMIN)
				$fieldseditable=true;
			
			if (isset($maxlevelassign) && ($useraccount['flags'] & QA_USER_FLAGS_USER_BLOCKED))
				$maxlevelassign=min($maxlevelassign, QA_USER_LEVEL_EDITOR); // if blocked, can't promote too high
		}
		
		$usereditbutton=$fieldseditable || isset($maxlevelassign);
		$userediting=$usereditbutton && (qa_get_state()=='edit');
	}


//	Process edit or save button for user

	if (!QA_FINAL_EXTERNAL_USERS) {
		$reloaduser=false;
		
		if ($usereditbutton) {
			if (qa_clicked('docancel'))
				qa_redirect(qa_request());
			
			elseif (qa_clicked('doedit'))
				qa_redirect(qa_request(), array('state' => 'edit'));
				
			elseif (qa_clicked('dosave')) {
				require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
				require_once QA_INCLUDE_DIR.'qa-db-users.php';
				
				$errors=array();
				
				if (qa_post_text('removeavatar')) {
					qa_db_user_set_flag($userid, QA_USER_FLAGS_SHOW_AVATAR, false);
					qa_db_user_set_flag($userid, QA_USER_FLAGS_SHOW_GRAVATAR, false);

					if (isset($useraccount['avatarblobid'])) {
						require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
						
						qa_db_user_set($userid, 'avatarblobid', null);
						qa_db_user_set($userid, 'avatarwidth', null);
						qa_db_user_set($userid, 'avatarheight', null);
						qa_db_blob_delete($useraccount['avatarblobid']);
					}
				}
				
				if ($fieldseditable) {
					$inemail=qa_post_text('email');
					
					$filterhandle=$handle; // we're not filtering the handle...
					$errors=qa_handle_email_filter($filterhandle, $inemail, $useraccount);
					unset($errors['handle']); // ...and we don't care about any errors in it
					
					if (!isset($errors['email']))
						if ($inemail != $useraccount['email']) {
							qa_db_user_set($userid, 'email', $inemail);
							qa_db_user_set_flag($userid, QA_USER_FLAGS_EMAIL_CONFIRMED, false);
						}
						
					$inprofile=array();
					foreach ($userfields as $userfield)
						$inprofile[$userfield['fieldid']]=qa_post_text('field_'.$userfield['fieldid']);
						
					$filtermodules=qa_load_modules_with('filter', 'filter_profile');
					foreach ($filtermodules as $filtermodule)
						$filtermodule->filter_profile($inprofile, $errors, $useraccount, $userprofile);
				
					foreach ($userfields as $userfield)
						if (!isset($errors[$userfield['fieldid']]))
							qa_db_user_profile_set($userid, $userfield['title'], $inprofile[$userfield['fieldid']]);
					
					if (count($errors))
						$userediting=true;
						
					qa_report_event('u_edit', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), array(
						'userid' => $userid,
						'handle' => $useraccount['handle'],
					));
				}
	
				if (isset($maxlevelassign)) {
					$inlevel=min($maxlevelassign, (int)qa_post_text('level')); // constrain based on maximum permitted to prevent simple browser-based attack
					
					if ($inlevel != $useraccount['level']) {
						qa_db_user_set($userid, 'level', $inlevel);

						qa_report_event('u_level', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), array(
							'userid' => $userid,
							'handle' => $useraccount['handle'],
							'level' => $inlevel,
							'oldlevel' => $useraccount['level'],
						));
					}
				}
						
				
				if (empty($errors))
					qa_redirect(qa_request());
				
				list($useraccount, $userprofile)=qa_db_select_with_pending(
					qa_db_user_account_selectspec($userid, true),
					qa_db_user_profile_selectspec($userid, true)
				);
			}
		}
		
		if (isset($maxlevelassign) && ($useraccount['level']<QA_USER_LEVEL_MODERATOR)) {
			if (qa_clicked('doblock')) {
				require_once QA_INCLUDE_DIR.'qa-db-users.php';
				
				qa_db_user_set_flag($userid, QA_USER_FLAGS_USER_BLOCKED, true);

				qa_report_event('u_block', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), array(
					'userid' => $userid,
					'handle' => $useraccount['handle'],
				));

				qa_redirect(qa_request());
			}

			if (qa_clicked('dounblock')) {
				require_once QA_INCLUDE_DIR.'qa-db-users.php';
				
				qa_db_user_set_flag($userid, QA_USER_FLAGS_USER_BLOCKED, false);

				qa_report_event('u_unblock', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), array(
					'userid' => $userid,
					'handle' => $useraccount['handle'],
				));

				qa_redirect(qa_request());
			}

			if (qa_clicked('dohideall') && !qa_user_permit_error('permit_hide_show')) {
				require_once QA_INCLUDE_DIR.'qa-db-admin.php';
				require_once QA_INCLUDE_DIR.'qa-app-posts.php';
				
				$postids=qa_db_get_user_visible_postids($userid);
				
				foreach ($postids as $postid)
					qa_post_set_hidden($postid, true, $loginuserid);
					
				qa_redirect(qa_request());
			}
			
			if (qa_clicked('dodelete') && ($loginlevel>=QA_USER_LEVEL_ADMIN)) {
				require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
				
				qa_delete_user($userid);
				
				qa_report_event('u_delete', $loginuserid, qa_get_logged_in_handle(), qa_cookie_get(), array(
					'userid' => $userid,
					'handle' => $useraccount['handle'],
				));
				
				qa_redirect('users');
			}
		}
	}


//	Process bonus setting button

	if ( ($loginlevel>=QA_USER_LEVEL_ADMIN) && qa_clicked('dosetbonus') ) {
		require_once QA_INCLUDE_DIR.'qa-db-points.php';
		
		qa_db_points_set_bonus($userid, (int)qa_post_text('bonus'));
		qa_db_points_update_ifuser($userid, null);
		qa_redirect(qa_request(), null, null, null, 'activity');
	}
	

//	Get information on user references in answers and other stuff need for page

	$pagesize=qa_opt('page_size_user_posts');
	$questions=qa_any_sort_and_dedupe(array_merge($questions, $answerqs, $commentqs, $editqs));
	$questions=array_slice($questions, 0, $pagesize);
	$usershtml=qa_userids_handles_html(qa_any_get_userids_handles($questions));
	$usershtml[$userid]=$userhtml;

	
//	Prepare content for theme
	
	$qa_content=qa_content_prepare(true);
	
	$qa_content['title']=qa_lang_html_sub('profile/user_x', $userhtml);

	if (isset($loginuserid) && !QA_FINAL_EXTERNAL_USERS)
		$qa_content['favorite']=qa_favorite_form(QA_ENTITY_USER, $useraccount['userid'], $favorite,
			qa_lang_sub($favorite ? 'main/remove_x_favorites' : 'users/add_user_x_favorites', $handle));


//	General information about the user, only available if we're using internal user management
	
	if (!QA_FINAL_EXTERNAL_USERS) {
		$qa_content['form_profile']=array(
			'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
			
			'style' => 'wide',
			
			'fields' => array(
				'avatar' => array(
					'type' => 'image',
					'style' => 'tall',
					'label' => '',
					'html' => qa_get_user_avatar_html($useraccount['flags'], $useraccount['email'], $useraccount['handle'],
						$useraccount['avatarblobid'], $useraccount['avatarwidth'], $useraccount['avatarheight'], qa_opt('avatar_profile_size')),
				),
				
				'removeavatar' => null,
				
				'duration' => array(
					'type' => 'static',
					'label' => qa_lang_html('users/member_for'),
					'value' => qa_html(qa_time_to_string(qa_opt('db_time')-$useraccount['created'])),
				),
				
				'level' => array(
					'type' => 'static',
					'label' => qa_lang_html('users/member_type'),
					'tags' => 'NAME="level"',
					'value' => qa_html(qa_user_level_string($useraccount['level'])),
					'note' => (($useraccount['flags'] & QA_USER_FLAGS_USER_BLOCKED) && isset($maxlevelassign)) ? qa_lang_html('users/user_blocked') : '',
				),
			),
		);
		
		if (empty($qa_content['form_profile']['fields']['avatar']['html']))
			unset($qa_content['form_profile']['fields']['avatar']);
		
	
	//	Private message form
	
		if ( qa_opt('allow_private_messages') && isset($loginuserid) && ($loginuserid!=$userid) && !($useraccount['flags'] & QA_USER_FLAGS_NO_MESSAGES) )
			$qa_content['form_profile']['fields']['level']['value'].=strtr(qa_lang_html('profile/send_private_message'), array(
				'^1' => '<A HREF="'.qa_path_html('message/'.$handle).'">',
				'^2' => '</A>',
			));
				
	
	//	Show any extra privileges due to user's level or their points
	
		$showpermits=array();
		$permitoptions=qa_get_permit_options();
		
		foreach ($permitoptions as $permitoption)
			if (qa_opt($permitoption)<QA_PERMIT_CONFIRMED) // if it's not available to all users (after they've confirmed their email address)
				if (!qa_permit_error($permitoption, $userid, $useraccount['level'], $useraccount['flags'], $userpoints['points'])) { // but this user can
					if ($permitoption=='permit_retag_cat')
						$showpermits[]=qa_lang(qa_using_categories() ? 'profile/permit_recat' : 'profile/permit_retag');
					else
						$showpermits[]=qa_lang('profile/'.$permitoption); // then show it as an extra priviliege
				}
				
		if (count($showpermits))
			$qa_content['form_profile']['fields']['permits']=array(
				'type' => 'static',
				'label' => qa_lang_html('profile/extra_privileges'),
				'value' => qa_html(implode("\n", $showpermits), true),
				'rows' => count($showpermits),
			);
		
	
	//	Show email address only if we're an administrator
		
		if (($loginlevel>=QA_USER_LEVEL_ADMIN) && !qa_user_permit_error()) {
			$doconfirms=qa_opt('confirm_user_emails') && ($useraccount['level']<QA_USER_LEVEL_EXPERT);
			$isconfirmed=($useraccount['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED) ? true : false;
	
			$qa_content['form_profile']['fields']['email']=array(
				'type' => $userediting ? 'text' : 'static',
				'label' => qa_lang_html('users/email_label'),
				'tags' => 'NAME="email"',
				'value' => qa_html(isset($inemail) ? $inemail : $useraccount['email']),
				'error' => qa_html(@$errors['email']),
				'note' => ($doconfirms ? (qa_lang_html($isconfirmed ? 'users/email_confirmed' : 'users/email_not_confirmed').' ') : '').
					qa_lang_html('users/only_shown_admins'),
			);

		}
			
	
	//	Show IP addresses and times for last login or write - only if we're a moderator or higher
	
		if (($loginlevel>=QA_USER_LEVEL_MODERATOR) && !qa_user_permit_error()) {
			$qa_content['form_profile']['fields']['lastlogin']=array(
				'type' => 'static',
				'label' => qa_lang_html('users/last_login_label'),
				'value' =>
					strtr(qa_lang_html('users/x_ago_from_y'), array(
						'^1' => qa_time_to_string(qa_opt('db_time')-$useraccount['loggedin']),
						'^2' => qa_ip_anchor_html($useraccount['loginip']),
					)),
				'note' => qa_lang_html('users/only_shown_moderators'),
			);

			if (isset($useraccount['written']))
				$qa_content['form_profile']['fields']['lastwrite']=array(
					'type' => 'static',
					'label' => qa_lang_html('users/last_write_label'),
					'value' =>
						strtr(qa_lang_html('users/x_ago_from_y'), array(
							'^1' => qa_time_to_string(qa_opt('db_time')-$useraccount['written']),
							'^2' => qa_ip_anchor_html($useraccount['writeip']),
						)),
					'note' => qa_lang_html('users/only_shown_moderators'),
				);
			else
				unset($qa_content['form_profile']['fields']['lastwrite']);

		}
		

	//	Show other profile fields

		$fieldsediting=$fieldseditable && $userediting;
		
		foreach ($userfields as $userfield) {	
			if (($userfield['flags'] & QA_FIELD_FLAGS_LINK_URL) && !$fieldsediting)
				$valuehtml=qa_url_to_html_link(@$userprofile[$userfield['title']], qa_opt('links_in_new_window'));

			else {
				$value=@$inprofile[$userfield['fieldid']];
				if (!isset($value))
					$value=@$userprofile[$userfield['title']];

				$valuehtml=qa_html($value, (($userfield['flags'] & QA_FIELD_FLAGS_MULTI_LINE) && !$fieldsediting) ? true : false);
			}
					
			$label=trim(qa_user_userfield_label($userfield), ':');
			if (strlen($label))
				$label.=':';
				
			$qa_content['form_profile']['fields'][$userfield['title']]=array(
				'type' => $fieldsediting ? 'text' : 'static',
				'label' => qa_html($label),
				'tags' => 'NAME="field_'.$userfield['fieldid'].'"',
				'value' => $valuehtml,
				'error' => qa_html(@$errors[$userfield['fieldid']]),
				'rows' => ($userfield['flags'] & QA_FIELD_FLAGS_MULTI_LINE) ? 8 : null,
			);
		}
		

	//	Edit form or button, if appropriate
		
		if ($usereditbutton) {

			if ($userediting) {

				if (
					(qa_opt('avatar_allow_gravatar') && ($useraccount['flags'] & QA_USER_FLAGS_SHOW_GRAVATAR)) ||
					(qa_opt('avatar_allow_upload') && (($useraccount['flags'] & QA_USER_FLAGS_SHOW_AVATAR)) && isset($useraccount['avatarblobid']))
				) {
					$qa_content['form_profile']['fields']['removeavatar']=array(
						'type' => 'checkbox',
						'label' => qa_lang_html('users/remove_avatar'),
						'tags' => 'NAME="removeavatar"',
					);
				}
				
				if (isset($maxlevelassign)) {
					$qa_content['form_profile']['fields']['level']['type']='select';
		
					$leveloptions=array(QA_USER_LEVEL_BASIC, QA_USER_LEVEL_EXPERT, QA_USER_LEVEL_EDITOR, QA_USER_LEVEL_MODERATOR, QA_USER_LEVEL_ADMIN, QA_USER_LEVEL_SUPER);
	
					foreach ($leveloptions as $leveloption)
						if ($leveloption<=$maxlevelassign)
							$qa_content['form_profile']['fields']['level']['options'][$leveloption]=qa_html(qa_user_level_string($leveloption));
				}
				
				$qa_content['form_profile']['buttons']=array(
					'save' => array(
						'label' => qa_lang_html('users/save_user'),
					),
					
					'cancel' => array(
						'tags' => 'NAME="docancel"',
						'label' => qa_lang_html('main/cancel_button'),
					),
				);
				
				$qa_content['form_profile']['hidden']=array(
					'dosave' => '1',
				);

			} else {
				$qa_content['form_profile']['buttons']=array(
					'edit' => array(
						'tags' => 'NAME="doedit"',
						'label' => qa_lang_html('users/edit_user_button'),
					),
				);
				
				if (isset($maxlevelassign) && ($useraccount['level']<QA_USER_LEVEL_MODERATOR)) {
					if ($useraccount['flags'] & QA_USER_FLAGS_USER_BLOCKED) {
						$qa_content['form_profile']['buttons']['unblock']=array(
							'tags' => 'NAME="dounblock"',
							'label' => qa_lang_html('users/unblock_user_button'),
						);
						
						if (count($questions) && !qa_user_permit_error('permit_hide_show'))
							$qa_content['form_profile']['buttons']['hideall']=array(
								'tags' => 'NAME="dohideall"',
								'label' => qa_lang_html('users/hide_all_user_button'),
							);
							
						if ($loginlevel>=QA_USER_LEVEL_ADMIN)
							$qa_content['form_profile']['buttons']['delete']=array(
								'tags' => 'NAME="dodelete"',
								'label' => qa_lang_html('users/delete_user_button'),
							);
						
					} else
						$qa_content['form_profile']['buttons']['block']=array(
							'tags' => 'NAME="doblock"',
							'label' => qa_lang_html('users/block_user_button'),
						);
				}
			}
		}
		
		if (!is_array($qa_content['form_profile']['fields']['removeavatar']))
			unset($qa_content['form_profile']['fields']['removeavatar']);
			
		$qa_content['raw']['account']=$useraccount; // for plugin layers to access
		$qa_content['raw']['profile']=$userprofile;
	}
	

//	Information about user activity, available also with single sign-on integration

	$qa_content['form_activity']=array(
		'title' => '<A NAME="activity">'.qa_lang_html_sub('profile/activity_by_x', $userhtml).'</A>',
		
		'style' => 'wide',
		
		'fields' => array(
			'bonus' => array(
				'label' => qa_lang_html('profile/bonus_points'),
				'tags' => 'NAME="bonus"',
				'value' => qa_html($userpoints['bonus']),
				'type' => 'number',
				'note' => qa_lang_html('users/only_shown_admins'),
			),
	
			'points' => array(
				'type' => 'static',
				'label' => qa_lang_html('profile/score'),
				'value' => (@$userpoints['points']==1)
					? qa_lang_html_sub('main/1_point', '<SPAN CLASS="qa-uf-user-points">1</SPAN>', '1')
					: qa_lang_html_sub('main/x_points', '<SPAN CLASS="qa-uf-user-points">'.qa_html(number_format(@$userpoints['points'])).'</SPAN>')
			),
			
			'title' => array(
				'type' => 'static',
				'label' => qa_lang_html('profile/title'),
				'value' => qa_get_points_title_html(@$userpoints['points'], qa_get_points_to_titles()),
			),
			
			'questions' => array(
				'type' => 'static',
				'label' => qa_lang_html('profile/questions'),
				'value' => '<SPAN CLASS="qa-uf-user-q-posts">'.qa_html(number_format(@$userpoints['qposts'])).'</SPAN>',
			),
	
			'answers' => array(
				'type' => 'static',
				'label' => qa_lang_html('profile/answers'),
				'value' => '<SPAN CLASS="qa-uf-user-a-posts">'.qa_html(number_format(@$userpoints['aposts'])).'</SPAN>',
			),
		),
	);
	
	if ($loginlevel>=QA_USER_LEVEL_ADMIN) {
		$qa_content['form_activity']['tags']='METHOD="POST" ACTION="'.qa_self_html().'"';
		
		$qa_content['form_activity']['buttons']=array(
			'setbonus' => array(
				'tags' => 'NAME="dosetbonus"',
				'label' => qa_lang_html('profile/set_bonus_button'),
			),
		);
		
	} else
		unset($qa_content['form_activity']['fields']['bonus']);
	
	if (!isset($qa_content['form_activity']['fields']['title']['value']))
		unset($qa_content['form_activity']['fields']['title']);
	
	if (qa_opt('comment_on_qs') || qa_opt('comment_on_as')) { // only show comment count if comments are enabled
		$qa_content['form_activity']['fields']['comments']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/comments'),
			'value' => '<SPAN CLASS="qa-uf-user-c-posts">'.qa_html(number_format(@$userpoints['cposts'])).'</SPAN>',
		);
	}
	
	if (qa_opt('voting_on_qs') || qa_opt('voting_on_as')) { // only show vote record if voting is enabled
		$votedonvalue='';
		
		if (qa_opt('voting_on_qs')) {
			$qvotes=@$userpoints['qupvotes']+@$userpoints['qdownvotes'];

			$innervalue='<SPAN CLASS="qa-uf-user-q-votes">'.number_format($qvotes).'</SPAN>';
			$votedonvalue.=($qvotes==1) ? qa_lang_html_sub('main/1_question', $innervalue, '1')
				: qa_lang_html_sub('main/x_questions', $innervalue);
				
			if (qa_opt('voting_on_as'))
				$votedonvalue.=', ';
		}
		
		if (qa_opt('voting_on_as')) {
			$avotes=@$userpoints['aupvotes']+@$userpoints['adownvotes'];
			
			$innervalue='<SPAN CLASS="qa-uf-user-a-votes">'.number_format($avotes).'</SPAN>';
			$votedonvalue.=($avotes==1) ? qa_lang_html_sub('main/1_answer', $innervalue, '1')
				: qa_lang_html_sub('main/x_answers', $innervalue);
		}
		
		$qa_content['form_activity']['fields']['votedon']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/voted_on'),
			'value' => $votedonvalue,
		);
		
		$upvotes=@$userpoints['qupvotes']+@$userpoints['aupvotes'];
		$innervalue='<SPAN CLASS="qa-uf-user-upvotes">'.number_format($upvotes).'</SPAN>';
		$votegavevalue=(($upvotes==1) ? qa_lang_html_sub('profile/1_up_vote', $innervalue, '1') : qa_lang_html_sub('profile/x_up_votes', $innervalue)).', ';
		
		$downvotes=@$userpoints['qdownvotes']+@$userpoints['adownvotes'];
		$innervalue='<SPAN CLASS="qa-uf-user-downvotes">'.number_format($downvotes).'</SPAN>';
		$votegavevalue.=($downvotes==1) ? qa_lang_html_sub('profile/1_down_vote', $innervalue, '1') : qa_lang_html_sub('profile/x_down_votes', $innervalue);
		
		$qa_content['form_activity']['fields']['votegave']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/gave_out'),
			'value' => $votegavevalue,
		);

		$innervalue='<SPAN CLASS="qa-uf-user-upvoteds">'.number_format(@$userpoints['upvoteds']).'</SPAN>';
		$votegotvalue=((@$userpoints['upvoteds']==1) ? qa_lang_html_sub('profile/1_up_vote', $innervalue, '1')
			: qa_lang_html_sub('profile/x_up_votes', $innervalue)).', ';
			
		$innervalue='<SPAN CLASS="qa-uf-user-downvoteds">'.number_format(@$userpoints['downvoteds']).'</SPAN>';
		$votegotvalue.=(@$userpoints['downvoteds']==1) ? qa_lang_html_sub('profile/1_down_vote', $innervalue, '1')
			: qa_lang_html_sub('profile/x_down_votes', $innervalue);

		$qa_content['form_activity']['fields']['votegot']=array(
			'type' => 'static',
			'label' => qa_lang_html('profile/received'),
			'value' => $votegotvalue,
		);
	}
	
	if (@$userpoints['points'])
		$qa_content['form_activity']['fields']['points']['value'].=
			qa_lang_html_sub('profile/ranked_x', '<SPAN CLASS="qa-uf-user-rank">'.number_format($userrank).'</SPAN>');
	
	if (@$userpoints['aselects'])
		$qa_content['form_activity']['fields']['questions']['value'].=($userpoints['aselects']==1)
			? qa_lang_html_sub('profile/1_with_best_chosen', '<SPAN CLASS="qa-uf-user-q-selects">1</SPAN>', '1')
			: qa_lang_html_sub('profile/x_with_best_chosen', '<SPAN CLASS="qa-uf-user-q-selects">'.number_format($userpoints['aselects']).'</SPAN>');
	
	if (@$userpoints['aselecteds'])
		$qa_content['form_activity']['fields']['answers']['value'].=($userpoints['aselecteds']==1)
			? qa_lang_html_sub('profile/1_chosen_as_best', '<SPAN CLASS="qa-uf-user-a-selecteds">1</SPAN>', '1')
			: qa_lang_html_sub('profile/x_chosen_as_best', '<SPAN CLASS="qa-uf-user-a-selecteds">'.number_format($userpoints['aselecteds']).'</SPAN>');


//	For plugin layers to access

	$qa_content['raw']['userid']=$userid;
	$qa_content['raw']['points']=$userpoints;
	$qa_content['raw']['rank']=$userrank;


//	Recent posts by this user

	if ($pagesize>0) {
		if (count($questions))
			$qa_content['q_list']['title']=qa_lang_html_sub('profile/recent_activity_by_x', $userhtml);
		else
			$qa_content['q_list']['title']=qa_lang_html_sub('profile/no_posts_by_x', $userhtml);
			
		$qa_content['q_list']['form_profile']=array(
			'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
		);
		
		$qa_content['q_list']['qs']=array();
		
		$htmloptions=qa_post_html_defaults('Q');
		$htmloptions['whoview']=false;
		$htmloptions['avatarsize']=0;
		
		foreach ($questions as $question)
			$qa_content['q_list']['qs'][]=qa_any_to_q_html_fields($question, $loginuserid, qa_cookie_get(), $usershtml,
				null, $htmloptions);
	}


	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/