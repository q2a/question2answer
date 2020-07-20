<?php

use Q2A\Database\DbConnection;

class DbConnectionTest extends PHPUnit_Framework_TestCase
{
	/** @var DbConnection */
	private $dbConnection;

	protected function setUp()
	{
		$this->dbConnection = new DbConnection();
	}

	public function test__expandQueryParameters_success()
	{
		$result = $this->dbConnection->expandQueryParameters('SELECT * FROM table WHERE field = 1', []);
		$expected = ['SELECT * FROM table WHERE field = 1', []];
		$this->assertSame($expected, $result);

		$result = $this->dbConnection->expandQueryParameters('SELECT * FROM table WHERE field = ?', [1]);
		$expected = ['SELECT * FROM table WHERE field = ?', [1]];
		$this->assertSame($expected, $result);

		$result = $this->dbConnection->expandQueryParameters('SELECT * FROM table WHERE field IN (?)', [[1]]);
		$expected = ['SELECT * FROM table WHERE field IN (?)', [1]];
		$this->assertSame($expected, $result);

		$result = $this->dbConnection->expandQueryParameters('SELECT * FROM table WHERE field IN (?)', [[1, 2]]);
		$expected = ['SELECT * FROM table WHERE field IN (?, ?)', [1, 2]];
		$this->assertSame($expected, $result);

		$result = $this->dbConnection->expandQueryParameters('INSERT INTO table(field) VALUES ?', [[ [1] ]]);
		$expected = ['INSERT INTO table(field) VALUES (?)', [1]];
		$this->assertSame($expected, $result);

		$result = $this->dbConnection->expandQueryParameters('INSERT INTO table(field) VALUES ?', [[ [1], [2] ]]);
		$expected = ['INSERT INTO table(field) VALUES (?), (?)', [1, 2]];
		$this->assertSame($expected, $result);

		$result = $this->dbConnection->expandQueryParameters('INSERT INTO table(field1, field2) VALUES ?', [[ [1, 2] ]]);
		$expected = ['INSERT INTO table(field1, field2) VALUES (?, ?)', [1, 2]];
		$this->assertSame($expected, $result);

		$result = $this->dbConnection->expandQueryParameters('INSERT INTO table(field1, field2) VALUES ?', [[ [1, 2], [3, 4] ]]);
		$expected = ['INSERT INTO table(field1, field2) VALUES (?, ?), (?, ?)', [1, 2, 3, 4]];
		$this->assertSame($expected, $result);
	}

	public function test__expandQueryParameters_incorrect_groups_error()
	{
		$this->setExpectedException('Q2A\Database\Exceptions\SelectSpecException');
		$this->dbConnection->expandQueryParameters('INSERT INTO table(field1, field2) VALUES ?', [[ [1, 2], [3] ]]);
	}
}
