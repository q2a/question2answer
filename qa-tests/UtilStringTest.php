<?php
require_once QA_INCLUDE_DIR.'qa-util-string.php';

class UtilStringTest extends PHPUnit_Framework_TestCase
{
	private $strBasic = 'So I tied an onion to my belt, which was the style at the time.';
	private $strAccents = 'Țĥé qũīçĶ ßřǭŴƞ Ƒöŧ ǰÙƢƥş ØƲĘŕ ƮĦȨ ĿÆƶȳ Ƌơǥ';
	private $blockWordString = 't*d o*n b*t style';

	function test__qa_string_to_words()
	{
		$test1 = qa_string_to_words($this->strBasic);
		$expected1 = array('so', 'i', 'tied', 'an', 'onion', 'to', 'my', 'belt', 'which', 'was', 'the', 'style', 'at', 'the', 'time');

		$test2 = qa_string_to_words($this->strBasic, false);
		$expected2 = array('So', 'I', 'tied', 'an', 'onion', 'to', 'my', 'belt', 'which', 'was', 'the', 'style', 'at', 'the', 'time');

		$this->assertEquals($expected1, $test1);
		$this->assertEquals($expected2, $test2);
	}

	function test__qa_string_remove_accents()
	{
		$test = qa_string_remove_accents($this->strAccents);
		$expected = 'The quicK ssroWn Fot jUOIps OVEr THE LAEzy Dog';

		$this->assertEquals($expected, $test);
	}

	function test__qa_tags_to_tagstring()
	{
		$test = qa_tags_to_tagstring( array('Hello', 'World') );
		$expected = 'Hello,World';

		$this->assertEquals($expected, $test);
	}

	function test__qa_tagstring_to_tags()
	{
		$test = qa_tagstring_to_tags('hello,world');
		$expected = array('hello', 'world');

		$this->assertEquals($expected, $test);
	}

	function test__qa_shorten_string_line()
	{
		// qa_shorten_string_line ($string, $length)

		$test = qa_shorten_string_line($this->strBasic, 30);

		$this->assertStringStartsWith('So I tied', $test);
		$this->assertStringEndsWith('time.', $test);
		$this->assertNotFalse(strpos($test, '...'));
	}

	function test__qa_block_words_explode()
	{
		$test = qa_block_words_explode($this->blockWordString);
		$expected = array('t*d', 'o*n', 'b*t', 'style');

		$this->assertEquals($expected, $test);
	}

	function test__qa_block_words_to_preg()
	{
		$test = qa_block_words_to_preg($this->blockWordString);
		$expected = '(?<= )t[^ ]*d(?= )|(?<= )o[^ ]*n(?= )|(?<= )b[^ ]*t(?= )|(?<= )style(?= )';

		$this->assertEquals($expected, $test);
	}

	function test__qa_block_words_match_all()
	{
		$test1 = qa_block_words_match_all('onion belt', '');

		$wordpreg = qa_block_words_to_preg($this->blockWordString);
		$test2 = qa_block_words_match_all('tried an ocean boat', $wordpreg);
		// matches are returned as array of [offset] => [length]
		$expected = array(
			 0 => 5, // tried
			 9 => 5, // ocean
			15 => 4, // boat
		);

		$this->assertEmpty($test1);
		$this->assertEquals($expected, $test2);
	}

	function test__qa_block_words_replace()
	{
		$wordpreg = qa_block_words_to_preg($this->blockWordString);
		$test = qa_block_words_replace('tired of my ocean boat style', $wordpreg);
		$expected = '***** of my ***** **** *****';

		$this->assertEquals($expected, $test);
	}

	function test__qa_random_alphanum()
	{
		$len = 50;
		$test = qa_random_alphanum($len);

		$this->assertEquals(strlen($test), $len);
		$this->assertEquals(preg_match('/[^a-z0-9]/', $test), 0);  // Returns FALSE if there is an error
	}

	function test__qa_email_validate()
	{
		$goodEmails = array(
			'hello@example.com',
			'q.a@question2answer.org',
			'example@newdomain.app',
			'another-email!`_#$%^&*=.{}@otherdomain.com.ar',
			'other-valid\'+@somewhat.long.email.addresses',
		);
		$badEmails = array(
			'nobody@nowhere',
			'pokémon@example.com',
			'email @ with spaces',
			'some random string',
			'with(round)-or-[square]-or-<angle>-brackets@test.com',
			'another-"invalid"-\|;:?/address@test.com',
		);

		foreach ($goodEmails as $email) {
			$this->assertTrue( qa_email_validate($email) );
		}
		foreach ($badEmails as $email)
			$this->assertFalse( qa_email_validate($email) );
	}

	function test__qa_strlen()
	{
		$test = qa_strlen($this->strAccents);

		$this->assertEquals($test, 43);
	}

	function test__qa_strtolower()
	{
		$test = qa_strtolower('hElLo WoRld');

		$this->assertEquals($test, 'hello world');
	}

	function test__qa_substr()
	{
		$test = qa_substr($this->strBasic, 5, 24);

		$this->assertEquals($test, 'tied an onion to my belt');
	}

	function test__qa_string_matches_one()
	{
		$matches = array( 'dyed', 'shallot', 'belt', 'fashion' );
		$nonMatches = array( 'dyed', 'shallot', 'buckle', 'fashion' );

		$this->assertTrue( qa_string_matches_one($this->strBasic, $matches) );
		$this->assertFalse( qa_string_matches_one($this->strBasic, $nonMatches) );
		$this->assertFalse( qa_string_matches_one('', $nonMatches) );
	}

}
