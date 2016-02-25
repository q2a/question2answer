<?php
require_once QA_INCLUDE_DIR.'app/limits.php';

class AppLimitsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test IP matching function.
	 */
	public function test__qa_block_ip_match()
	{
		$ipv4a = '127.0.0.1';
		$ipv4b = '88.77.66.123';
		$ipv4_range1 = '88.77.66.1-88.77.66.200';
		$ipv4_range2 = '88.77.66.124-88.77.66.200';
		$ipv4_wild1 = '88.77.66.*';
		$ipv4_wild2 = '88.77.55.*';
		$ipv4_wild3 = '88.77.*.*';

		$ipv6 = 'fe80:1:2:3:a:bad:1dea:dad';
		$ipv6_compact = '::ffff:7f00:0001';
		$ipv6_expanded = '0:0:0:0:0:ffff:7f00:0001';
		$ipv6_range1 = '::ffff:7f00:0001-::ffff:7f00:0099';
		$ipv6_range2 = '::ffff:7f00:0002-::ffff:7f00:0099';
		$ipv6_range3 = '::ffff:7f00:0001-0:0:0:0:0:ffff:7f00:0099';
		$ipv6_wildcard1 = '::ffff:7f00:*';
		$ipv6_wildcard2 = '::ffff:7e99:*';
		$ipv6_wildcard3 = '::ffff:*:*';

		// check mixed types
		$this->assertSame(false, qa_block_ip_match($ipv4b, $ipv6), 'Mixed IP types');

		// check single IPv4
		$this->assertSame(true, qa_block_ip_match($ipv4a, $ipv4a), 'IPv4 match');
		$this->assertSame(false, qa_block_ip_match($ipv4a, $ipv4b), 'IPv4 non-match');

		// check IPv4 range
		$this->assertSame(true, qa_block_ip_match($ipv4b, $ipv4_range1), 'IPv4 range match');
		$this->assertSame(false, qa_block_ip_match($ipv4b, $ipv4_range2), 'IPv4 range non-match');

		// check IPv4 wildcard
		$this->assertSame(true, qa_block_ip_match($ipv4b, $ipv4_wild1), 'IPv4 wildcard match');
		$this->assertSame(false, qa_block_ip_match($ipv4b, $ipv4_wild2), 'IPv4 wildcard non-match');
		$this->assertSame(true, qa_block_ip_match($ipv4b, $ipv4_wild3), 'IPv4 double wildcard match');

		// check single IPv6
		$this->assertSame(true, qa_block_ip_match($ipv6, $ipv6), 'IPv6 match');
		$this->assertSame(false, qa_block_ip_match($ipv6, $ipv6_expanded), 'IPv6 non-match');
		$this->assertSame(true, qa_block_ip_match($ipv6_compact, $ipv6_compact), 'IPv6 compact match');
		$this->assertSame(true, qa_block_ip_match($ipv6_compact, $ipv6_expanded), 'IPv6 compact+expanded match');
		$this->assertSame(false, qa_block_ip_match($ipv6, $ipv6_compact), 'IPv6 compact+expanded non-match');

		// check IPv6 range
		$this->assertSame(true, qa_block_ip_match($ipv6_compact, $ipv6_range1), 'IPv6 range match');
		$this->assertSame(false, qa_block_ip_match($ipv6_compact, $ipv6_range2), 'IPv6 range non-match');
		$this->assertSame(true, qa_block_ip_match($ipv6_compact, $ipv6_range3), 'IPv6 range compact+expanded match');

		// check IPv6 wildcard
		$this->assertSame(true, qa_block_ip_match($ipv6_expanded, $ipv6_wildcard1), 'IPv6 wildcard match');
		$this->assertSame(true, qa_block_ip_match($ipv6_compact, $ipv6_wildcard1), 'IPv6 compact wildcard match');
		$this->assertSame(false, qa_block_ip_match($ipv6_compact, $ipv6_wildcard2), 'IPv6 wildcard non-match');
		$this->assertSame(true, qa_block_ip_match($ipv6_compact, $ipv6_wildcard3), 'IPv6 double wildcard match');
	}
}
