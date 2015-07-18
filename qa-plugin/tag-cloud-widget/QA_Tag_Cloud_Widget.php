<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/tag-cloud-widget/qa-tag-cloud.php
	Description: Widget module class for tag cloud plugin


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

class QA_Tag_Cloud_Widget extends Q2A_Plugin_Module_Widget
{
	public function getInternalId()
	{
		return 'QA_Tag_Cloud_Widget';
	}

	public function getDisplayName() {
		return 'Tag Cloud Widget';
	}

	public function isAllowedInTemplate($template)
	{
		$allowed = array(
			'activity', 'qa', 'questions', 'hot', 'ask', 'categories', 'question',
			'tag', 'tags', 'unanswered', 'user', 'users', 'search', 'admin', 'custom',
		);
		return in_array($template, $allowed);
	}

	public function isAllowedInRegion($region)
	{
		return $region === 'side';
	}

	public function output($region, $place, $themeObject, $template, &$qa_content)
	{
		require_once QA_INCLUDE_DIR.'db/selects.php';

		$populartags = qa_db_single_select(qa_db_popular_tags_selectspec(0, (int) qa_opt('tag_cloud_count_tags')));

		$maxcount = reset($populartags);

		$themeObject->output(sprintf('<h2 style="margin-top: 0; padding-top: 0;">%s</h2>', qa_lang_html('main/popular_tags')));

		$themeObject->output('<div style="font-size: 10px;">');

		$maxsize = qa_opt('tag_cloud_font_size');
		$minsize = qa_opt('tag_cloud_minimal_font_size');
		$scale = qa_opt('tag_cloud_size_popular');
		$blockwordspreg = qa_get_block_words_preg();

		foreach ($populartags as $tag => $count) {
			$matches = qa_block_words_match_all($tag, $blockwordspreg);
			if (empty($matches)) {
				if ($scale) {
					$size = number_format($maxsize * $count / $maxcount, 1);
					if ($size < $minsize)
						$size = $minsize;
				} else
					$size = $maxsize;

				$themeObject->output(sprintf('<a href="%s" style="font-size: %dpx; vertical-align: baseline;">%s</a>', qa_path_html('tag/' . $tag), $size, qa_html($tag)));
			}
		}

		$themeObject->output('</div>');
	}
}
