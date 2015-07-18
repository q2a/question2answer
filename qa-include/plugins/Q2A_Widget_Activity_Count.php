<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-widget-activity-count.php
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

class Q2A_Widget_Activity_Count extends Q2A_Plugin_Module_Widget
{
	public function getInternalId()
	{
		return 'Q2A_Widget_Activity_Count';
	}

	public function getDisplayName() {
		return 'Activity Count';
	}

	public function isAllowedInRegion($region)
	{
		return $region === 'side';
	}

	public function output($region, $place, $themeObject, $template, &$qa_content)
	{
		$themeObject->output('<div class="qa-activity-count">');

		$this->outputCount($themeObject, qa_opt('cache_qcount'), 'main/1_question', 'main/x_questions');
		$this->outputCount($themeObject, qa_opt('cache_acount'), 'main/1_answer', 'main/x_answers');

		if (qa_opt('comment_on_qs') || qa_opt('comment_on_as'))
			$this->outputCount($themeObject, qa_opt('cache_ccount'), 'main/1_comment', 'main/x_comments');

		$this->outputCount($themeObject, qa_opt('cache_userpointscount'), 'main/1_user', 'main/x_users');

		$themeObject->output('</div>');
	}

	public function outputCount($themeobject, $value, $langsingular, $langplural)
	{
		require_once QA_INCLUDE_DIR . 'app/format.php';

		$themeobject->output('<p class="qa-activity-count-item">');

		if ($value==1)
			$themeobject->output(qa_lang_html_sub($langsingular, '<span class="qa-activity-count-data">1</span>', '1'));
		else
			$themeobject->output(qa_lang_html_sub($langplural, '<span class="qa-activity-count-data">'.qa_format_number((int) $value, 0, true).'</span>'));

		$themeobject->output('</p>');
	}
}
