<?php
require_once QA_INCLUDE_DIR.'app/votes.php';
require_once QA_INCLUDE_DIR.'app/options.php';

class AppVotesTest extends PHPUnit_Framework_TestCase
{
	private $voteviewOpts = array(
		'voting_on_qs' => 1,
		'voting_on_as' => 1,
		'voting_on_cs' => 1,
		// 'voting_on_q_page_only' => 1,
		// 'votes_separated' => 0,
		'permit_vote_q' => QA_PERMIT_USERS,
		'permit_vote_a' => QA_PERMIT_USERS,
		'permit_vote_c' => QA_PERMIT_USERS,
		'permit_vote_down' => QA_PERMIT_USERS,
	);

	private $mockQuestion = array(
		'postid' => 16349,
		'categoryid' => '',
		'type' => 'Q',
		'basetype' => 'Q',
		'hidden' => 0,
		'queued' => 0,
		'acount' => 13,
		'selchildid' => '',
		'closedbyid' => '',
		'upvotes' => 1,
		'downvotes' => 0,
		'netvotes' => 1,
		'views' => 20,
		'hotness' => 33319100000,
		'flagcount' => 0,
		'title' => 'To be or not to be?',
		'tags' => 'question,answer',
		'created' => 1344623702,
		'name' => '',
		'categoryname' => '',
		'categorybackpath' => '',
		'categoryids' => '',
		'uservote' => 1,
		'userflag' => 0,
		'userfavoriteq' => '0',
		'content' => 'That is the question.',
		'notify' => '',
		'updated' => 1409375832,
		'updatetype' => 'E',
		'format' => '',
		'lastuserid' => 21981,
		'lastip' => '',
		'parentid' => '',
		'lastviewip' => '',
		'userid' => 1,
		'cookieid' => '',
		'createip' => '',
		'points' => 140,
		'flags' => 0,
		'level' => 0,
		'email' => '21981@example.com',
		'handle' => 'QuestionAsker',
		'avatarblobid' => '',
		'avatarwidth' => '',
		'avatarheight' => '',
		'lasthandle' => 'QuestionAsker',
	);

	private $mockUser = array(
		'userid' => 1,
		'passsalt' => null,
		'passcheck' => null,
		'passhash' => 'passhash',
		'email' => 'email',
		'level' => 120,
		'emailcode' => '',
		'handle' => 'admin',
		'created' => '',
		'sessioncode' => '',
		'sessionsource' => null,
		'flags' => 265,
		'loggedin' => '',
		'loginip' => '',
		'written' => '',
		'writeip' => '',
		'avatarblobid' => '',
		'avatarwidth' => '',
		'avatarheight' => '',
		'points' => 100,
		'wallposts' => 6,
	);


	/**
	 * Test voteview where upvotes/downvotes are combined
	 */
	public function test__qa_vote_error_html()
	{
		// set options/lang/user cache to bypass database
		global $qa_options_cache, $qa_curr_ip_blocked, $qa_cached_logged_in_user, $qa_phrases_full;
		$qa_options_cache = array_merge($qa_options_cache, $this->voteviewOpts);
		$qa_curr_ip_blocked = false;
		$qa_cached_logged_in_user = $this->mockUser;

		$qa_phrases_full['main']['vote_not_allowed'] = 'Voting on this is not allowed';
		$qa_phrases_full['main']['vote_disabled_hidden'] = 'You cannot vote on hidden posts';

		$topage = '123/to-be-or-not-to-be';

		$this->assertSame($qa_phrases_full['main']['vote_not_allowed'], qa_vote_error_html($this->mockQuestion, 1, 1, $topage));

		$hiddenQ = $this->mockQuestion;
		$hiddenQ['hidden'] = 1;
		$this->assertSame($qa_phrases_full['main']['vote_disabled_hidden'], qa_vote_error_html($hiddenQ, 1, 17, $topage));

		// can't test more right now due to qa_user_limits_remaining() call from qa_user_permit_error()
	}
}
