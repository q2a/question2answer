<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database-level access to user points and statistics


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


/**
 * Returns an array of option names required to perform calculations in userpoints table
 * @return mixed
 */
function qa_db_points_option_names()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	return array(
		'points_post_q', 'points_select_a', 'points_per_q_voted_up', 'points_per_q_voted_down', 'points_q_voted_max_gain', 'points_q_voted_max_loss',
		'points_post_a', 'points_a_selected', 'points_per_a_voted_up', 'points_per_a_voted_down', 'points_a_voted_max_gain', 'points_a_voted_max_loss',
		'points_per_c_voted_up', 'points_per_c_voted_down', 'points_c_voted_max_gain', 'points_c_voted_max_loss',
		'points_vote_up_q', 'points_vote_down_q', 'points_vote_up_a', 'points_vote_down_a',

		'points_multiple', 'points_base',
	);
}


/**
 * Returns an array containing all the calculation formulae for the userpoints table. Each element of this
 * array is for one column - the key contains the column name, and the value is a further array of two elements.
 * The element 'formula' contains the SQL fragment that calculates the columns value for one or more users,
 * and must contain one placeholder using ? for which the userid is passed as a parameter in the query.
 * The element 'multiple' specifies what to multiply each column by to create the final sum in the points column.
 * @return mixed
 */
function qa_db_points_calculations()
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	require_once QA_INCLUDE_DIR . 'app/options.php';

	$options = qa_get_options(qa_db_points_option_names());

	return array(
		'qposts' => array(
			'multiple' => $options['points_multiple'] * $options['points_post_q'],
			'formula' => "COUNT(*) AS qposts FROM ^posts AS userid_src WHERE userid=? AND type='Q'",
		),

		'aposts' => array(
			'multiple' => $options['points_multiple'] * $options['points_post_a'],
			'formula' => "COUNT(*) AS aposts FROM ^posts AS userid_src WHERE userid=? AND type='A'",
		),

		'cposts' => array(
			'multiple' => 0,
			'formula' => "COUNT(*) AS cposts FROM ^posts AS userid_src WHERE userid=? AND type='C'",
		),

		'aselects' => array(
			'multiple' => $options['points_multiple'] * $options['points_select_a'],
			'formula' => "COUNT(*) AS aselects FROM ^posts AS userid_src WHERE userid=? AND type='Q' AND selchildid IS NOT NULL",
		),

		'aselecteds' => array(
			'multiple' => $options['points_multiple'] * $options['points_a_selected'],
			'formula' => "COUNT(*) AS aselecteds FROM ^posts AS userid_src JOIN ^posts AS questions ON questions.selchildid=userid_src.postid WHERE userid_src.userid=? AND userid_src.type='A' AND NOT (questions.userid<=>userid_src.userid)",
		),

		'qupvotes' => array(
			'multiple' => $options['points_multiple'] * $options['points_vote_up_q'],
			'formula' => "COUNT(*) AS qupvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid=? AND LEFT(^posts.type, 1)='Q' AND userid_src.vote>0",
		),

		'qdownvotes' => array(
			'multiple' => $options['points_multiple'] * $options['points_vote_down_q'],
			'formula' => "COUNT(*) AS qdownvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid=? AND LEFT(^posts.type, 1)='Q' AND userid_src.vote<0",
		),

		'aupvotes' => array(
			'multiple' => $options['points_multiple'] * $options['points_vote_up_a'],
			'formula' => "COUNT(*) AS aupvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid=? AND LEFT(^posts.type, 1)='A' AND userid_src.vote>0",
		),

		'adownvotes' => array(
			'multiple' => $options['points_multiple'] * $options['points_vote_down_a'],
			'formula' => "COUNT(*) AS adownvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid=? AND LEFT(^posts.type, 1)='A' AND userid_src.vote<0",
		),

		'cupvotes' => array(
			'multiple' => 0,
			'formula' => "COUNT(*) AS cupvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid=? AND LEFT(^posts.type, 1)='C' AND userid_src.vote>0",
		),

		'cdownvotes' => array(
			'multiple' => 0,
			'formula' => "COUNT(*) AS cdownvotes FROM ^uservotes AS userid_src JOIN ^posts ON userid_src.postid=^posts.postid WHERE userid_src.userid=? AND LEFT(^posts.type, 1)='C' AND userid_src.vote<0",
		),

		'qvoteds' => array(
			'multiple' => $options['points_multiple'],
			'formula' => "COALESCE(SUM(" .
				"LEAST(" . ((int)$options['points_per_q_voted_up']) . "*upvotes," . ((int)$options['points_q_voted_max_gain']) . ")" .
				"-" .
				"LEAST(" . ((int)$options['points_per_q_voted_down']) . "*downvotes," . ((int)$options['points_q_voted_max_loss']) . ")" .
				"), 0) AS qvoteds FROM ^posts AS userid_src WHERE LEFT(type, 1)='Q' AND userid=?",
		),

		'avoteds' => array(
			'multiple' => $options['points_multiple'],
			'formula' => "COALESCE(SUM(" .
				"LEAST(" . ((int)$options['points_per_a_voted_up']) . "*upvotes," . ((int)$options['points_a_voted_max_gain']) . ")" .
				"-" .
				"LEAST(" . ((int)$options['points_per_a_voted_down']) . "*downvotes," . ((int)$options['points_a_voted_max_loss']) . ")" .
				"), 0) AS avoteds FROM ^posts AS userid_src WHERE LEFT(type, 1)='A' AND userid=?",
		),

		'cvoteds' => array(
			'multiple' => $options['points_multiple'],
			'formula' => "COALESCE(SUM(" .
				"LEAST(" . ((int)$options['points_per_c_voted_up']) . "*upvotes," . ((int)$options['points_c_voted_max_gain']) . ")" .
				"-" .
				"LEAST(" . ((int)$options['points_per_c_voted_down']) . "*downvotes," . ((int)$options['points_c_voted_max_loss']) . ")" .
				"), 0) AS cvoteds FROM ^posts AS userid_src WHERE LEFT(type, 1)='C' AND userid=?",
		),

		'upvoteds' => array(
			'multiple' => 0,
			'formula' => "COALESCE(SUM(upvotes), 0) AS upvoteds FROM ^posts AS userid_src WHERE userid=?",
		),

		'downvoteds' => array(
			'multiple' => 0,
			'formula' => "COALESCE(SUM(downvotes), 0) AS downvoteds FROM ^posts AS userid_src WHERE userid=?",
		),
	);
}


