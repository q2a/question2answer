<?php
require_once QA_INCLUDE_DIR.'app/limits.php';

class AppLimitsTest extends PHPUnit_Framework_TestCase
{
	private $ipv4_wildcard1 = '88.77.66.*';
	private $ipv4_wildcard2 = '88.77.55.*';
	private $ipv4_wildcard3 = '88.77.*.*';

	private $ipv6 = 'fe80:1:2:3:a:bad:1dea:dad';
	private $ipv6_compact = '::ffff:7f00:0001';
	private $ipv6_expanded = '0:0:0:0:0:ffff:7f00:0001';
	private $ipv6_wildcard1 = '::ffff:7f00:*';
	private $ipv6_wildcard2 = '::ffff:7e99:*';
	private $ipv6_wildcard3 = '::ffff:*:*';

	/**
	 * Test IP matching function with IPv4.
	 */
	public function test__qa_block_ip_match__ipv4()
	{
		$ipv4a = '127.0.0.1';
		$ipv4b = '88.77.66.123';

		// check mixed types
		$this->assertSame(false, qa_block_ip_match($ipv4b, $this->ipv6), 'Mixed IP types');

		// check single IPv4
		$this->assertSame(true, qa_block_ip_match($ipv4a, $ipv4a), 'IPv4 match');
		$this->assertSame(false, qa_block_ip_match($ipv4a, $ipv4b), 'IPv4 non-match');

		// check IPv4 range
		$this->assertSame(true, qa_block_ip_match($ipv4b, '88.77.66.1-88.77.66.200'), 'IPv4 range match');
		$this->assertSame(false, qa_block_ip_match($ipv4b, '88.77.66.124-88.77.66.200'), 'IPv4 range non-match');

		// check IPv4 wildcard
		$this->assertSame(true, qa_block_ip_match($ipv4b, $this->ipv4_wildcard1), 'IPv4 wildcard match');
		$this->assertSame(false, qa_block_ip_match($ipv4b, $this->ipv4_wildcard2), 'IPv4 wildcard non-match');
		$this->assertSame(true, qa_block_ip_match($ipv4b, $this->ipv4_wildcard3), 'IPv4 double wildcard match');
	}

	/**
	 * Test IP matching function with IPv6.
	 */
	public function test__qa_block_ip_match__ipv6()
	{
		// check single IPv6
		$this->assertSame(true, qa_block_ip_match($this->ipv6, $this->ipv6), 'IPv6 match');
		$this->assertSame(false, qa_block_ip_match($this->ipv6, $this->ipv6_expanded), 'IPv6 non-match');
		$this->assertSame(true, qa_block_ip_match($this->ipv6_compact, $this->ipv6_compact), 'IPv6 compact match');
		$this->assertSame(true, qa_block_ip_match($this->ipv6_compact, $this->ipv6_expanded), 'IPv6 compact+expanded match');
		$this->assertSame(false, qa_block_ip_match($this->ipv6, $this->ipv6_compact), 'IPv6 compact+expanded non-match');

		// check IPv6 range
		$this->assertSame(true, qa_block_ip_match($this->ipv6_compact, '::ffff:7f00:0001-0:0:0:0:0:ffff:7f00:0099'), 'IPv6 range match');
		$this->assertSame(false, qa_block_ip_match($this->ipv6_compact, '::ffff:7f00:0002-::ffff:7f00:0099'), 'IPv6 range non-match');
		$this->assertSame(true, qa_block_ip_match('ffff::1', 'ffff::1-ffff::5'), 'Large IPv6 range match');
		$this->assertSame(false, qa_block_ip_match('ffff::1', 'ffff::2-ffff::5'), 'Large IPv6 range non-match');

		// check IPv6 wildcard
		$this->assertSame(true, qa_block_ip_match($this->ipv6_expanded, $this->ipv6_wildcard1), 'IPv6 wildcard match');
		$this->assertSame(true, qa_block_ip_match($this->ipv6_compact, $this->ipv6_wildcard1), 'IPv6 compact wildcard match');
		$this->assertSame(false, qa_block_ip_match($this->ipv6_compact, $this->ipv6_wildcard2), 'IPv6 wildcard non-match');
		$this->assertSame(true, qa_block_ip_match($this->ipv6_compact, $this->ipv6_wildcard3), 'IPv6 double wildcard match');
	}

	public function test__qa_ip_between()
	{
		$this->assertTrue(qa_ip_between('::1', '0:0:0:0:0:0:0:0001', '0:0:0:0:0:0:0:0005'), 'IPv6 match');
		$this->assertFalse(qa_ip_between('::1', '0:0:0:0:0:0:0:0002', '0:0:0:0:0:0:0:0005'), 'IPv6 non-match');
		$this->assertTrue(qa_ip_between('ffff::3', 'ffff::1', 'ffff::5'), 'Large IPv6 match');
		$this->assertFalse(qa_ip_between('ffff::1', 'ffff::2', 'ffff::5'), 'Large IPv6 non-match');
	}

	public function test__qa_ipv6_expand()
	{
		$this->assertSame('0000:0000:0000:0000:0000:0000:0000:0001', qa_ipv6_expand('::1'));
		$this->assertSame('ffff:0000:0000:0000:0000:0000:0000:0001', qa_ipv6_expand('ffff::1'));
		$this->assertSame('0000:0000:0000:0000:0000:ffff:7f00:*', qa_ipv6_expand($this->ipv6_wildcard1));
		$this->assertSame('0000:0000:0000:0000:0000:ffff:*:*', qa_ipv6_expand($this->ipv6_wildcard3));
	}
}
