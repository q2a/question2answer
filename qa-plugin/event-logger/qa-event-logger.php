<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/event-logger/qa-event-logger.php
	Description: Event module class for event logger plugin


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

class qa_event_logger
{
	public function init_queries($table_list)
	{
		if (qa_opt('event_logger_to_database')) {
			$tablename = (new \Q2A\Database\DbQueryHelper)->addTablePrefix('eventlog');

			if (!in_array($tablename, $table_list)) {
				// table does not exist, so create it
				require_once QA_INCLUDE_DIR . 'app/users.php';
				require_once QA_INCLUDE_DIR . 'db/maxima.php';

				return 'CREATE TABLE ^eventlog (' .
					'datetime DATETIME NOT NULL,' .
					'ipaddress VARCHAR (45) CHARACTER SET ascii,' .
					'userid ' . qa_get_mysql_user_column_type() . ',' .
					'handle VARCHAR(' . QA_DB_MAX_HANDLE_LENGTH . '),' .
					'cookieid BIGINT UNSIGNED,' .
					'event VARCHAR (20) CHARACTER SET ascii NOT NULL,' .
					'params VARCHAR (800) NOT NULL,' .
					'KEY datetime (datetime),' .
					'KEY ipaddress (ipaddress),' .
					'KEY userid (userid),' .
					'KEY event (event)' .
					') ENGINE=MyISAM DEFAULT CHARSET=utf8';
			} else {
				// table exists: check it has the correct schema
				$column = qa_service('database')->query('SHOW COLUMNS FROM ^eventlog WHERE Field="ipaddress"')->fetchNextAssocOrFail();
				if (strtolower($column['Type']) !== 'varchar(45)') {
					// upgrade to handle IPv6
					return 'ALTER TABLE ^eventlog MODIFY ipaddress VARCHAR(45) CHARACTER SET ascii';
				}
			}
		}

		return array();
	}


	public function admin_form(&$qa_content)
	{
		// Process form input

		$saved = false;

		if (qa_clicked('event_logger_save_button')) {
			qa_opt('event_logger_to_database', (int)qa_post_text('event_logger_to_database_field'));
			qa_opt('event_logger_to_files', qa_post_text('event_logger_to_files_field'));
			qa_opt('event_logger_directory', qa_post_text('event_logger_directory_field'));
			qa_opt('event_logger_hide_header', !qa_post_text('event_logger_hide_header_field'));

			$saved = true;
		}

		// Check the validity of the currently entered directory (if any)

		$directory = qa_opt('event_logger_directory');

		$note = null;
		$error = null;

		if (!strlen($directory))
			$note = qa_lang_html('event_logger/specify_dir_writable');
		elseif (!file_exists($directory))
			$error = qa_lang_html('event_logger/dir_not_found');
		elseif (!is_dir($directory))
			$error = qa_lang_html('event_logger/enter_directory');
		elseif (!is_writable($directory))
			$error = qa_lang_html('event_logger/dir_not_writable');

		// Create the form for display

		qa_set_display_rules($qa_content, array(
			'event_logger_directory_display' => 'event_logger_to_files_field',
			'event_logger_hide_header_display' => 'event_logger_to_files_field',
		));

		return array(
			'ok' => ($saved && !isset($error)) ? qa_lang_html('admin/options_saved') : null,

			'fields' => array(
				array(
					'label' => qa_lang_html_sub('event_logger/log_events_x_table', '<code>' . qa_html(QA_MYSQL_TABLE_PREFIX . 'eventlog') . '</code>'),
					'tags' => 'name="event_logger_to_database_field"',
					'value' => qa_opt('event_logger_to_database'),
					'type' => 'checkbox',
				),

				array(
					'label' => qa_lang_html('event_logger/log_events_daily'),
					'tags' => 'name="event_logger_to_files_field" id="event_logger_to_files_field"',
					'value' => qa_opt('event_logger_to_files'),
					'type' => 'checkbox',
				),

				array(
					'id' => 'event_logger_directory_display',
					'label' => qa_lang_html('event_logger/log_files_dir'),
					'value' => qa_html($directory),
					'tags' => 'name="event_logger_directory_field"',
					'note' => $note,
					'error' => qa_html($error),
				),

				array(
					'id' => 'event_logger_hide_header_display',
					'label' => qa_lang_html('event_logger/header_lines'),
					'type' => 'checkbox',
					'tags' => 'name="event_logger_hide_header_field"',
					'value' => !qa_opt('event_logger_hide_header'),
				),
			),

			'buttons' => array(
				array(
					'label' => qa_lang_html('main/save_button'),
					'tags' => 'name="event_logger_save_button"',
				),
			),
		);
	}


	public function value_to_text($value)
	{
		require_once QA_INCLUDE_DIR . 'util/string.php';

		if (is_array($value))
			$text = 'array(' . count($value) . ')';
		elseif (qa_strlen($value) > 40)
			$text = qa_substr($value, 0, 38) . '...';
		else
			$text = $value;

		return strtr($text, "\t\n\r", '   ');
	}


	public function process_event($event, $userid, $handle, $cookieid, $params)
	{
		if (qa_opt('event_logger_to_database')) {
			$paramstring = '';

			foreach ($params as $key => $value) {
				$paramstring .= (strlen($paramstring) ? "\t" : '') . $key . '=' . $this->value_to_text($value);
			}

			qa_service('database')->query(
				'INSERT INTO ^eventlog (datetime, ipaddress, userid, handle, cookieid, event, params) ' .
				'VALUES (NOW(), ?, ?, ?, ?, ?, ?)',
				[qa_remote_ip_address(), $userid, $handle, $cookieid, $event, $paramstring]
			);
		}

		if (qa_opt('event_logger_to_files')) {
			// Substitute some placeholders if certain information is missing
			if (!strlen($userid))
				$userid = 'no_userid';

			if (!strlen($handle))
				$handle = 'no_handle';

			if (!strlen($cookieid))
				$cookieid = 'no_cookieid';

			$ip = qa_remote_ip_address();
			if (!strlen($ip))
				$ip = 'no_ipaddress';

			// Build the log file line to be written

			$fixedfields = array(
				'Date' => date('Y\-m\-d'),
				'Time' => date('H\:i\:s'),
				'IPaddress' => $ip,
				'UserID' => $userid,
				'Username' => $handle,
				'CookieID' => $cookieid,
				'Event' => $event,
			);

			$fields = $fixedfields;

			foreach ($params as $key => $value) {
				$fields['param_' . $key] = $key . '=' . $this->value_to_text($value);
			}

			$string = implode("\t", $fields);

			// Build the full path and file name

			$directory = qa_opt('event_logger_directory');

			if (substr($directory, -1) != '/')
				$directory .= '/';

			$filename = $directory . 'q2a-log-' . date('Y\-m\-d') . '.txt';

			// Open, lock, write, unlock, close (to prevent interference between multiple writes)

			$exists = file_exists($filename);

			$file = @fopen($filename, 'a');

			if (is_resource($file)) {
				if (flock($file, LOCK_EX)) {
					if (!$exists && filesize($filename) === 0 && !qa_opt('event_logger_hide_header')) {
						$string = qa_lang_html_sub('event_logger/q2a_x_log_file_generated', QA_VERSION) . "\n" .
							qa_lang_html('event_logger/utf8_tab_delimited_file') . "\n\n" .
							implode("\t", array_keys($fixedfields)) . "\t" . qa_lang_html('event_logger/extras') . "\n\n" . $string;
					}

					fwrite($file, $string . "\n");
					flock($file, LOCK_UN);
				}

				fclose($file);
			}
		}
	}
}
