<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-config-example.php
	Description: After renaming, use this to set up database details and other stuff


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

/*
	======================================================================
	  THE 4 DEFINITIONS BELOW ARE REQUIRED AND MUST BE SET BEFORE USING!
	======================================================================

	For QA_MYSQL_HOSTNAME, try '127.0.0.1' or 'localhost' if MySQL is on the same server.

	For persistent connections, set the QA_PERSISTENT_CONN_DB at the bottom of this file; do NOT
	prepend the hostname with 'p:'.

	To use a non-default port, add the following line to the list of defines, with the appropriate port number:
	define('QA_MYSQL_PORT', '3306');
*/

	define('QA_MYSQL_HOSTNAME', '127.0.0.1');
	define('QA_MYSQL_USERNAME', 'your-mysql-username');
	define('QA_MYSQL_PASSWORD', 'your-mysql-password');
	define('QA_MYSQL_DATABASE', 'your-mysql-db-name');

/*
	Ultra-concise installation instructions:

	1. Create a MySQL database.
	2. Create a MySQL user with full permissions for that database.
	3. Rename this file to qa-config.php.
	4. Set the above four definitions and save.
	5. Place all the Question2Answer files on your server.
	6. Open the appropriate URL, and follow the instructions.

	More detailed installation instructions here: http://www.question2answer.org/
*/

/*
	======================================================================
	 OPTIONAL CONSTANT DEFINITIONS, INCLUDING SUPPORT FOR SINGLE SIGN-ON
	======================================================================

	QA_MYSQL_TABLE_PREFIX will be added to table names, to allow multiple datasets in a single
	MySQL database, or to include the Question2Answer tables in an existing MySQL database.
*/

	define('QA_MYSQL_TABLE_PREFIX', 'qa_');

/*
	If you wish, you can define QA_MYSQL_USERS_PREFIX separately from QA_MYSQL_TABLE_PREFIX.
	If so, tables containing information about user accounts (not including users' activity and points)
	get the prefix of QA_MYSQL_TABLE_PREFIX. This allows multiple Q2A sites to have shared logins
	and users, but separate posts and activity.

	If you have installed question2answer with default "qa_" prefix and want to setup a second
	installation, you define the QA_MYSQL_USERS_PREFIX as "qa_" so this new installation
	can access the same database as the first installation.

	define('QA_MYSQL_USERS_PREFIX', 'sharedusers_');
*/

/*
	If you wish, you can define QA_BLOBS_DIRECTORY to store BLOBs (binary large objects) such
	as avatars and uploaded files on disk, rather than in the database. If so this directory
	must be writable by the web server process - on Unix/Linux use chown/chmod as appropriate.
	Note than if multiple Q2A sites are using QA_MYSQL_USERS_PREFIX to share users, they must
	also have the same value for QA_BLOBS_DIRECTORY.

	If there are already some BLOBs stored in the database from previous uploads, click the
	'Move BLOBs to disk' button in the 'Stats' section of the admin panel to move them to disk.

	define('QA_BLOBS_DIRECTORY', '/path/to/writable_blobs_directory/');
*/

/*
	If you wish to use file-based caching, you must define QA_CACHE_DIRECTORY to store the cache
	files. The directory must be writable by the web server. For maximum security it's STRONGLY
	recommended to place the folder outside of the web root (so they can never be accessed via a
	web browser).

	define('QA_CACHE_DIRECTORY', '/path/to/writable_cache_directory/');
*/

/*
	If you wish, you can define QA_COOKIE_DOMAIN so that any cookies created by Q2A are assigned
	to a specific domain name, instead of the full domain name of the request by default. This is
	useful if you're running multiple Q2A sites on subdomains with a shared user base.

	define('QA_COOKIE_DOMAIN', '.example.com'); // be sure to keep the leading period
*/

/*
	If you wish, you can define an array $QA_CONST_PATH_MAP to modify the URLs used in your Q2A site.
	The key of each array element should be the standard part of the path, e.g. 'questions',
	and the value should be the replacement for that standard part, e.g. 'topics'. If you edit this
	file in UTF-8 encoding you can also use non-ASCII characters in these URLs.

	$QA_CONST_PATH_MAP = array(
		'questions' => 'topics',
		'categories' => 'sections',
		'users' => 'contributors',
		'user' => 'contributor',
	);
*/

