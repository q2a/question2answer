<?php

require_once QA_INCLUDE_DIR . 'db/post-create.php';

// For qa_create_new_user(), qa_set_user_blocked(), qa_delete_user()
require_once QA_INCLUDE_DIR . 'app/users-edit.php';

// For qa_handle_to_userid()
require_once QA_INCLUDE_DIR . 'app/users.php';

// For qa_post_create(), qa_post_set_selchildid(), qa_post_set_closed()
require_once QA_INCLUDE_DIR . 'app/posts.php';

// For qa_vote_set()
require_once QA_INCLUDE_DIR . 'app/votes.php';

// For qa_suspend_notifications()
require_once QA_INCLUDE_DIR . 'app/emails.php';

// For qa_question_set_status(), qa_answer_set_status(), qa_comment_set_status(), qa_question_delete(), qa_answer_delete(), qa_comment_delete()
require_once QA_INCLUDE_DIR . 'app/post-update.php';

/**
 * @group database
 */
class DbCachedOptionsTest extends \PHPUnit\Framework\TestCase
{
	protected static $user1 = array(
		'userid' => null,
		'handle' => 'user1',
		'email' => 'user1@example.com',
		'password' => 'passpass',
		'level' => QA_USER_LEVEL_BASIC,
		'confirmed' => true,
	);
	protected static $user2 = array(
		'userid' => null,
		'handle' => 'user2',
		'email' => 'user2@example.com',
		'password' => 'passpass',
		'level' => QA_USER_LEVEL_BASIC,
		'confirmed' => true,
	);

	public static function setUpBeforeClass(): void
	{
		$handlesToUserIds = qa_handles_to_userids(array(
			self::$user1['handle'],
			self::$user2['handle'],
		));
		foreach ($handlesToUserIds as $userId) {
			if (isset($userId)) {
				qa_delete_user($userId);
			}
		}

		self::$user1['userid'] = qa_create_new_user(self::$user1['email'], self::$user1['password'], self::$user1['handle'], self::$user1['level'], self::$user1['confirmed']);
		self::$user2['userid'] = qa_create_new_user(self::$user2['email'], self::$user2['password'], self::$user2['handle'], self::$user2['level'], self::$user2['confirmed']);

		qa_suspend_event_reports();
		qa_suspend_notifications();

		// Avoid issues with session_start() being called in qa_set_user_blocked()
		global $qa_logged_in_userid_checked;
		$qa_logged_in_userid_checked = true;
	}

