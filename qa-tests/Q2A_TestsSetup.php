<?php

class Q2A_TestsSetup
{
	private $useDatabase = false;
	private $qaConfig = __DIR__ . '/../qa-config.php';
	private $qaConfigBackup = __DIR__ . '/../qa-config.php.bak';
	private $phpunitConfig = __DIR__ . '/phpunit-qa-config.php';

	public function run()
	{
		if (!$this->databaseIsNeeded()) {
			$this->initialConfiguration();
			return;
		}

		// If we're using the database, replace config with a stand-in before loading Q2A
		$this->useDatabase = true;
		$this->replaceConfig();
		// This ensures it's restored after PHPUnit finishes
		register_shutdown_function(function() {
			$this->restoreConfig();
		});

		$this->initialConfiguration();
		$this->recreateTables();
		$this->createContent();
	}

	/**
	 * Backup config file, overwrite with phpunit stand-in.
	 */
	private function replaceConfig()
	{
		if (!is_file($this->qaConfig)) {
			throw new Exception("Q2A config file {$this->qaConfig} could not be found.");
		}
		if (!is_file($this->phpunitConfig)) {
			throw new Exception("PHPUnit stand-in config file {$this->phpunitConfig} could not be found.");
		}

		if (!copy($this->qaConfig, $this->qaConfigBackup)) {
			throw new Exception("Could not backup Q2A config file; tests will not be run.");
		}
		if (!copy($this->phpunitConfig, $this->qaConfig)) {
			throw new Exception("Could not replace Q2A config file with PHPUnit stand-in; tests will not be run.");
		}
	}

	/**
	 * Remove phpunit stand-in, restore original config file.
	 */
	private function restoreConfig()
	{
		if (!is_file($this->qaConfigBackup)) {
			throw new Exception("Q2A config backup file {$this->qaConfigBackup} could not be found.");
		}

		if (copy($this->qaConfigBackup, $this->qaConfig)) {
			unlink($this->qaConfigBackup);
		}
	}

	private function initialConfiguration()
	{
		// Prevent accessing database before we're ready (required in the case of the test database not set up yet)
		global $qa_options_cache, $qa_autoconnect;
		$qa_autoconnect = false;
		$qa_options_cache['enabled_plugins'] = '';

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
	 * used to run PHPUnit, and if the database tests were explicitly excluded there then the test
	 * database is not generated. These are two example command lines with and without the exclusion:
	 * `phpunit --bootstrap qa-tests/autoload.php qa-tests`
	 * `phpunit --bootstrap qa-tests/autoload.php --exclude-group database qa-tests`
	 */
	private function databaseIsNeeded()
	{
		global $argv;

		foreach ($argv as $index => $arg) {
			if ($arg === '--exclude-group' && isset($argv[$index + 1]) && $argv[$index + 1] === 'database') {
				return false;
			}
		}

		return true;
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
