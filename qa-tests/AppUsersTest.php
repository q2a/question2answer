<?php
require_once QA_INCLUDE_DIR.'app/users.php';

class AppUsersTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test logic of permissions function.
	 * User level values: QA_USER_LEVEL_* in app/users.php [BASIC..SUPER]
	 * Permission values: QA_PERMIT_* in app/options.php [ALL..SUPERS]
	 * User flag values: QA_USER_FLAGS_* in app/users.php
	 */
	public function test__qa_permit_value_error()
	{
		// set options cache to bypass database
		global $qa_options_cache;
		$qa_options_cache['confirm_user_emails'] = '1';
		$qa_options_cache['moderate_users'] = '0';

		$userFlags = QA_USER_FLAGS_EMAIL_CONFIRMED;
		$blockedFlags = QA_USER_FLAGS_EMAIL_CONFIRMED | QA_USER_FLAGS_USER_BLOCKED;

		// Admin trying to do Super stuff
		$error = qa_permit_value_error(QA_PERMIT_SUPERS, 1, QA_USER_LEVEL_ADMIN, $userFlags);
		$this->assertSame('level', $error);

		// Admin trying to do Admin stuff
		$error = qa_permit_value_error(QA_PERMIT_ADMINS, 1, QA_USER_LEVEL_ADMIN, $userFlags);
		$this->assertSame(false, $error);

		// Admin trying to do Editor stuff
		$error = qa_permit_value_error(QA_PERMIT_EDITORS, 1, QA_USER_LEVEL_ADMIN, $userFlags);
		$this->assertSame(false, $error);

		// Expert trying to do Moderator stuff
		$error = qa_permit_value_error(QA_PERMIT_MODERATORS, 1, QA_USER_LEVEL_EXPERT, $userFlags);
		$this->assertSame('level', $error);

		// Unconfirmed User trying to do Confirmed stuff
		$error = qa_permit_value_error(QA_PERMIT_CONFIRMED, 1, QA_USER_LEVEL_BASIC, 0);
		$this->assertSame('confirm', $error);

		// Blocked User trying to do anything
		$error = qa_permit_value_error(QA_PERMIT_ALL, 1, QA_USER_LEVEL_BASIC, $blockedFlags);
		$this->assertSame('userblock', $error);

		// Logged Out User trying to do User stuff
		$error = qa_permit_value_error(QA_PERMIT_USERS, null, null, 0);
		$this->assertSame('login', $error);

		// Logged Out User trying to do Moderator stuff
		$error = qa_permit_value_error(QA_PERMIT_MODERATORS, null, null, 0);
		$this->assertSame('login', $error);
	}
}
