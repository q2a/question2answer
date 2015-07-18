<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/basic-adsense/qa-basic-adsense.php
	Description: Widget module class for AdSense widget plugin


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

class QA_Event_Logger_Settings extends Q2A_Plugin_Module_Settings
{
	public function getInternalId()
	{
		return 'QA_Event_Logger_Settings';
	}

	public function getForm(&$qa_content)
	{
	//	Process form input

		$saved=false;

		if (qa_clicked('event_logger_save_button')) {
			qa_opt('event_logger_to_database', (int)qa_post_text('event_logger_to_database_field'));
			qa_opt('event_logger_to_files', qa_post_text('event_logger_to_files_field'));
			qa_opt('event_logger_directory', qa_post_text('event_logger_directory_field'));
			qa_opt('event_logger_hide_header', !qa_post_text('event_logger_hide_header_field'));

			$saved=true;
		}

	//	Check the validity of the currently entered directory (if any)

		$directory=qa_opt('event_logger_directory');

		$note=null;
		$error=null;

		if (!strlen($directory))
			$note='Please specify a directory that is writable by the web server.';
		elseif (!file_exists($directory))
			$error='This directory cannot be found. Please enter the full path.';
		elseif (!is_dir($directory))
			$error='This is a file. Please enter the full path of a directory.';
		elseif (!is_writable($directory))
			$error='This directory is not writable by the web server. Please choose a different directory, use chown/chmod to change permissions, or contact your web hosting company for assistance.';

	//	Create the form for display

		qa_set_display_rules($qa_content, array(
			'event_logger_directory_display' => 'event_logger_to_files_field',
			'event_logger_hide_header_display' => 'event_logger_to_files_field',
		));

		return array(
			'ok' => ($saved && !isset($error)) ? 'Event log settings saved' : null,

			'fields' => array(
				array(
					'label' => 'Log events to <code>'.QA_MYSQL_TABLE_PREFIX.'eventlog</code> database table',
					'tags' => 'name="event_logger_to_database_field"',
					'value' => qa_opt('event_logger_to_database'),
					'type' => 'checkbox',
				),

				array(
					'label' => 'Log events to daily log files',
					'tags' => 'name="event_logger_to_files_field" id="event_logger_to_files_field"',
					'value' => qa_opt('event_logger_to_files'),
					'type' => 'checkbox',
				),

				array(
					'id' => 'event_logger_directory_display',
					'label' => 'Directory for log files - enter full path:',
					'value' => qa_html($directory),
					'tags' => 'name="event_logger_directory_field"',
					'note' => $note,
					'error' => qa_html($error),
				),

				array(
					'id' => 'event_logger_hide_header_display',
					'label' => 'Include header lines at top of each log file',
					'type' => 'checkbox',
					'tags' => 'name="event_logger_hide_header_field"',
					'value' => !qa_opt('event_logger_hide_header'),
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="event_logger_save_button"',
				),
			),
		);
	}
}
