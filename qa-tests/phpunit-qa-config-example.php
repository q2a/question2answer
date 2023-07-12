<?php
// Stand-in config file for PHPUnit

// IMPORTANT: All data in the database configured below will be wiped out in order to execute the tests
define('QA_MYSQL_HOSTNAME', '127.0.0.1');
define('QA_MYSQL_USERNAME', 'your-test-mysql-username');
define('QA_MYSQL_PASSWORD', 'your-test-mysql-password');
define('QA_MYSQL_DATABASE', 'your-test-mysql-db-name');

define('QA_MYSQL_TABLE_PREFIX', 'qa_test_');
define('QA_EXTERNAL_USERS', false);
