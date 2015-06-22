<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/mouseover-layer/qa-mouseover-layer.php
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

class qa_html_theme_layer extends qa_html_theme_base
{
	public function q_list($q_list)
	{
		if (!empty($q_list['qs']) && qa_opt('mouseover_content_on')) { // first check it is not an empty list and the feature is turned on

			// Collect the question ids of all items in the question list (so we can do this in one DB query)

			$postids = array();
			foreach ($q_list['qs'] as $question) {
				if (isset($question['raw']['postid']))
					$postids[] = $question['raw']['postid'];
			}

			if (!empty($postids)) {

				// Retrieve the content for these questions from the database

				$maxlength = qa_opt('mouseover_content_max_len');
				$result = qa_db_query_sub('SELECT postid, content, format FROM ^posts WHERE postid IN (#)', $postids);
				$postinfo = qa_db_read_all_assoc($result, 'postid');

				// Get the regular expression fragment to use for blocked words and the maximum length of content to show

				$blockwordspreg = qa_get_block_words_preg();

				// Now add the popup to the title for each question

				foreach ($q_list['qs'] as $index => $question) {
					if (isset($postinfo[$question['raw']['postid']])) {
						$thispost = $postinfo[$question['raw']['postid']];
						$text = qa_viewer_text($thispost['content'], $thispost['format'], array('blockwordspreg' => $blockwordspreg));
						$text = preg_replace('/\s+/', ' ', $text);  // Remove duplicated blanks, new line characters, tabs, etc
						$text = qa_shorten_string_line($text, $maxlength);
						$title = isset($question['title']) ? $question['title'] : '';
						$q_list['qs'][$index]['title'] = $this->getHtmlTitle(qa_html($text), $title);
					}
				}
			}
		}

		parent::q_list($q_list); // call back through to the default function
	}

	/**
	 * Returns the needed HTML to display the tip. Depending on the theme in use, this might need to be
	 * tuned in order for the tip to be displayed properly
	 *
	 * @access private
	 * @param string $mouseOverText Text of the tip
	 * @param string $questionTitle Question title
	 * @return string HTML needed to display the tip and the question title
	 */
	private function getHtmlTitle($mouseOverText, $questionTitle)
	{
		return sprintf('<span title="%s">%s</span>', $mouseOverText, $questionTitle);
	}
}
