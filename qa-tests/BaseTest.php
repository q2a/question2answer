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

	public function test__convert_to_bytes()
	{
		$test = convert_to_bytes('k', 100);
		$this->assertSame(102400, $test);

		$test = convert_to_bytes('m', 100);
		$this->assertSame(104857600, $test);

		$test = convert_to_bytes('g', 100);
		$this->assertSame(107374182400, $test);

		$test = convert_to_bytes('K', 100);
		$this->assertSame(102400, $test);

		$test = convert_to_bytes('M', 100);
		$this->assertSame(104857600, $test);

		$test = convert_to_bytes('G', 100);
		$this->assertSame(107374182400, $test);

		$test = convert_to_bytes('', 100);
		$this->assertSame(100, $test);

		$test = convert_to_bytes('k', 1024);
		$this->assertSame(1048576, $test);
	}

}
