<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Widget module class for activity count plugin


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

class qa_activity_count
{
	public function allow_template($template)
	{
		return true;
	}

	public function allow_region($region)
	{
		return ($region == 'side');
	}

	public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		$themeobject->output('<div class="qa-activity-count">');

		$this->output_count($themeobject, qa_opt('cache_qcount'), 'main/1_question', 'main/x_questions');
		$this->output_count($themeobject, qa_opt('cache_acount'), 'main/1_answer', 'main/x_answers');

		if (qa_opt('comment_on_qs') || qa_opt('comment_on_as'))
			$this->output_count($themeobject, qa_opt('cache_ccount'), 'main/1_comment', 'main/x_comments');

		$this->output_count($themeobject, qa_opt('cache_userpointscount'), 'main/1_user', 'main/x_users');

		$themeobject->output('</div>');
	}

	public function output_count($themeobject, $value, $langsingular, $langplural)
	{
		require_once QA_INCLUDE_DIR . 'app/format.php';

		$themeobject->output('<p class="qa-activity-count-item">');

		if ($value == 1)
			$themeobject->output(qa_lang_html_sub($langsingular, '<span class="qa-activity-count-data">1</span>', '1'));
		else
			$themeobject->output(qa_lang_html_sub($langplural, '<span class="qa-activity-count-data">' . qa_format_number((int)$value, 0, true) . '</span>'));

		$themeobject->output('</p>');
	}
}
