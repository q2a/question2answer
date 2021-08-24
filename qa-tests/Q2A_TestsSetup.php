<?php

class Q2A_TestsSetup
{
	public function run()
	{
		// currently, all Q2A code depends on qa-base
		require_once __DIR__ . '/../qa-include/qa-base.php';

		$this->recreateTables();
		$this->initialConfiguration();
		$this->createContent();
	}

	private function recreateTables()
	{
		require_once QA_INCLUDE_DIR . 'db/install.php';

		qa_db_query_sub('SET FOREIGN_KEY_CHECKS = 0');
		qa_db_query_sub('SET GROUP_CONCAT_MAX_LEN=32768');
		qa_db_query_sub('SET @tables = NULL');
		qa_db_query_sub(
			'SELECT GROUP_CONCAT("`", table_name, "`") INTO @tables ' .
			'FROM information_schema.tables ' .
			'WHERE table_schema = (SELECT DATABASE())'
		);
		qa_db_query_sub('SELECT IFNULL(@tables, "dummy") INTO @tables');

		qa_db_query_sub('SET @tables = CONCAT("DROP TABLE IF EXISTS ", @tables)');
		qa_db_query_sub('PREPARE stmt FROM @tables');
		qa_db_query_sub('EXECUTE stmt');
		qa_db_query_sub('DEALLOCATE PREPARE stmt');

		qa_db_install_tables();
	}

	private function initialConfiguration()
	{
		// For qa_suspend_notifications
		require_once QA_INCLUDE_DIR . 'app/emails.php';

		// Include utils that might be needed by tests
		require_once __DIR__ . '/Q2A_TestsUtils.php';

		global $qa_options_cache, $qa_autoconnect;

		// Needed in order to avoid accessing the database while including the qa-base.php file
		$qa_options_cache['enabled_plugins'] = '';

		$qa_autoconnect = false;

		qa_suspend_notifications();
	}

	private function createContent()
	{
		// For qa_create_new_user
		require_once QA_INCLUDE_DIR . 'app/users-edit.php';

		qa_create_new_user('superadmin@example.com', 'passpass', 'superadmin', QA_USER_LEVEL_SUPER, true);
	}
}
