<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Sets up routing for all regular pages in Q2A.


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

use Q2A\Http\Router;

/**
 * Set up routing using Controllers.
 */
function qa_controller_routing(Router $router)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$ns = '\Q2A\Controllers'; // base namespace

	$router->addRoute('GET', 'user/{str}', "$ns\User\UserProfile", 'profile', ['template' => 'user']);
	$router->addRoute('POST', 'user/{str}', "$ns\User\UserProfile", 'profile', ['template' => 'user']);
	$router->addRoute('GET', 'user', "$ns\User\UserProfile", 'index');
	$router->addRoute('GET', 'user/{str}/wall', "$ns\User\UserMessages", 'wall', ['template' => 'user-wall']);
	$router->addRoute('GET', 'user/{str}/activity', "$ns\User\UserPosts", 'activity', ['template' => 'user-activity']);
	$router->addRoute('GET', 'user/{str}/questions', "$ns\User\UserPosts", 'questions', ['template' => 'user-questions']);
	$router->addRoute('GET', 'user/{str}/answers', "$ns\User\UserPosts", 'answers', ['template' => 'user-answers']);

	$router->addRoute('GET', 'users', "$ns\User\UsersList", 'top', ['template' => 'users']);
	$router->addRoute('GET', 'users/blocked', "$ns\User\UsersList", 'blocked', ['template' => 'users']);
	$router->addRoute('GET', 'users/new', "$ns\User\UsersList", 'newest', ['template' => 'users']);
	$router->addRoute('GET', 'users/special', "$ns\User\UsersList", 'special', ['template' => 'users']);

	$router->addRoute('GET', 'ip/{str}', "$ns\User\Ip", 'address', ['template' => 'ip']);
	$router->addRoute('POST', 'ip/{str}', "$ns\User\Ip", 'address', ['template' => 'ip']);

	$router->addRoute('GET', 'admin/userfields', "$ns\Admin\UserFields", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/userfields', "$ns\Admin\UserFields", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/usertitles', "$ns\Admin\UserTitles", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/usertitles', "$ns\Admin\UserTitles", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/layoutwidgets', "$ns\Admin\Widgets", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/layoutwidgets', "$ns\Admin\Widgets", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/categories', "$ns\Admin\Categories", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/categories', "$ns\Admin\Categories", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/pages', "$ns\Admin\Pages", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/pages', "$ns\Admin\Pages", 'index', ['template' => 'admin']);

	$router->addRoute('GET', 'admin/points', "$ns\Admin\Points", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/points', "$ns\Admin\Points", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/stats', "$ns\Admin\Stats", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/plugins', "$ns\Admin\Plugins", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/plugins', "$ns\Admin\Plugins", 'index', ['template' => 'admin']);

	$router->addRoute('GET', 'admin/moderate', "$ns\Admin\Moderate", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/moderate', "$ns\Admin\Moderate", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/flagged', "$ns\Admin\Flagged", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/flagged', "$ns\Admin\Flagged", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/hidden', "$ns\Admin\Hidden", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/hidden', "$ns\Admin\Hidden", 'index', ['template' => 'admin']);
	$router->addRoute('GET', 'admin/approve', "$ns\Admin\Approve", 'index', ['template' => 'admin']);
	$router->addRoute('POST', 'admin/approve', "$ns\Admin\Approve", 'index', ['template' => 'admin']);
}

/**
 * Return an array of the default Q2A requests and which /qa-include/pages/*.php file implements them
 * If the key of an element ends in /, it should be used for any request with that key as its prefix
 */
function qa_page_routing()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return array(
		'account' => 'pages/account.php',
		'activity/' => 'pages/activity.php',
		'admin/' => 'pages/admin/admin-default.php',
		'admin/recalc' => 'pages/admin/admin-recalc.php',
		'answers/' => 'pages/answers.php',
		'ask' => 'pages/ask.php',
		'categories/' => 'pages/categories.php',
		'comments/' => 'pages/comments.php',
		'confirm' => 'pages/confirm.php',
		'favorites' => 'pages/favorites.php',
		'favorites/questions' => 'pages/favorites-list.php',
		'favorites/users' => 'pages/favorites-list.php',
		'favorites/tags' => 'pages/favorites-list.php',
		'feedback' => 'pages/feedback.php',
		'forgot' => 'pages/forgot.php',
		'hot/' => 'pages/hot.php',
		'login' => 'pages/login.php',
		'logout' => 'pages/logout.php',
		'messages/' => 'pages/messages.php',
		'message/' => 'pages/message.php',
		'questions/' => 'pages/questions.php',
		'register' => 'pages/register.php',
		'reset' => 'pages/reset.php',
		'search' => 'pages/search.php',
		'tag/' => 'pages/tag.php',
		'tags' => 'pages/tags.php',
		'unanswered/' => 'pages/unanswered.php',
		'unsubscribe' => 'pages/unsubscribe.php',
		'updates' => 'pages/updates.php',
	);
}