/**
 * Update the userpoints table in the database for $userid and $columns, plus the summary points column.
 * Set $columns to true for all, false for none, an array for several, or a single value for one.
 * This dynamically builds some fairly crazy looking SQL, but it works, and saves repeat calculations.
 * @param mixed $userid
 * @param bool|string|array $columns
 * @return mixed
 */
function qa_db_points_update_ifuser($userid, $columns)
{
	if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

	$db = qa_service('database');

	if ($db->shouldUpdateCounts() && isset($userid)) {
		require_once QA_INCLUDE_DIR . 'app/options.php';
		require_once QA_INCLUDE_DIR . 'app/cookies.php';

		$calculations = qa_db_points_calculations();

		if ($columns === true) {
			$keycolumns = $calculations;
		} elseif (empty($columns)) {
			$keycolumns = array();
		} elseif (is_array($columns)) {
			$keycolumns = array_flip($columns);
		} else {
			$keycolumns = array($columns => true);
		}

		$insertfields = 'userid, ';
		$insertvalues = '?, ';
		$insertparams = [$userid];
		$insertpoints = (int)qa_opt('points_base');

		$updates = '';
		$updatepoints = $insertpoints;

		foreach ($calculations as $field => $calculation) {
			$multiple = (int)$calculation['multiple'];

			if (isset($keycolumns[$field])) {
				$insertfields .= $field . ', ';
				$insertvalues .= '@_' . $field . ':=(SELECT ' . $calculation['formula'] . '), ';
				$insertparams[] = $userid;
				$updates .= $field . '=@_' . $field . ', ';
				$insertpoints .= '+(' . (int)$multiple . '*@_' . $field . ')';
			}

			$updatepoints .= '+(' . $multiple . '*' . (isset($keycolumns[$field]) ? '@_' : '') . $field . ')';
		}

		$query = 'INSERT INTO ^userpoints (' . $insertfields . 'points) VALUES (' . $insertvalues . $insertpoints . ') ' .
			'ON DUPLICATE KEY UPDATE ' . $updates . 'points=' . $updatepoints . '+bonus';

		$result = $db->query($query, $insertparams);

		if ($result->affectedRows() > 0) {
			qa_db_userpointscount_update();
		}
	}
}


/**
 * Set the number of explicit bonus points for $userid to $bonus
 * @param mixed $userid
 * @param int $bonus
 */
function qa_db_points_set_bonus($userid, $bonus)
{
	qa_service('database')->query(
		'INSERT INTO ^userpoints (userid, bonus) VALUES (?, ?) ON DUPLICATE KEY UPDATE bonus=?',
		[$userid, $bonus, $bonus]
	);
}


/**
 * Update the cached count in the database of the number of rows in the userpoints table
 */
function qa_db_userpointscount_update()
{
	$db = qa_service('database');
	if ($db->shouldUpdateCounts()) {
		$db->query(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_userpointscount', COUNT(*) FROM ^userpoints " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}
