<?php

class BaseTest extends PHPUnit_Framework_TestCase
{
	public function test__qa_js()
	{
		$test = qa_js('test');
		$this->assertEquals($test, "'test'");

		$test = qa_js('test', true);
		$this->assertEquals($test, "'test'");

		$test = qa_js(123);
		$this->assertEquals($test, 123);

		$test = qa_js(123, true);
		$this->assertEquals($test, "'123'");

		$test = qa_js(true);
		$this->assertEquals($test, 'true');

		$test = qa_js(true, true);
		$this->assertEquals($test, "'true'");
	}
}
