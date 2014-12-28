<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-filter-basic.php
	Description: Basic module for validating form inputs


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

require_once QA_INCLUDE_DIR.'db/maxima.php';
require_once QA_INCLUDE_DIR.'util/string.php';

class qa_filter_basic
{
	public function filter_email(&$email, $olduser)
	{
		if (!strlen($email))
			return qa_lang('users/email_required');

		if (!qa_email_validate($email))
			return qa_lang('users/email_invalid');

		if (qa_strlen($email)>QA_DB_MAX_EMAIL_LENGTH)
			return qa_lang_sub('main/max_length_x', QA_DB_MAX_EMAIL_LENGTH);
	}

	public function filter_handle(&$handle, $olduser)
	{
		if (!strlen($handle))
			return qa_lang('users/handle_empty');

		if (preg_match('/[\\@\\+\\/]/', $handle))
			return qa_lang_sub('users/handle_has_bad', '@ + /');

		if (qa_strlen($handle)>QA_DB_MAX_HANDLE_LENGTH)
			return qa_lang_sub('main/max_length_x', QA_DB_MAX_HANDLE_LENGTH);
	}

	public function filter_question(&$question, &$errors, $oldquestion)
	{
		$this->validate_length($errors, 'title', @$question['title'], qa_opt('min_len_q_title'),
			max(qa_opt('min_len_q_title'), min(qa_opt('max_len_q_title'), QA_DB_MAX_TITLE_LENGTH)));

		$this->validate_length($errors, 'content', @$question['content'], 0, QA_DB_MAX_CONTENT_LENGTH); // for storage

		$this->validate_length($errors, 'content', @$question['text'], qa_opt('min_len_q_content'), null); // for display

		if (isset($question['tags'])) {
			$counttags=count($question['tags']);
			$mintags=min(qa_opt('min_num_q_tags'), qa_opt('max_num_q_tags'));

			if ($counttags<$mintags)
				$errors['tags']=qa_lang_sub('question/min_tags_x', $mintags);
			elseif ($counttags>qa_opt('max_num_q_tags'))
				$errors['tags']=qa_lang_sub('question/max_tags_x', qa_opt('max_num_q_tags'));
			else
				$this->validate_length($errors, 'tags', qa_tags_to_tagstring($question['tags']), 0, QA_DB_MAX_TAGS_LENGTH); // for storage
		}

		$this->validate_post_email($errors, $question);
	}

	public function filter_answer(&$answer, &$errors, $question, $oldanswer)
	{
		$this->validate_length($errors, 'content', @$answer['content'], 0, QA_DB_MAX_CONTENT_LENGTH); // for storage
		$this->validate_length($errors, 'content', @$answer['text'], qa_opt('min_len_a_content'), null); // for display
		$this->validate_post_email($errors, $answer);
	}

	public function filter_comment(&$comment, &$errors, $question, $parent, $oldcomment)
	{
		$this->validate_length($errors, 'content', @$comment['content'], 0, QA_DB_MAX_CONTENT_LENGTH); // for storage
		$this->validate_length($errors, 'content', @$comment['text'], qa_opt('min_len_c_content'), null); // for display
		$this->validate_post_email($errors, $comment);
	}

	public function filter_profile(&$profile, &$errors, $user, $oldprofile)
	{
		foreach ($profile as $field => $value)
			$this->validate_length($errors, $field, $value, 0, QA_DB_MAX_PROFILE_CONTENT_LENGTH);
	}


//	The definitions below are not part of a standard filter module, but just used within this one

	/**
	 * Add textual element $field to $errors if length of $input is not between $minlength and $maxlength.
	 *
	 * @deprecated This function will become private in Q2A 1.8. It is specific to this plugin and
	 * should not be used by outside code.
	 */
	public function validate_length(&$errors, $field, $input, $minlength, $maxlength)
	{
		$length = isset($input) ? qa_strlen($input) : 0;

		if ($length < $minlength)
			$errors[$field] = ($minlength == 1) ? qa_lang('main/field_required') : qa_lang_sub('main/min_length_x', $minlength);
		elseif (isset($maxlength) && ($length > $maxlength))
			$errors[$field] = qa_lang_sub('main/max_length_x', $maxlength);
	}

	/**
	 * Wrapper function for validating a post's email address.
	 *
	 * @deprecated This function will become private in Q2A 1.8. It is specific to this plugin and
	 * should not be used by outside code.
	 */
	public function validate_post_email(&$errors, $post)
	{
		if (@$post['notify'] && strlen(@$post['email'])) {
			$error=$this->filter_email($post['email'], null);
			if (isset($error))
				$errors['email']=$error;
		}
	}
}
