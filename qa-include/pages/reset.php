<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-page-reset.php
	Description: Controller for password reset page (comes after forgot page)


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

	// don't allow this page to be requested directly from browser
	if (!defined('QA_VERSION'))
	{ 
		header('Location: ../');
		exit;
	}


//	Check we're not using single-sign on integration and that we're not logged in

	if (QA_FINAL_EXTERNAL_USERS) 
	{
		qa_fatal_error('User login is handled by external code');			
	}

	if (qa_is_logged_in())
	{
		qa_redirect('');			
	}


	$dosubmitform = false;
	$showmsg_checkmail = false;
	
//	Process incoming form

	if (qa_clicked('doreset')) 
	{
		require_once QA_INCLUDE_DIR.'app/users-edit.php';
		require_once QA_INCLUDE_DIR.'db/users.php';

		$inemailhandle = qa_post_text('emailhandle');
		// trim to prevent passing in blank values to match uninitiated DB rows
		$incode = trim(qa_post_text('code')); 

		$errors = array();

		if (!qa_check_form_security_code('reset', qa_post_text('formcode'))) 
		{
			$errors['page'] = qa_lang_html('misc/form_security_again');
		}
		else 
		{
			if (qa_opt('allow_login_email_only') || (strpos($inemailhandle, '@')!==false)) // handles can't contain @ symbols
			{
				$matchusers = qa_db_user_find_by_email($inemailhandle);
			}
			else
			{
				$matchusers = qa_db_user_find_by_handle($inemailhandle);					
			}

			// if match more than one (should be impossible), consider it a non-match
			if (count($matchusers)==1) 
			{
				require_once QA_INCLUDE_DIR.'db/selects.php';
				
				$inuserid = $matchusers[0];
				$userinfo = qa_db_select_with_pending(qa_db_user_account_selectspec($inuserid, true));
				
				// strlen() check is vital otherwise we can reset code for most users by entering the empty string
				if (strlen($incode) && (strtolower(trim($userinfo['emailcode'])) == strtolower($incode))) 
				{
					qa_complete_reset_user($inuserid);
					// redirect to login page
					qa_redirect('login', array('e' => $inemailhandle, 'ps' => '1'));
				}
				else
				{
					$errors['code'] = qa_lang('users/reset_code_wrong');						
				}

			}
			else
			{
				$errors['emailhandle'] = qa_lang('users/user_not_found');					
			}
		}
	}
	else 
	{
		// in case the user clicked on the link in the email e and c are set in the URL
		$inemailhandle = qa_get('e');
		$incode = qa_get('c');
		if(isset($inemailhandle) && isset($incode))
		{
			// we fill the form and submit by javascript
			$dosubmitform = true;
		}
		else if(isset($inemailhandle) && !isset($incode))
		{
			// request comes directly from /forgot page, we only have the e in the URL
			$showmsg_checkmail = true;
		}
	}


//	Prepare content for theme

	$qa_content = qa_content_prepare();

	$qa_content['title'] = qa_lang_html('users/reset_title');
	
	$qa_content['error'] = @$errors['page'];

	if (empty($inemailhandle) || isset($errors['emailhandle']))
	{
		$forgotpath = qa_path('forgot');			
	}
	else
	{
		$forgotpath = qa_path('forgot', array('e' => $inemailhandle));			
	}

	// only show message to check for email
	if($showmsg_checkmail)
	{
		// $qa_content['success'] = qa_lang_html('users/reset_code_emailed');
		$qa_content['error'] = qa_lang_html('users/reset_code_emailed');
	}
	else
	{
		$qa_content['form'] = array(
			'tags' => 'method="post" action="'.qa_self_html().'" name="resetform"',

			'style' => 'tall',

			// 'ok' => empty($incode) ? qa_lang_html('users/reset_code_emailed') : null,

			'fields' => array(
				'email_handle' => array(
					'label' => qa_opt('allow_login_email_only') ? qa_lang_html('users/email_label') : qa_lang_html('users/email_handle_label'),
					'tags' => 'name="emailhandle" id="emailhandle"',
					'value' => qa_html(@$inemailhandle),
					'error' => qa_html(@$errors['emailhandle']),
				),

				'code' => array(
					'label' => qa_lang_html('users/reset_code_label'),
					'tags' => 'name="code" id="code"',
					'value' => qa_html(@$incode),
					'error' => qa_html(@$errors['code']),
					'note' => qa_lang_html('users/reset_code_emailed').' - '.
						'<a href="'.qa_html($forgotpath).'">'.qa_lang_html('users/reset_code_another').'</a>',
				),
			),

			'buttons' => array(
				'reset' => array(
					'label' => qa_lang_html('users/send_password_button'),
				),
			),

			'hidden' => array(
				'doreset' => '1',
				'formcode' => qa_get_form_security_code('reset'),
			),
		);

		$qa_content['focusid'] = (isset($errors['emailhandle']) || !strlen(@$inemailhandle)) ? 'emailhandle' : 'code';
	}

	if($dosubmitform)
	{
		$qa_content['custom'] = '
			<script type="text/javascript">
				document.resetform.submit();
			</script>
		';
	}
	
	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/