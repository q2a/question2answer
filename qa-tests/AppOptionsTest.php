<?php
require_once QA_INCLUDE_DIR.'app/format.php';
require_once QA_INCLUDE_DIR.'app/options.php';

class AppOptionsTest extends PHPUnit_Framework_TestCase
{
	private $voteviewOpts = array(
		'voting_on_qs' => 1,
		'voting_on_as' => 1,
		'voting_on_cs' => 1,
		'voting_on_q_page_only' => 1,
		'votes_separated' => 0,
		'permit_vote_q' => QA_PERMIT_USERS,
		'permit_vote_a' => QA_PERMIT_USERS,
		'permit_vote_c' => QA_PERMIT_USERS,
		'permit_vote_down' => QA_PERMIT_USERS,
	);

	private $mockUser = array(
		'userid' => '1',
		'passsalt' => null,
		'passcheck' => null,
		'passhash' => 'passhash',
		'email' => 'email',
		'level' => '120',
		'emailcode' => '',
		'handle' => 'admin',
		'created' => '',
		'sessioncode' => '',
		'sessionsource' => null,
		'flags' => '265',
		'loggedin' => '',
		'loginip' => '',
		'written' => '',
		'writeip' => '',
		'avatarblobid' => '',
		'avatarwidth' => '',
		'avatarheight' => '',
		'points' => '100',
		'wallposts' => '6',
	);

	/**
	 * Test voteview where upvotes/downvotes are combined
	 */
	public function test__qa_get_vote_view__net()
	{
		// set options/user cache to bypass database
		global $qa_options_cache, $qa_curr_ip_blocked, $qa_cached_logged_in_user;
		$qa_options_cache = array_merge($qa_options_cache, $this->voteviewOpts);
		$qa_curr_ip_blocked = false;
		$qa_cached_logged_in_user = $this->mockUser;


		$this->assertSame('net', qa_get_vote_view('Q', true));
		$this->assertSame('net-disabled-page', qa_get_vote_view('Q', false));
		$this->assertSame('net-disabled-page', qa_get_vote_view('Q', true, false));
		$this->assertSame('net-disabled-page', qa_get_vote_view('Q', false, false));

		$this->assertSame('net', qa_get_vote_view('A', true));
		$this->assertSame('net', qa_get_vote_view('A', false));
		$this->assertSame('net-disabled-page', qa_get_vote_view('A', true, false));
		$this->assertSame('net-disabled-page', qa_get_vote_view('A', false, false));

		$this->assertSame('net', qa_get_vote_view('C', true));
		$this->assertSame('net', qa_get_vote_view('C', false));


		$qa_options_cache['voting_on_qs'] = 0;
		$qa_options_cache['voting_on_as'] = 0;
		$qa_options_cache['voting_on_cs'] = 0;
		$this->assertSame(false, qa_get_vote_view('Q', true));
		$this->assertSame(false, qa_get_vote_view('A', true));
		$this->assertSame(false, qa_get_vote_view('C', true));
	}

	/**
	 * Test voteview where upvotes/downvotes are separated
	 */
	public function test__qa_get_vote_view__updown()
	{
		// set options/user cache to bypass database
		global $qa_options_cache, $qa_curr_ip_blocked, $qa_cached_logged_in_user;
		$qa_options_cache = array_merge($qa_options_cache, $this->voteviewOpts, array('votes_separated' => 1));
		$qa_curr_ip_blocked = false;
		$qa_cached_logged_in_user = $this->mockUser;

		$this->assertSame('updown', qa_get_vote_view('Q', true));
		$this->assertSame('updown-disabled-page', qa_get_vote_view('Q', false));
		$this->assertSame('updown-disabled-page', qa_get_vote_view('Q', true, false));
		$this->assertSame('updown-disabled-page', qa_get_vote_view('Q', false, false));

		$this->assertSame('updown', qa_get_vote_view('A', true));
		$this->assertSame('updown', qa_get_vote_view('A', false));
		$this->assertSame('updown-disabled-page', qa_get_vote_view('A', true, false));
		$this->assertSame('updown-disabled-page', qa_get_vote_view('A', false, false));

		$this->assertSame('updown', qa_get_vote_view('C', true));
		$this->assertSame('updown', qa_get_vote_view('C', false));

		$qa_options_cache['voting_on_qs'] = 0;
		$qa_options_cache['voting_on_as'] = 0;
		$qa_options_cache['voting_on_cs'] = 0;
		$this->assertSame(false, qa_get_vote_view('Q', true));
		$this->assertSame(false, qa_get_vote_view('A', true));
		$this->assertSame(false, qa_get_vote_view('C', true));
	}
}