	public function test__qa_question_create()
	{
		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionId = qa_post_create('Q', null, 'Question title: test_question_create', 'Dummy post content');

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount + 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount + 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_create_queued()
	{
		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionId = qa_post_create('Q_QUEUED', null, 'Question title: test_question_create_queued', 'Dummy post content');

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_content_without_remoderation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_content_without_remoderation', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_content($question, 'New Question title: question_set_content_without_remoderation', 'New question content', '', 'New question content', '', null, null, null, null);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_content_with_remoderation_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_content_with_remoderation_closed_question', 'Dummy post content');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_content($question, 'New Question title: question_set_content_with_remoderation_closed_question', 'New question content', '', 'New question content', '', null, null, null, null, null, null, true);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount - 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_content_with_remoderation_without_recalculating_unaqcount_unupaqcount_unselqcount()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_content_with_remoderation_without_recalculating_unaqcount_unupaqcount_unselqcount', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_selchildid($questionId, $answerId);

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_content($question, 'New Question title: question_set_content_with_remoderation_without_recalculating_unaqcount_unupaqcount_unselqcount', 'New question content', '', 'New question content', '', null, null, null, null, null, null, true);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount - 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_content_with_remoderation_recalculating_unaqcount_unupaqcount_unselqcount()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_content_with_remoderation_recalculating_unaqcount_unupaqcount_unselqcount', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_content($question, 'New Question title: question_set_content_with_remoderation_recalculating_unaqcount_unupaqcount_unselqcount', 'New question content', '', 'New question content', '', null, null, null, null, null, null, true);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount - 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount - 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount - 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount - 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_normal_to_normal()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_normal_to_normal', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_normal_to_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_normal_to_hidden', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount - 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount - 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount - 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount - 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_normal_to_hidden_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_normal_to_hidden_closed_question', 'Dummy post content');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount - 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_normal_to_hidden_without_recaulating_unaqcount_unselqcount_unupaqcount()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_normal_to_hidden_without_recaulating_unaqcount_unselqcount_unupaqcount', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_selchildid($questionId, $answerId);

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount - 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_normal_to_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_normal_to_queued', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount - 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount - 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount - 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount - 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_hidden_to_normal()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_hidden_to_normal', 'Dummy post content');

		$question = qa_post_get_full($questionId);
		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount + 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount + 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_hidden_to_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_hidden_to_hidden', 'Dummy post content');

		$question = qa_post_get_full($questionId);
		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_hidden_to_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_hidden_to_queued', 'Dummy post content');

		$question = qa_post_get_full($questionId);
		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_queued_to_normal()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_queued_to_normal', 'Dummy post content');

		$question = qa_post_get_full($questionId);
		qa_question_set_status($question, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount + 1, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount + 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount - 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_queued_to_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_queued_to_hidden', 'Dummy post content');

		$question = qa_post_get_full($questionId);
		qa_question_set_status($question, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount - 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_set_status_queued_to_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_queued_to_queued', 'Dummy post content');

		$question = qa_post_get_full($questionId);
		qa_question_set_status($question, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_set_status($question, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_question_delete()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_delete', 'Dummy post content');

		$question = qa_post_get_full($questionId);
		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_question_delete($question, self::$user1['userid'], self::$user1['handle'], null);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_update_counts_for_q(null);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_create()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_create', 'Dummy post content');
		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');
		$acount = (int)qa_opt('cache_acount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $acount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount - 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
			$this->assertSame($acount + 1, (int)qa_opt('cache_acount'));

			$this->assertSame($questionAcount + 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_create_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_create', 'Dummy post content');
		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');
		$acount = (int)qa_opt('cache_acount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_post_create('A_QUEUED', $questionId, null, 'This is the content of the answer');

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $acount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
			$this->assertSame($acount, (int)qa_opt('cache_acount'));

			$this->assertSame($questionAcount, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_content_without_remoderation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_without_remoderation', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_content($answer, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_content_with_remoderation_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_with_remoderation_closed_question', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_content($answer, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question, null, true);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_content_with_remoderation_two_answers_with_selected_and_upvoted_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_with_remoderation_two_answers_with_selected_and_upvoted_answer', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		$answer2Id = qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		qa_post_set_selchildid($questionId, $answer1Id);

		$answer1 = qa_post_get_full($answer1Id);

		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer2 = qa_post_get_full($answer2Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_content($answer2, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question, null, true);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_content_with_remoderation_with_selected_and_upvoted_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_with_remoderation_with_selected_and_upvoted_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_selchildid($questionId, $answerId);

		$answer = qa_post_get_full($answerId);

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_content($answer, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question, null, true);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_content_with_remoderation_with_closed_question_and_upvoted_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_with_remoderation_with_closed_question_and_upvoted_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$answer = qa_post_get_full($answerId);

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_content($answer, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question, null, true);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_content_with_remoderation_two_answers_with_closed_question_and_upvoted_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_with_remoderation_with_selected_and_upvoted_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');
		qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$answer = qa_post_get_full($answerId);

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_content($answer, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question, null, true);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_normal()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_normal', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_hidden', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_hidden_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_hidden_closed_question', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_hidden_one_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_hidden_one_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_hidden_two_answers_with_selected_and_upvoted_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_hidden_two_answers_with_selected_and_upvoted_answer', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		qa_post_set_selchildid($questionId, $answer1Id);

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer1, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount + 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_hidden_two_answers_with_two_upvoted_answers()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_hidden_two_answers_with_two_upvoted_answers', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		$answer2Id = qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$answer2 = qa_post_get_full($answer2Id);
		qa_vote_set($answer2, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer1, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_hidden_two_answers_with_upvoted_answer_not_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_hidden_two_answers_with_upvoted_answer_not_hidden', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		$answer2Id = qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer2 = qa_post_get_full($answer2Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer2, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_queued', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_queued_with_selected_and_upvoted_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_queued_with_selected_and_upvoted_answer', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_selchildid($questionId, $answer1Id);

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer1, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_hidden_to_normal()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_hidden_to_normal', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the first answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount - 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount + 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_hidden_to_normal_two_answers_with_upvoted_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_hidden_to_normal_two_answers_with_upvoted_answer', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		qa_answer_set_status($answer1, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer1, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount - 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount + 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote + 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_hidden_to_normal_two_answers_with_upvoted_answer_and_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_hidden_to_normal_two_answers_with_upvoted_answer_and_closed_question', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		qa_answer_set_status($answer1, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer1, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount + 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote + 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_hidden_to_normal_and_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_hidden_to_normal_and_closed_question', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the first answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount + 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_hidden_to_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_hidden_to_hidden', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the first answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_set_status_hidden_to_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_hidden_to_queued', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the first answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_set_status($answer, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_delete()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_delete', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_delete($answer, $question, self::$user1['userid'], self::$user1['handle'], null);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_delete_with_selected_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_delete_with_selected_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_selchildid($questionId, $answerId);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_delete($answer, $question, self::$user1['userid'], self::$user1['handle'], null);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_to_comment_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_closed_question', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_to_comment(
			$answer, $question['postid'], $answer['content'], $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name']
		);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_to_comment_one_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_one_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_to_comment(
			$answer, $question['postid'], $answer['content'], $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name']
		);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_to_comment_two_answers_with_selected_and_upvoted_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_two_answers_with_selected_and_upvoted_answer', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		qa_post_set_selchildid($questionId, $answer1Id);

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_to_comment(
			$answer1, $question['postid'], $answer1['content'], $answer1['format'], $answer1['content'], $answer1['notify'],
			$answer1['userid'], $answer1['handle'], $answer1['cookieid'], $question, array(), array(), $answer1['name']
		);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount + 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_to_comment_two_answers_with_upvoted_answer_not_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_two_answers_with_upvoted_answer_not_hidden', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		$answer2Id = qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer2 = qa_post_get_full($answer2Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_to_comment(
			$answer2, $question['postid'], $answer2['content'], $answer2['format'], $answer2['content'], $answer2['notify'],
			$answer2['userid'], $answer2['handle'], $answer2['cookieid'], $question, array(), array(), $answer2['name']
		);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_to_comment_one_answer_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_one_answer_queued', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_to_comment(
			$answer, $question['postid'], $answer['content'], $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name']
		);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_to_comment_one_answer_with_remoderation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_one_answer_queued', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		$questionAcount = (int)$question['acount'];
		$questionAmaxvote = (int)$question['amaxvote'];

		qa_answer_to_comment(
			$answer, $question['postid'], 'New answer content', $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name'], true
		);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount, $queuedcount, $questionAcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount + 1, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));

			$this->assertSame($questionAcount - 1, (int)$question['acount']);
			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_update_q_counts_for_a($questionId);
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_create()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_create', 'Dummy post content');

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount + 1, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_create_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_create', 'Dummy post content');

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_post_create('C_QUEUED', $questionId, null, 'This is the content of the comment');

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_content_without_remoderation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_content_without_remoderation', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_content($comment, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_content_with_remoderation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_content_without_remoderation', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_content($comment, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question, $question, null, true);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount - 1, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_to_comment_comment_count_without_remoderation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_comment_count_with_remoderation', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_answer_to_comment(
			$answer, $question['postid'], $answer['content'], $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name']
		);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount + 1, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_answer_to_comment_comment_count_with_remoderation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_comment_count_with_remoderation', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_answer_to_comment(
			$answer, $question['postid'], 'New post content', $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name'], true
		);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_normal_to_normal()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_normal_to_normal', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_normal_to_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_normal_to_hidden', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount - 1, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_normal_to_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_normal_to_queued', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount - 1, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_hidden_to_normal()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_hidden_to_normal', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount + 1, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_hidden_to_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_hidden_to_hidden', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_hidden_to_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_hidden_to_queued', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount + 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_queued_to_normal()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_queued_to_normal', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount + 1, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount - 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_queued_to_hidden()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_queued_to_hidden', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount - 1, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_set_status_queued_to_queued()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_queued_to_queued', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_set_status($comment, QA_POST_STATUS_QUEUED, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_comment_delete()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_delete', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$ccount = (int)qa_opt('cache_ccount');
		$queuedcount = (int)qa_opt('cache_queuedcount');

		qa_comment_delete($comment, $question, $question, self::$user1['userid'], self::$user1['handle'], null);

		$testValues = function () use ($ccount, $queuedcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($ccount, (int)qa_opt('cache_ccount'));
			$this->assertSame($queuedcount, (int)qa_opt('cache_queuedcount'));
		};

		$testValues();

		qa_db_ccount_update();
		qa_db_queuedcount_update();

		$testValues();
	}

	public function test__qa_post_set_selchildid_selected_one_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: post_set_selchildid_selected_one_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		qa_post_set_selchildid($questionId, $answerId);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount - 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);

		$testValues();
	}

	public function test__qa_post_set_selchildid_deselected_one_answer()
	{
		$questionId = qa_post_create('Q', null, 'Question title: post_set_selchildid_deselected_one_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_selchildid($questionId, $answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		qa_post_set_selchildid($questionId, null);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount + 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);

		$testValues();
	}

	public function test__qa_post_set_selchildid_change_selected_two_answers()
	{
		$questionId = qa_post_create('Q', null, 'Question title: post_set_selchildid_change_selected_two_answers', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		$answer2Id = qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		qa_post_set_selchildid($questionId, $answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		qa_post_set_selchildid($questionId, $answer2Id);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);

		$testValues();
	}

	public function test__qa_post_set_selchildid_selected_one_answer_with_close_on_select()
	{
		$questionId = qa_post_create('Q', null, 'Question title: post_set_selchildid_selected_one_answer_with_close_on_select', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		Q2A_TestsUtils::setCachedOption('do_close_on_select', 1);
		qa_post_set_selchildid($questionId, $answerId);
		Q2A_TestsUtils::setCachedOption('do_close_on_select', 0);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount - 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);

		$testValues();
	}

	public function test__qa_post_set_selchildid_deselected_one_answer_with_close_on_select()
	{
		$questionId = qa_post_create('Q', null, 'Question title: post_set_selchildid_deselected_one_answer', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_selchildid($questionId, $answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		Q2A_TestsUtils::setCachedOption('do_close_on_select', 1);
		qa_post_set_selchildid($questionId, null);
		Q2A_TestsUtils::setCachedOption('do_close_on_select', 0);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount + 1, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);

		$testValues();
	}

	public function test__qa_post_set_selchildid_change_selected_two_answers_with_close_on_select()
	{
		$questionId = qa_post_create('Q', null, 'Question title: post_set_selchildid_change_selected_two_answers', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		$answer2Id = qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		qa_post_set_selchildid($questionId, $answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$qcount = (int)qa_opt('cache_qcount');
		$unaqcount = (int)qa_opt('cache_unaqcount');
		$unselqcount = (int)qa_opt('cache_unselqcount');
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		Q2A_TestsUtils::setCachedOption('do_close_on_select', 1);
		qa_post_set_selchildid($questionId, $answer2Id);
		Q2A_TestsUtils::setCachedOption('do_close_on_select', 0);

		$testValues = function () use ($qcount, $unaqcount, $unselqcount, $unupaqcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($qcount, (int)qa_opt('cache_qcount'));
			$this->assertSame($unaqcount, (int)qa_opt('cache_unaqcount'));
			$this->assertSame($unselqcount, (int)qa_opt('cache_unselqcount'));
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_question', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		qa_vote_set($question, self::$user1['userid'], self::$user1['handle'], null, 1);

		$testValues = function () use ($unupaqcount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));
		};

		$testValues();

		qa_update_counts_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_zero_to_one_with_upvote()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_zero_to_one_with_upvote', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount - 1, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote + 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_zero_to_one_with_upvote_and_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_zero_to_one_with_upvote_and_closed_question', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote + 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_zero_to_two_with_upvote()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_zero_to_one_with_upvote', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, -1);

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user2['userid'], self::$user2['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount - 1, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote + 2, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_zero_to_zero_with_downvote()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_zero_to_zero_with_downvote', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, -1);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_zero_to_zero_with_downvote_and_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_zero_to_zero_with_downvote_and_closed_question', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, -1);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_two_to_one_with_nilvote()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_zero_to_one_with_upvote', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user2['userid'], self::$user2['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 0);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_two_to_one_with_nilvote_with_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_zero_to_one_with_upvote_and_closed_question', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user2['userid'], self::$user2['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 0);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_one_to_zero_with_downvote()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_one_to_zero_with_downvote', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user2['userid'], self::$user2['handle'], null, -1);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_one_to_zero_with_downvote_and_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_one_to_zero_with_downvote_and_closed_question', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user2['userid'], self::$user2['handle'], null, -1);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_one_answer_amaxvote_one_to_zero_with_nilvote()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_one_answer_amaxvote_one_to_zero_with_nilvote', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$answer = qa_post_get_full($answerId);
		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer, self::$user1['userid'], self::$user1['handle'], null, 0);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_two_answers_amaxvote_one_to_one_with_downvote_without_recalculation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_two_answers_amaxvote_one_to_one_with_downvote_without_recalculation', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		$answer2Id = qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$answer2 = qa_post_get_full($answer2Id);
		qa_vote_set($answer2, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		$questionAmaxvote = (int)$question['amaxvote'];

		qa_vote_set($answer1, self::$user2['userid'], self::$user2['handle'], null, -1);

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_two_answers_amaxvote_one_to_zero_with_downvote_with_recalculation()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_two_answers_amaxvote_one_to_zero_with_downvote_with_recalculation', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		qa_vote_set($answer1, self::$user2['userid'], self::$user2['handle'], null, -1);

		$questionAmaxvote = (int)$question['amaxvote'];

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount + 1, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_vote_set_answer_two_answers_amaxvote_one_to_zero_with_downvote_with_recalculation_and_closed_question()
	{
		$questionId = qa_post_create('Q', null, 'Question title: vote_set_answer_two_answers_amaxvote_one_to_zero_with_downvote_with_recalculation_and_closed_question', 'Dummy post content');
		$answer1Id = qa_post_create('A', $questionId, null, 'This is the content of the first answer');
		qa_post_create('A', $questionId, null, 'This is the content of the second answer');

		qa_post_set_closed($questionId, true, null, 'Irrelevant question', self::$user1['userid']);

		$answer1 = qa_post_get_full($answer1Id);
		qa_vote_set($answer1, self::$user1['userid'], self::$user1['handle'], null, 1);

		$question = qa_post_get_full($questionId);
		$answer1 = qa_post_get_full($answer1Id);

		Q2A_TestsUtils::removeAllCachedOptions();
		$unupaqcount = (int)qa_opt('cache_unupaqcount');

		qa_vote_set($answer1, self::$user2['userid'], self::$user2['handle'], null, -1);

		$questionAmaxvote = (int)$question['amaxvote'];

		$testValues = function () use ($unupaqcount, $questionAmaxvote, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($unupaqcount, (int)qa_opt('cache_unupaqcount'));

			$this->assertSame($questionAmaxvote - 1, (int)$question['amaxvote']);
		};

		$testValues();

		qa_update_counts_for_q($questionId);
		qa_db_amaxvote_update_for_q($questionId);

		$testValues();
	}

	public function test__qa_create_new_user_basic_user()
	{
		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_create_new_user('new_user_basic@example.com', 'passpass', 'new_user_basic', QA_USER_LEVEL_BASIC, true);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount + 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_create_new_user_approved_user()
	{
		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_create_new_user('new_user_app@example.com', 'passpass', 'new_user_app', QA_USER_LEVEL_APPROVED, true);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_block_basic()
	{
		$userId = qa_create_new_user('user_block_basic@example.com', 'passpass', 'user_block_basic', QA_USER_LEVEL_BASIC, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_blocked($userId, $userAccount, true);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount - 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_block_approved()
	{
		$userId = qa_create_new_user('user_block_app@example.com', 'passpass', 'user_block_app', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_blocked($userId, $userAccount, true);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_unblock_basic()
	{
		$userId = qa_create_new_user('user_unblock_basic@example.com', 'passpass', 'user_unblock_basic', QA_USER_LEVEL_BASIC, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_blocked($userId, $userAccount, false);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount + 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_unblock_approved()
	{
		$userId = qa_create_new_user('user_unblock_app@example.com', 'passpass', 'user_unblock_app', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_blocked($userId, $userAccount, false);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_unblocked_basic_user_with_user_account()
	{
		$userId = qa_create_new_user('unb_bas_with_acc@example.com', 'passpass', 'unb_bas_with_acc', QA_USER_LEVEL_BASIC, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_delete_user($userId, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount - 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_unblocked_approved_user_with_user_account()
	{
		$userId = qa_create_new_user('unb_app_with_acc@example.com', 'passpass', 'unb_app_with_acc', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_delete_user($userId, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_unblocked_basic_user_without_user_account()
	{
		$userId = qa_create_new_user('unb_bas_without_acc@example.com', 'passpass', 'unb_bas_without_acc', QA_USER_LEVEL_BASIC, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_delete_user($userId);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount - 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_unblocked_approved_user_without_user_account()
	{
		$userId = qa_create_new_user('unb_app_without_acc@example.com', 'passpass', 'unb_app_without_acc', QA_USER_LEVEL_APPROVED, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_delete_user($userId);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_blocked_basic_user_with_user_account()
	{
		$userId = qa_create_new_user('blo_bas_with_acc@example.com', 'passpass', 'blo_bas_with_acc', QA_USER_LEVEL_BASIC, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_delete_user($userId, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_blocked_approved_user_with_user_account()
	{
		$userId = qa_create_new_user('blo_app_with_acc@example.com', 'passpass', 'blo_app_with_acc', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_delete_user($userId, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_blocked_basic_user_without_user_account()
	{
		$userId = qa_create_new_user('blo_bas_without_acc@example.com', 'passpass', 'blo_bas_without_acc', QA_USER_LEVEL_BASIC, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_delete_user($userId);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_blocked_approved_user_without_user_account()
	{
		$userId = qa_create_new_user('blo_app_without_acc@example.com', 'passpass', 'blo_app_without_acc', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_delete_user($userId);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_unblocked_basic_to_approved_with_user_account()
	{
		$userId = qa_create_new_user('unb_upg_with_acc@example.com', 'passpass', 'unb_upg_with_acc', QA_USER_LEVEL_BASIC, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $userAccount['handle'], QA_USER_LEVEL_APPROVED, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount - 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_unblocked_approved_to_basic_with_user_account()
	{
		$userId = qa_create_new_user('unb_dng_with_acc@example.com', 'passpass', 'unb_dng_with_acc', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $userAccount['handle'], QA_USER_LEVEL_BASIC, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount + 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_unblocked_approved_to_admin_with_user_account()
	{
		$userId = qa_create_new_user('unb_upg_with_acc@example.com', 'passpass', 'unb_upg_with_acc', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $userAccount['handle'], QA_USER_LEVEL_ADMIN, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_blocked_basic_to_approved_with_user_account()
	{
		$userId = qa_create_new_user('blo_upg_with_acc@example.com', 'passpass', 'blo_upg_with_acc', QA_USER_LEVEL_BASIC, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $userAccount['handle'], QA_USER_LEVEL_APPROVED, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_blocked_approved_to_basic_with_user_account()
	{
		$userId = qa_create_new_user('blo_dng_with_acc@example.com', 'passpass', 'blo_dng_with_acc', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $userAccount['handle'], QA_USER_LEVEL_BASIC, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_blocked_approved_to_admin_with_user_account()
	{
		$userId = qa_create_new_user('unb_noc_with_acc@example.com', 'passpass', 'unb_noc_with_acc', QA_USER_LEVEL_APPROVED, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $userAccount['handle'], QA_USER_LEVEL_ADMIN, $userAccount);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_unblocked_basic_to_approved_without_user_account()
	{
		$handle = 'unb_upg_without_acc';
		$level = QA_USER_LEVEL_BASIC;

		$userId = qa_create_new_user('unb_upg_without_acc@example.com', 'passpass', $handle, $level, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $handle, QA_USER_LEVEL_APPROVED, $level);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount - 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_unblocked_approved_to_basic_without_user_account()
	{
		$handle = 'unb_dng_without_acc';
		$level = QA_USER_LEVEL_APPROVED;

		$userId = qa_create_new_user('unb_dng_without_acc@example.com', 'passpass', $handle, $level, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $handle, QA_USER_LEVEL_BASIC, $level);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount + 1, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_unblocked_approved_to_admin_without_user_account()
	{
		$handle = 'unb_noc_without_acc';
		$level = QA_USER_LEVEL_APPROVED;

		$userId = qa_create_new_user('unb_noc_without_acc@example.com', 'passpass', $handle, $level, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $handle, QA_USER_LEVEL_ADMIN, $level);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_blocked_basic_to_approved_without_user_account()
	{
		$handle = 'blo_upg_without_acc';
		$level = QA_USER_LEVEL_BASIC;

		$userId = qa_create_new_user('blo_upg_without_acc@example.com', 'passpass', $handle, $level, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $handle, QA_USER_LEVEL_APPROVED, $level);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_blocked_approved_to_basic_without_user_account()
	{
		$handle = 'blo_dng_without_acc';
		$level = QA_USER_LEVEL_APPROVED;

		$userId = qa_create_new_user('blo_dng_without_acc@example.com', 'passpass', $handle, $level, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $handle, QA_USER_LEVEL_BASIC, $level);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_set_user_level_blocked_approved_to_admin_without_user_account()
	{
		$handle = 'unb_noc_without_acc';
		$level = QA_USER_LEVEL_APPROVED;

		$userId = qa_create_new_user('unb_noc_without_acc@example.com', 'passpass', $handle, $level, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		qa_set_user_blocked($userId, $userAccount, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$uapprovecount = (int)qa_opt('cache_uapprovecount');

		qa_set_user_level($userId, $handle, QA_USER_LEVEL_ADMIN, $level);

		$testValues = function () use ($uapprovecount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($uapprovecount, (int)qa_opt('cache_uapprovecount'));
		};

		$testValues();

		qa_db_uapprovecount_update();

		$testValues();
	}

	public function test__qa_delete_user_with_userpoints_record()
	{
		$userId = qa_create_new_user('del_with_up@example.com', 'passpass', 'del_with_up', QA_USER_LEVEL_BASIC, true);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$userpointscount = (int)qa_opt('cache_userpointscount');

		qa_delete_user($userId, $userAccount);

		$testValues = function () use ($userpointscount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($userpointscount - 1, (int)qa_opt('cache_userpointscount'));
		};

		$testValues();

		qa_db_userpointscount_update();

		$testValues();
	}

	public function test__qa_delete_user_without_userpoints_record()
	{
		$userId = qa_create_new_user('del_without_up@example.com', 'passpass', 'del_without_up', QA_USER_LEVEL_BASIC, true);

		qa_db_query_sub('DELETE FROM `^userpoints` WHERE userid = $', $userId);

		qa_db_userpointscount_update(-1);

		$userAccount = qa_db_single_select(qa_db_user_account_selectspec($userId, true));

		Q2A_TestsUtils::removeAllCachedOptions();
		$userpointscount = (int)qa_opt('cache_userpointscount');

		qa_delete_user($userId, $userAccount);

		$testValues = function () use ($userpointscount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($userpointscount, (int)qa_opt('cache_userpointscount'));
		};

		$testValues();

		qa_db_userpointscount_update();

		$testValues();
	}

	public function test__qa_db_points_update_ifuser_user_with_userpoints_record()
	{
		$userId = qa_create_new_user('puif_with_up@example.com', 'passpass', 'puif_with_up', QA_USER_LEVEL_BASIC, true);

		Q2A_TestsUtils::removeAllCachedOptions();
		$userpointscount = (int)qa_opt('cache_userpointscount');

		qa_db_points_update_ifuser($userId, null);

		$testValues = function () use ($userpointscount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($userpointscount, (int)qa_opt('cache_userpointscount'));
		};

		$testValues();

		qa_db_userpointscount_update();

		$testValues();
	}

	public function test__qa_db_points_update_ifuser_user_without_userpoints_record()
	{
		$userId = qa_create_new_user('puif_without_up@example.com', 'passpass', 'puif_without_up', QA_USER_LEVEL_BASIC, true);

		qa_db_query_sub('DELETE FROM `^userpoints` WHERE userid = $', $userId);

		qa_db_userpointscount_update(-1);

		Q2A_TestsUtils::removeAllCachedOptions();
		$userpointscount = (int)qa_opt('cache_userpointscount');

		qa_db_points_update_ifuser($userId, null);

		$testValues = function () use ($userpointscount) {
			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($userpointscount + 1, (int)qa_opt('cache_userpointscount'));
		};

		$testValues();

		qa_db_userpointscount_update();

		$testValues();
	}

	public function test__qa_flag_post_for_flaggedcount()
	{
		$questionId = qa_post_create('Q', null, 'Question title: flag_post_for_flaggedcount', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount + 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount + 1, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_flag_twice_post_for_flaggedcount()
	{
		$questionId = qa_post_create('Q', null, 'Question title: flag_post_for_flaggedcount', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_unflag_post_for_flaggedcount()
	{
		$questionId = qa_post_create('Q', null, 'Question title: unflag_post_for_flaggedcount', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_flag_clear($question, self::$user1['userid'], self::$user1['handle'], null);

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount - 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount - 1, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_unflag_twice_post_for_flaggedcount()
	{
		$questionId = qa_post_create('Q', null, 'Question title: unflag_post_for_flaggedcount', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);

		qa_flag_clear($question, self::$user1['userid'], self::$user1['handle'], null);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_flag_clear($question, self::$user1['userid'], self::$user1['handle'], null);

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_question_set_content_without_remoderation_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_content_without_remoderation_without_flags', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_question_set_content($question, 'New Question title: question_set_content_without_remoderation_without_flags', 'New question content', '', 'New question content', '', null, null, null, null);

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_question_set_content_without_remoderation_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_content_without_remoderation_with_flags', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_question_set_content($question, 'New Question title: question_set_content_without_remoderation_with_flags', 'New question content', '', 'New question content', '', null, null, null, null);

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_question_set_content_with_remoderation_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_content_with_remoderation_without_flags', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_question_set_content($question, 'New Question title: question_set_content_with_remoderation_without_flags', 'New question content', '', 'New question content', '', null, null, null, null, null, null, true);

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_question_set_content_with_remoderation_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_content_with_remoderation_with_flags', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_question_set_content($question, 'New Question title: question_set_content_with_remoderation_with_flags', 'New question content', '', 'New question content', '', null, null, null, null, null, null, true);

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount - 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_question_set_status_normal_to_hidden_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_normal_to_hidden_without_flags', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_question_set_status_hidden_to_normal_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_hidden_to_normal_without_flags', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_question_set_status($question, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_question_set_status_normal_to_hidden_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_normal_to_hidden_with_flags', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount - 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_question_set_status_hidden_to_normal_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: question_set_status_hidden_to_normal_with_flags', 'Dummy post content');

		$question = qa_post_get_full($questionId);

		qa_flag_set_tohide($question, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);

		qa_question_set_status($question, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$question = qa_post_get_full($questionId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$questionFlagcount = (int)$question['flagcount'];

		qa_question_set_status($question, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, array(), array());

		$testValues = function () use ($flaggedcount, $questionFlagcount, $questionId) {
			$question = qa_post_get_full($questionId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount + 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($questionFlagcount, (int)$question['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($questionId);

		$testValues();
	}

	public function test__qa_answer_set_content_without_remoderation_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_without_remoderation_without_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_set_content($answer, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question);

		$testValues = function () use ($flaggedcount, $answerFlagcount, $answerId) {
			$answer = qa_post_get_full($answerId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($answerFlagcount, (int)$answer['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_set_content_without_remoderation_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_without_remoderation_without_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_flag_set_tohide($answer, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_set_content($answer, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question);

		$testValues = function () use ($flaggedcount, $answerFlagcount, $answerId) {
			$answer = qa_post_get_full($answerId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($answerFlagcount, (int)$answer['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_set_content_with_remoderation_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_content_with_remoderation_without_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_set_content($answer, 'This is the new content of the answer', '', 'This is the new content of the answer', null, null, null, null, $question, null, true);

		$testValues = function () use ($flaggedcount, $answerFlagcount, $answerId) {
			$answer = qa_post_get_full($answerId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($answerFlagcount, (int)$answer['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_hidden_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_hidden_without_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($flaggedcount, $answerFlagcount, $answerId) {
			$answer = qa_post_get_full($answerId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($answerFlagcount, (int)$answer['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_set_status_hidden_to_normal_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_hidden_to_normalwithout_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_set_status($answer, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($flaggedcount, $answerFlagcount, $answerId) {
			$answer = qa_post_get_full($answerId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($answerFlagcount, (int)$answer['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_set_status_normal_to_hidden_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_normal_to_hidden_with_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_flag_set_tohide($answer, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($flaggedcount, $answerFlagcount, $answerId) {
			$answer = qa_post_get_full($answerId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount - 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($answerFlagcount, (int)$answer['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_set_status_hidden_to_normal_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_set_status_hidden_to_normal_with_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_flag_set_tohide($answer, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_answer_set_status($answer, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_set_status($answer, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, array());

		$testValues = function () use ($flaggedcount, $answerFlagcount, $answerId) {
			$answer = qa_post_get_full($answerId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount + 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($answerFlagcount, (int)$answer['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_comment_set_content_without_remoderation_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_content_without_remoderation_without_flags', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$commentFlagcount = (int)$comment['flagcount'];

		qa_comment_set_content($comment, 'This is the new content of the comment', '', 'This is the new content of the comment', null, null, null, null, $question, $question);

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($commentId);

		$testValues();
	}

	public function test__qa_comment_set_content_without_remoderation_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_content_without_remoderation_with_flags', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_flag_set_tohide($comment, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$commentFlagcount = (int)$comment['flagcount'];

		qa_comment_set_content($comment, 'This is the new content of the comment', '', 'This is the new content of the comment', null, null, null, null, $question, $question);

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($commentId);

		$testValues();
	}

	public function test__qa_comment_set_content_with_remoderation_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_content_with_remoderation_without_flags', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$commentFlagcount = (int)$comment['flagcount'];

		qa_comment_set_content($comment, 'This is the new content of the comment', '', 'This is the new content of the comment', null, null, null, null, $question, $question, null, true);

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($commentId);

		$testValues();
	}

	public function test__qa_comment_set_content_with_remoderation_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_content_with_remoderation_with_flags', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_flag_set_tohide($comment, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$commentFlagcount = (int)$comment['flagcount'];

		qa_comment_set_content($comment, 'This is the new content of the comment', '', 'This is the new content of the comment', null, null, null, null, $question, $question, null, true);

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount - 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($commentId);

		$testValues();
	}

	public function test__qa_answer_to_comment_without_remoderation__without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_without_remoderation__without_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_to_comment(
			$answer, $question['postid'], $answer['content'], $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name']
		);

		$commentId = $answerId;
		$commentFlagcount = $answerFlagcount;

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_to_comment_without_remoderation__with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_without_remoderation__with_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_flag_set_tohide($answer, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_to_comment(
			$answer, $question['postid'], $answer['content'], $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name']
		);

		$commentId = $answerId;
		$commentFlagcount = $answerFlagcount;

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_to_comment_with_remoderation__without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_with_remoderation__without_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_to_comment(
			$answer, $question['postid'], 'Content change', $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name'], true
		);

		$commentId = $answerId;
		$commentFlagcount = $answerFlagcount;

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_answer_to_comment_with_remoderation__with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: answer_to_comment_with_remoderation__with_flags', 'Dummy post content');
		$answerId = qa_post_create('A', $questionId, null, 'This is the content of the answer');

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		qa_flag_set_tohide($answer, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$answer = qa_post_get_full($answerId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$answerFlagcount = (int)$answer['flagcount'];

		qa_answer_to_comment(
			$answer, $question['postid'], 'Content change', $answer['format'], $answer['content'], $answer['notify'],
			$answer['userid'], $answer['handle'], $answer['cookieid'], $question, array(), array(), $answer['name'], true
		);

		$commentId = $answerId;
		$commentFlagcount = $answerFlagcount;

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount - 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($answerId);

		$testValues();
	}

	public function test__qa_comment_set_status_normal_to_hidden_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_normal_to_hidden_without_flags', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$commentFlagcount = (int)$comment['flagcount'];

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($commentId);

		$testValues();
	}

	public function test__qa_comment_set_status_hidden_to_normal_without_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_hidden_to_normalwithout_flags', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$commentFlagcount = (int)$comment['flagcount'];

		qa_comment_set_status($comment, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($commentId);

		$testValues();
	}

	public function test__qa_comment_set_status_normal_to_hidden_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_normal_to_hidden_with_flags', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_flag_set_tohide($comment, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$commentFlagcount = (int)$comment['flagcount'];

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount - 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($commentId);

		$testValues();
	}

	public function test__qa_comment_set_status_hidden_to_normal_with_flags()
	{
		$questionId = qa_post_create('Q', null, 'Question title: comment_set_status_hidden_to_normal_with_flags', 'Dummy post content');
		$commentId = qa_post_create('C', $questionId, null, 'This is the content of the comment');

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_flag_set_tohide($comment, self::$user1['userid'], self::$user1['handle'], null, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		qa_comment_set_status($comment, QA_POST_STATUS_HIDDEN, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$question = qa_post_get_full($questionId);
		$comment = qa_post_get_full($commentId);

		Q2A_TestsUtils::removeAllCachedOptions();
		$flaggedcount = (int)qa_opt('cache_flaggedcount');

		$commentFlagcount = (int)$comment['flagcount'];

		qa_comment_set_status($comment, QA_POST_STATUS_NORMAL, self::$user1['userid'], self::$user1['handle'], null, $question, $question);

		$testValues = function () use ($flaggedcount, $commentFlagcount, $commentId) {
			$comment = qa_post_get_full($commentId);

			Q2A_TestsUtils::removeAllCachedOptions();
			$this->assertSame($flaggedcount + 1, (int)qa_opt('cache_flaggedcount'));

			$this->assertSame($commentFlagcount, (int)$comment['flagcount']);
		};

		$testValues();

		qa_db_flaggedcount_update();
		qa_db_post_recount_flags($commentId);

		$testValues();
	}
}