/*
	Set QA_EXTERNAL_USERS to true to use your user identification code in qa-external/qa-external-users.php
	This allows you to integrate with your existing user database and management system. For more details,
	consult the online documentation on installing Question2Answer with single sign-on.

	The constants QA_EXTERNAL_LANG and QA_EXTERNAL_EMAILER are deprecated from Q2A 1.5 since the same
	effect can now be achieved in plugins by using function overrides.
*/

	define('QA_EXTERNAL_USERS', false);

/*
	Out-of-the-box WordPress 3.x integration - to integrate with your WordPress site and user
	database, define QA_WORDPRESS_INTEGRATE_PATH as the full path to the WordPress directory
	containing wp-load.php. You do not need to set the QA_MYSQL_* constants above since these
	will be taken from WordPress automatically. See online documentation for more details.

	define('QA_WORDPRESS_INTEGRATE_PATH', '/PATH/TO/WORDPRESS');
*/

/*
	Out-of-the-box Joomla! 3.x integration - to integrate with your Joomla! site, define
	QA_JOOMLA_INTEGRATE_PATH. as the full path to the Joomla! directory. If your Q2A
	site is a subdirectory of your main Joomla site (recommended), you can specify
	dirname(__DIR__) rather than the full path.
	With this set, you do not need to set the QA_MYSQL_* constants above since these
	will be taken from Joomla automatically. See online documentation for more details.

	define('QA_JOOMLA_INTEGRATE_PATH', dirname(__DIR__));
*/

/*
	Some settings to help optimize your Question2Answer site's performance.

	If QA_HTML_COMPRESSION is true, HTML web pages will be output using Gzip compression, which
	will increase the performance of your site (if the user's browser indicates this is supported).
	This is best done at the server level if possible, but many hosts don't provide server access.

	QA_MAX_LIMIT_START is the maximum start parameter that can be requested, for paging through
	long lists of questions, etc... As the start parameter gets higher, queries tend to get
	slower, since MySQL must examine more information. Very high start numbers are usually only
	requested by search engine robots anyway.

	If a word is used QA_IGNORED_WORDS_FREQ times or more in a particular way, it is ignored
	when searching or finding related questions. This saves time by ignoring words which are so
	common that they are probably not worth matching on.

	Set QA_ALLOW_UNINDEXED_QUERIES to true if you don't mind running some database queries which
	are not indexed efficiently. For example, this will enable browsing unanswered questions per
	category. If your database becomes large, these queries could become costly.

	Set QA_OPTIMIZE_DISTANT_DB to false if your web server and MySQL are running on the same box.
	When viewing a page on your site, this will use many simple MySQL queries instead of fewer
	complex ones, which makes sense since there is no latency for localhost access.
	Otherwise, set it to true if your web server and MySQL are far enough apart to create
	significant latency. This will minimize the number of database queries as much as is possible,
	even at the cost of significant additional processing at each end.

	The option QA_OPTIMIZE_LOCAL_DB is no longer used, since QA_OPTIMIZE_DISTANT_DB covers our uses.

	Set QA_PERSISTENT_CONN_DB to true to use persistent database connections. Requires PHP 5.3.
	Only use this if you are absolutely sure it is a good idea under your setup - generally it is
	not. For more information: http://www.php.net/manual/en/features.persistent-connections.php

	Set QA_DEBUG_PERFORMANCE to true to show detailed performance profiling information at the
	bottom of every Question2Answer page.
*/

	define('QA_HTML_COMPRESSION', false);
	define('QA_MAX_LIMIT_START', 19999);
	define('QA_IGNORED_WORDS_FREQ', 10000);
	define('QA_ALLOW_UNINDEXED_QUERIES', false);
	define('QA_OPTIMIZE_DISTANT_DB', false);
	define('QA_PERSISTENT_CONN_DB', false);
	define('QA_DEBUG_PERFORMANCE', false);

/*
	And lastly... if you want to, you can predefine any constant from qa-include/db/maxima.php in this
	file to override the default setting. Just make sure you know what you're doing!
*/
