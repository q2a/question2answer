<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax favorite requests


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

require_once QA_INCLUDE_DIR . 'app/users.php';
require_once QA_INCLUDE_DIR . 'app/cookies.php';
require_once QA_INCLUDE_DIR . 'app/favorites.php';
require_once QA_INCLUDE_DIR . 'app/format.php';


$entitytype = qa_post_text('entitytype');
$entityid = qa_post_text('entityid');
$setfavorite = qa_post_text('favorite');

$userid = qa_get_logged_in_userid();

if (!qa_check_form_security_code('favorite-' . $entitytype . '-' . $entityid, qa_post_text('code'))) {
	echo "QA_AJAX_RESPONSE\n0\n" . qa_lang('misc/form_security_reload');
} elseif (isset($userid)) {
	$cookieid = qa_cookie_get();

	qa_user_favorite_set($userid, qa_get_logged_in_handle(), $cookieid, $entitytype, $entityid, $setfavorite);

	$favoriteform = qa_favorite_form($entitytype, $entityid, $setfavorite, qa_lang($setfavorite ? 'main/remove_favorites' : 'main/add_favorites'));

	$themeclass = qa_load_theme_class(qa_get_site_theme(), 'ajax-favorite', null, null);
	$themeclass->initialize();

	echo "QA_AJAX_RESPONSE\n1\n";

	$themeclass->favorite_inner_html($favoriteform);
}
