<?php

class BaseTest extends PHPUnit_Framework_TestCase
{
	public function test__qa_js()
	{
		$test = qa_js('test');
		$this->assertSame("'test'", $test);

		$test = qa_js('test', true);
		$this->assertSame("'test'", $test);

		$test = qa_js(123);
		$this->assertSame(123, $test);

		$test = qa_js(123, true);
		$this->assertSame("'123'", $test);

		$test = qa_js(true);
		$this->assertSame('true', $test);

		$test = qa_js(true, true);
		$this->assertSame("'true'", $test);
	}
}
