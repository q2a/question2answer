<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Definitions that determine database column size and rows retrieved


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


$maximaDefaults = array(
	// Maximum column sizes - any of these can be defined in qa-config.php to override the defaults below,
	// but you need to do so before creating the database, otherwise it's too late.
	'QA_DB_MAX_EMAIL_LENGTH' => 80,
	'QA_DB_MAX_HANDLE_LENGTH' => 20,
	'QA_DB_MAX_TITLE_LENGTH' => 800,
	'QA_DB_MAX_CONTENT_LENGTH' => 12000,
	'QA_DB_MAX_FORMAT_LENGTH' => 20,
	'QA_DB_MAX_TAGS_LENGTH' => 800,
	'QA_DB_MAX_NAME_LENGTH' => 40,
	'QA_DB_MAX_WORD_LENGTH' => 80,
	'QA_DB_MAX_CAT_PAGE_TITLE_LENGTH' => 80,
	'QA_DB_MAX_CAT_PAGE_TAGS_LENGTH' => 200,
	'QA_DB_MAX_CAT_CONTENT_LENGTH' => 800,
	'QA_DB_MAX_WIDGET_TAGS_LENGTH' => 800,
	'QA_DB_MAX_WIDGET_TITLE_LENGTH' => 80,
	'QA_DB_MAX_OPTION_TITLE_LENGTH' => 40,
	'QA_DB_MAX_PROFILE_TITLE_LENGTH' => 40,
	'QA_DB_MAX_PROFILE_CONTENT_LENGTH' => 8000,
	'QA_DB_MAX_CACHE_AGE' => 86400,
	'QA_DB_MAX_BLOB_FILE_NAME_LENGTH' => 255,
	'QA_DB_MAX_META_TITLE_LENGTH' => 40,
	'QA_DB_MAX_META_CONTENT_LENGTH' => 8000,
	'QA_DB_MAX_WORD_COUNT' => 255, // The field is currently a TINYINT so it shouldn't exceed this value

	// How many records to retrieve for different circumstances. In many cases we retrieve more records than we
	// end up needing to display once we know the value of an option. Wasteful, but allows one query per page.
	'QA_DB_RETRIEVE_QS_AS' => 50,
	'QA_DB_RETRIEVE_TAGS' => 200,
	'QA_DB_RETRIEVE_USERS' => 200,
	'QA_DB_RETRIEVE_ASK_TAG_QS' => 500,
	'QA_DB_RETRIEVE_COMPLETE_TAGS' => 1000,
	'QA_DB_RETRIEVE_MESSAGES' => 20,

	// Keep event streams trimmed - not worth storing too many events per question because we only display the
	// most recent event for each question, that has not been invalidated due to hiding/unselection/etc...
	'QA_DB_MAX_EVENTS_PER_Q' => 5,
);

foreach ($maximaDefaults as $key => $def) {
	if (!defined($key)) {
		define($key, $def);
	}
}
