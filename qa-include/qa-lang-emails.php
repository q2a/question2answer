<?php
	
/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-lang-emails.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Language phrases for email notifications


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

	return array(
		'a_commented_body' => "Your answer on ^site_title has a new comment by ^c_handle:\n\n^open^c_content^close\n\nYour answer was:\n\n^open^c_context^close\n\nYou may respond by adding your own comment:\n\n^url\n\nThank you,\n\n^site_title",
		'a_commented_subject' => 'Your ^site_title answer has a new comment',

		'a_followed_body' => "Your answer on ^site_title has a new related question by ^q_handle:\n\n^open^q_title^close\n\nYour answer was:\n\n^open^a_content^close\n\nClick below to answer the new question:\n\n^url\n\nThank you,\n\n^site_title",
		'a_followed_subject' => 'Your ^site_title answer has a related question',

		'a_selected_body' => "Congratulations! Your answer on ^site_title has been selected as the best by ^s_handle:\n\n^open^a_content^close\n\nThe question was:\n\n^open^q_title^close\n\nClick below to see your answer:\n\n^url\n\nThank you,\n\n^site_title",
		'a_selected_subject' => 'Your ^site_title answer has been selected!',

		'c_commented_body' => "A new comment by ^c_handle has been added after your comment on ^site_title:\n\n^open^c_content^close\n\nThe discussion is following:\n\n^open^c_context^close\n\nYou may respond by adding another comment:\n\n^url\n\nThank you,\n\n^site_title",
		'c_commented_subject' => 'Your ^site_title comment has been added to',

		'confirm_body' => "Please click below to confirm your email address for ^site_title.\n\n^url\n\nThank you,\n^site_title",
		'confirm_subject' => '^site_title - Email Address Confirmation',

		'feedback_body' => "Comments:\n^message\n\nName:\n^name\n\nEmail:\n^email\n\nPrevious page:\n^previous\n\nUser:\n^url\n\nIP address:\n^ip\n\nBrowser:\n^browser",
		'feedback_subject' => '^ feedback',

		'flagged_body' => "A post by ^p_handle has received ^flags:\n\n^open^p_context^close\n\nClick below to see the post:\n\n^url\n\n\nClick below to review all flagged posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
		'flagged_subject' => '^site_title has a flagged post',

		'moderate_body' => "A post by ^p_handle requires your approval:\n\n^open^p_context^close\n\nClick below to approve or reject the post:\n\n^url\n\n\nClick below to review all queued posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
		'moderate_subject' => '^site_title moderation',

		'new_password_body' => "Your new password for ^site_title is below.\n\nPassword: ^password\n\nIt is recommended to change this password immediately after logging in.\n\nThank you,\n^site_title\n^url",
		'new_password_subject' => '^site_title - Your New Password',

		'private_message_body' => "You have been sent a private message by ^f_handle on ^site_title:\n\n^open^message^close\n\n^moreThank you,\n\n^site_title\n\n\nTo block private messages, visit your account page:\n^a_url",
		'private_message_info' => "More information about ^f_handle:\n\n^url\n\n",
		'private_message_reply' => "Click below to reply to ^f_handle by private message:\n\n^url\n\n",
		'private_message_subject' => 'Message from ^f_handle on ^site_title',

		'q_answered_body' => "Your question on ^site_title has been answered by ^a_handle:\n\n^open^a_content^close\n\nYour question was:\n\n^open^q_title^close\n\nIf you like this answer, you may select it as the best:\n\n^url\n\nThank you,\n\n^site_title",
		'q_answered_subject' => 'Your ^site_title question was answered',

		'q_commented_body' => "Your question on ^site_title has a new comment by ^c_handle:\n\n^open^c_content^close\n\nYour question was:\n\n^open^c_context^close\n\nYou may respond by adding your own comment:\n\n^url\n\nThank you,\n\n^site_title",
		'q_commented_subject' => 'Your ^site_title question has a new comment',

		'q_posted_body' => "A new question has been asked by ^q_handle:\n\n^open^q_title\n\n^q_content^close\n\nClick below to see the question:\n\n^url\n\nThank you,\n\n^site_title",
		'q_posted_subject' => '^site_title has a new question',
		
		'remoderate_body' => "An edited post by ^p_handle requires your reapproval:\n\n^open^p_context^close\n\nClick below to approve or hide the edited post:\n\n^url\n\n\nClick below to review all queued posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
		'remoderate_subject' => '^site_title moderation',

		'reset_body' => "Please click below to reset your password for ^site_title.\n\n^url\n\nAlternatively, enter the code below into the field provided.\n\nCode: ^code\n\nIf you did not ask to reset your password, please ignore this message.\n\nThank you,\n^site_title",
		'reset_subject' => '^site_title - Reset Forgotten Password',

		'to_handle_prefix' => "^,\n\n",
		
		'u_registered_body' => "A new user has registered as ^u_handle.\n\nClick below to view the user profile:\n\n^url\n\nThank you,\n\n^site_title",
		'u_to_approve_body' => "A new user has registered as ^u_handle.\n\nClick below to approve the user:\n\n^url\n\nClick below to review all users waiting for approval:\n\n^a_url\n\nThank you,\n\n^site_title",
		'u_registered_subject' => '^site_title has a new registered user',
		
		'u_approved_body' => "You can see your new user profile here:\n\n^url\n\nThank you,\n\n^site_title",
		'u_approved_subject' => 'Your ^site_title user has been approved',
		
		'wall_post_subject' => 'Post on your ^site_title wall',
		'wall_post_body' => "^f_handle has posted on your user wall at ^site_title:\n\n^open^post^close\n\nYou may respond to the post here:\n\n^url\n\nThank you,\n\n^site_title",

		'welcome_body' => "Thank you for registering for ^site_title.\n\n^custom^confirmYour login details are as follows:\n\nUsername: ^handle\nEmail: ^email\n\nPlease keep this information safe for future reference.\n\nThank you,\n\n^site_title\n^url",
		'welcome_confirm' => "Please click below to confirm your email address.\n\n^url\n\n",
		'welcome_subject' => 'Welcome to ^site_title!',
	);
	

/*
	Omit PHP closing tag to help avoid accidental output
*/