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

	public function test__applyArraySub_success()
	{
		$result = $this->dbConnection->applyArraySub('SELECT * FROM table WHERE field = 1', array());
		$this->assertSame('SELECT * FROM table WHERE field = 1', $result);

		$result = $this->dbConnection->applyArraySub('SELECT * FROM table WHERE field = ?', array(1));
		$this->assertSame('SELECT * FROM table WHERE field = ?', $result);

		$result = $this->dbConnection->applyArraySub('SELECT * FROM table WHERE field IN (?)', array(array(1)));
		$this->assertSame('SELECT * FROM table WHERE field IN (?)', $result);

		$result = $this->dbConnection->applyArraySub('SELECT * FROM table WHERE field IN (?)', array(array(1, 2)));
		$this->assertSame('SELECT * FROM table WHERE field IN (?, ?)', $result);

		$result = $this->dbConnection->applyArraySub('INSERT INTO table(field) VALUES ?', array(array(array(1))));
		$this->assertSame('INSERT INTO table(field) VALUES (?)', $result);

		$result = $this->dbConnection->applyArraySub('INSERT INTO table(field) VALUES ?', array(array(array(1), array(2))));
		$this->assertSame('INSERT INTO table(field) VALUES (?), (?)', $result);

		$result = $this->dbConnection->applyArraySub('INSERT INTO table(field1, field2) VALUES ?', array(array(array(1, 2))));
		$this->assertSame('INSERT INTO table(field1, field2) VALUES (?, ?)', $result);

		$result = $this->dbConnection->applyArraySub('INSERT INTO table(field1, field2) VALUES ?', array(array(array(1, 2), array(3, 4))));
		$this->assertSame('INSERT INTO table(field1, field2) VALUES (?, ?), (?, ?)', $result);
	}

	public function test__applyArraySub_parameter_groups_with_different_element_count_error()
	{
		$this->setExpectedException('Q2A\Database\Exceptions\SelectSpecException');
		$this->dbConnection->applyArraySub('INSERT INTO table(field1, field2) VALUES ?', array(array(array(1, 2), array(3))));
	}
}
