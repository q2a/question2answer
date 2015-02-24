<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/wysiwyg-editor/qa-wysiwyg-editor.php
	Description: Editor module class for WYSIWYG editor plugin


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


class qa_wysiwyg_ajax
{
	public function match_request($request)
	{
		return $request == 'wysiwyg-editor-ajax';
	}

	// Fix path to WYSIWYG editor smilies
	public function process_request($request)
	{
		// todo: lock tables?

		$sql = 'SELECT postid, title, content FROM ^posts WHERE format="html" AND content LIKE "%/wysiwyg-editor/plugins/smiley/images/%" LIMIT 5';
		$result = qa_db_query_sub($sql);
		while (($post=qa_db_read_one_assoc($result, true)) !== null) {
			$search = '#<(img|a)([^>]+)(src|href)="([^"]+)/wysiwyg-editor/plugins/smiley/images/([^"]+)"#';
			$replace = '<$1$2$3="$4/wysiwyg-editor/ckeditor/plugins/smiley/images/$5"';
			$newcontent = preg_replace($search, $replace, $post['content']);

			qa_post_set_content($post['postid'], $post['title'], $newcontent);
		}
	}
}
