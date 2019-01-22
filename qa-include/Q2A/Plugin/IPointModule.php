<?php

interface Q2A_Plugin_IPointModule
{
	/**
	 * Return the amount of points that the module expects to change for the given user id
	 *
	 * @param int|string $userId The user id
	 *
	 * @return int The number of points that the module should change for the given user
	 */
	public function getPointsForUser($userId);

	/**
	 * Return the amount of points that the module expects to change for the given array of user ids
	 *
	 * @param array $userIds The array of user ids
	 *
	 * @return array The array containing as keys the user ids and as values the amount of points to change
	 */
	public function getPointsForUsers($userIds);
}
