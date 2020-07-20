<?php

use Q2A\Database\DbQueryHelper;

class DbQueryHelperTest extends PHPUnit_Framework_TestCase
{
	/** @var DbQueryHelper */
	private $helper;

	protected function setUp()
	{
		$this->helper = new DbQueryHelper();
	}

	public function test__expandParameters_success()
	{
		$result = $this->helper->expandParameters('SELECT * FROM table WHERE field=1', []);
		$expected = ['SELECT * FROM table WHERE field=1', []];
		$this->assertSame($expected, $result);

		$result = $this->helper->expandParameters('SELECT * FROM table WHERE field=?', [1]);
		$expected = ['SELECT * FROM table WHERE field=?', [1]];
		$this->assertSame($expected, $result);

		$result = $this->helper->expandParameters('SELECT * FROM table WHERE field=#', [1]);
		$expected = ['SELECT * FROM table WHERE field=?', [1]];
		$this->assertSame($expected, $result);

		$result = $this->helper->expandParameters('SELECT * FROM table WHERE field IN (?)', [[1]]);
		$expected = ['SELECT * FROM table WHERE field IN (?)', [1]];
		$this->assertSame($expected, $result);

		$result = $this->helper->expandParameters('SELECT * FROM table WHERE field IN (?)', [[1, 2]]);
		$expected = ['SELECT * FROM table WHERE field IN (?, ?)', [1, 2]];
		$this->assertSame($expected, $result);

		$result = $this->helper->expandParameters('INSERT INTO table(field) VALUES ?', [[ [1] ]]);
		$expected = ['INSERT INTO table(field) VALUES (?)', [1]];
		$this->assertSame($expected, $result);

		$result = $this->helper->expandParameters('INSERT INTO table(field) VALUES ?', [[ [1], [2] ]]);
		$expected = ['INSERT INTO table(field) VALUES (?), (?)', [1, 2]];
		$this->assertSame($expected, $result);

		$result = $this->helper->expandParameters('INSERT INTO table(field1, field2) VALUES ?', [[ [1, 2] ]]);
		$expected = ['INSERT INTO table(field1, field2) VALUES (?, ?)', [1, 2]];
		$this->assertSame($expected, $result);

		$result = $this->helper->expandParameters('INSERT INTO table(field1, field2) VALUES ?', [[ [1, 2], [3, 4] ]]);
		$expected = ['INSERT INTO table(field1, field2) VALUES (?, ?), (?, ?)', [1, 2, 3, 4]];
		$this->assertSame($expected, $result);
	}

	public function test__expandParameters_incorrect_groups_error()
	{
		$this->setExpectedException('Q2A\Database\Exceptions\SelectSpecException');
		$this->helper->expandParameters('INSERT INTO table(field1, field2) VALUES ?', [[ [1, 2], [3] ]]);
	}

	public function test__applyTableSub()
	{
		$result = $this->helper->applyTableSub('SELECT * FROM ^options');
		$this->assertSame('SELECT * FROM qa_options', $result);

		$result = $this->helper->applyTableSub('SELECT * FROM ^users WHERE userid=?');
		$this->assertSame('SELECT * FROM qa_users WHERE userid=?', $result);
	}

	public function test__applyTableSub_users_prefix()
	{
		define('QA_MYSQL_USERS_PREFIX', 'base_');

		$result = $this->helper->applyTableSub('SELECT * FROM ^options');
		$this->assertSame('SELECT * FROM qa_options', $result);

		$result = $this->helper->applyTableSub('SELECT * FROM ^users WHERE userid=?');
		$this->assertSame('SELECT * FROM base_users WHERE userid=?', $result);
	}
}
