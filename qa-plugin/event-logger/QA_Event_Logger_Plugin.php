<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/event-logger/qa-plugin.php
	Description: Initiates event logger plugin


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

class QA_Event_Logger_Plugin extends Q2A_Plugin_BasePlugin
{
	public function getId()
	{
		return 'QA_Event_Logger_Plugin';
	}

	public function initialization()
	{
		$this->registerModule('QA_Event_Logger_Settings.php');
		$this->registerModule('QA_Event_Logger_Event.php');
	}

	public function requiresDatabaseInitialization($tables)
	{
		return qa_opt('event_logger_to_database') && !in_array(qa_db_add_table_prefix('eventlog'), $tables);
	}

	public function initializeDatabase(Q2A_Install_ProgressUpdater $progressUpdater)
	{
		require_once QA_INCLUDE_DIR.'app/users.php';
		require_once QA_INCLUDE_DIR.'db/maxima.php';

		$query =
			'CREATE TABLE ^eventlog ('.
				'datetime DATETIME NOT NULL,'.
				'ipaddress VARCHAR (15) CHARACTER SET ascii,'.
				'userid ' . qa_get_mysql_user_column_type() . ','.
				'handle VARCHAR(' . QA_DB_MAX_HANDLE_LENGTH . '),'.
				'cookieid BIGINT UNSIGNED,'.
				'event VARCHAR (20) CHARACTER SET ascii NOT NULL,'.
				'params VARCHAR (800) NOT NULL,'.
				'KEY datetime (datetime),'.
				'KEY ipaddress (ipaddress),'.
				'KEY userid (userid),'.
				'KEY event (event)'.
			') ENGINE=MyISAM DEFAULT CHARSET=utf8';
		$progressUpdater->update($query);
		qa_db_query_sub($query);
	}

}