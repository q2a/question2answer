<?php

class BaseTest extends PHPUnit_Framework_TestCase
{
	public function test__qa_js()
	{
		$this->assertSame("'test'", qa_js('test'));
		$this->assertSame("'test'", qa_js('test', true));

		$this->assertSame(123,      qa_js(123));
		$this->assertSame("'123'",  qa_js(123, true));

		$this->assertSame('true',   qa_js(true));
		$this->assertSame("'true'", qa_js(true, true));
	}

	public function test__convert_to_bytes()
	{
		$this->assertSame(      102400, convert_to_bytes('k', 100));
		$this->assertSame(   104857600, convert_to_bytes('m', 100));
		$this->assertSame(107374182400, convert_to_bytes('g', 100));

		$this->assertSame(      102400, convert_to_bytes('K', 100));
		$this->assertSame(   104857600, convert_to_bytes('M', 100));
		$this->assertSame(107374182400, convert_to_bytes('G', 100));

		$this->assertSame(         100, convert_to_bytes('',  100));
		$this->assertSame(     1048576, convert_to_bytes('k', 1024));
	}

	public function test__qa_q_request()
	{
		// set options/lang cache to bypass database
		global $qa_options_cache;

		$title1 = 'How much wood would a woodchuck chuck if a woodchuck could chuck wood?';
		$title2 = 'Țĥé qũīçĶ ßřǭŴƞ Ƒöŧ ǰÙƢƥş ØƯĘŕ ƬĦȨ ĿÆƶȳ Ƌơǥ';

		$qa_options_cache['q_urls_title_length'] = 50;
		$qa_options_cache['q_urls_remove_accents'] = false;
		$expected1 = '1234/much-wood-would-woodchuck-chuck-woodchuck-could-chuck-wood';
		$expected2 = '5678/țĥé-qũīçķ-ßřǭŵƞ-ƒöŧ-ǰùƣƥş-øưęŕ-ƭħȩ-ŀæƶȳ-ƌơǥ';

		$this->assertSame($expected1, qa_q_request(1234, $title1));
		$this->assertSame($expected2, qa_q_request(5678, $title2));

		$qa_options_cache['q_urls_remove_accents'] = true;
		$expected3 = '9000/the-quick-ssrown-fot-juoips-ouer-the-laezy-dog';

		$this->assertSame($expected3, qa_q_request(9000, $title2));
	}
}
