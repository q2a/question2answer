<?php
require_once QA_INCLUDE_DIR.'app/format.php';
require_once QA_INCLUDE_DIR.'app/options.php';

class AppFormatTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test basic number formatting (no compact numbers)
	 */
	public function test__qa_format_number()
	{
		// set options/lang cache to bypass database
		global $qa_options_cache, $qa_phrases_custom;
		$qa_options_cache['show_compact_numbers'] = '0';

		$qa_phrases_custom['main']['_decimal_point'] = '.';
		$qa_phrases_custom['main']['_thousands_separator'] = ',';

		$this->assertSame( '5.5', qa_format_number(5.452, 1) );
		$this->assertSame( '5',   qa_format_number(5.452, 0) );

		$this->assertSame('9,123', qa_format_number(9123, 0));
		$this->assertSame('9,123.0', qa_format_number(9123, 1));

		// $compact parameter should have no effect here
		$this->assertSame('9,123.0', qa_format_number(9123, 1, true));
		$this->assertSame('123,456,789', qa_format_number(123456789, 0, true));

		// change separators
		$qa_phrases_custom['main']['_decimal_point'] = ',';
		$qa_phrases_custom['main']['_thousands_separator'] = '.';

		$this->assertSame('5,5', qa_format_number(5.452, 1));
		$this->assertSame('5', qa_format_number(5.452, 0));
		$this->assertSame('9.123', qa_format_number(9123, 0));
		$this->assertSame('9.123,0', qa_format_number(9123, 1));
	}

	/**
	 * Test number formatting including compact numbers (e.g. 1.3k)
	 */
	public function test__qa_format_number__compact()
	{
		// set options/lang cache to bypass database
		global $qa_options_cache, $qa_phrases_custom;
		$qa_options_cache['show_compact_numbers'] = '1';

		$qa_phrases_custom['main']['_decimal_point'] = '.';
		$qa_phrases_custom['main']['_thousands_separator'] = ',';
		$qa_phrases_custom['main']['_thousands_suffix'] = 'k';
		$qa_phrases_custom['main']['_millions_suffix'] = 'm';

		$this->assertSame('9.1k', qa_format_number(9123, 1, true));
		$this->assertSame('9k', qa_format_number(9123, 0, true));
		$this->assertSame('9k', qa_format_number(9000, 0, true));
		$this->assertSame('9k', qa_format_number(9000, 1, true));

		$this->assertSame('123m', qa_format_number(123456789, 0, true));
		$this->assertSame('235m', qa_format_number(234567891, 0, true));
		$this->assertSame('123m', qa_format_number(123456789, 1, true));
		$this->assertSame('235m', qa_format_number(234567891, 1, true));

		$this->assertSame('9,000', qa_format_number(9000, 0, false));
		$this->assertSame('123,456,789', qa_format_number(123456789, 0, false));

		// change compact suffixes
		$qa_phrases_custom['main']['_thousands_suffix'] = 'th';
		$qa_phrases_custom['main']['_millions_suffix'] = 'mi';

		$this->assertSame('9.1th', qa_format_number(9123, 1, true));
		$this->assertSame('9th', qa_format_number(9123, 0, true));
		$this->assertSame('123mi', qa_format_number(123456789, 0, true));
	}
}
