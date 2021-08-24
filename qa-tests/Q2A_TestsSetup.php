<?php

class Q2A_TestsSetup
{
	private $useDatabase = true;

	public function run()
	{
		$this->initialConfiguration();
		$this->recreateTables();
		$this->createContent();
	}

	private function initialConfiguration()
	{
		$this->disableDatabaseIfNeeded();

		// currently, all Q2A code depends on qa-base
		require_once __DIR__ . '/../qa-include/qa-base.php';

		// For qa_suspend_notifications
		require_once QA_INCLUDE_DIR . 'app/emails.php';

		// Include utils that might be needed by tests
		require_once __DIR__ . '/Q2A_TestsUtils.php';

		qa_suspend_notifications();
	}

	/**
	 * Detect whether Q2A should access the database while testing or not. This reads the command line
	 * used to run PHPUnit and, if the database tests were explicitly excluded there, then the database
	 * access is disabled. These are two example command lines with and without the exclusion:
	 * `phpunit --bootstrap qa-tests/autoload.php qa-tests`
	 * `phpunit --bootstrap qa-tests/autoload.php --exclude-group database qa-tests`
	 */
	private function disableDatabaseIfNeeded()
	{
		global $qa_options_cache, $qa_autoconnect, $argv;

		foreach ($argv as $index => $arg) {
			if ($arg === '--exclude-group' && isset($argv[$index + 1]) && $argv[$index + 1] === 'database') {
				$qa_autoconnect = false;
				$qa_options_cache['enabled_plugins'] = '';
				$this->useDatabase = false;
				break;
			}
		}
	}

	private function recreateTables()
	{
		if (!$this->useDatabase) {
			return;
		}

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

	private function createContent()
	{
		if (!$this->useDatabase) {
			return;
		}

		// For qa_create_new_user
		require_once QA_INCLUDE_DIR . 'app/users-edit.php';

		qa_create_new_user('superadmin@example.com', 'passpass', 'superadmin', QA_USER_LEVEL_SUPER, true);
	}
}
