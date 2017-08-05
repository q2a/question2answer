<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax category information requests


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

require_once QA_INCLUDE_DIR . 'db/selects.php';


$categoryid = qa_post_text('categoryid');
if (!strlen($categoryid))
	$categoryid = null;

list($fullcategory, $categories) = qa_db_select_with_pending(
	qa_db_full_category_selectspec($categoryid, true),
	qa_db_category_sub_selectspec($categoryid)
);

echo "QA_AJAX_RESPONSE\n1\n";

echo qa_html(strtr(@$fullcategory['content'], "\r\n", '  ')); // category description

foreach ($categories as $category) {
	// subcategory information
	echo "\n" . $category['categoryid'] . '/' . $category['title'];
}
