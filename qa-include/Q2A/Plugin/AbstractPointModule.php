<?php

abstract class Q2A_Plugin_AbstractPointModule implements Q2A_Plugin_IPointModule
{
	/**
	 * Return the amount of points that the module expects to change for the given user id. This default implementation
	 * just calls the getPointsForUsers() function wrapping the given user id as the only user in the array. It can be
	 * overridden in subclasses
	 *
	 * @param int|string $userId The user id
	 *
	 * @return int The number of points that the module should change for the given user
	 */
	public function getPointsForUser($userId)
	{
		$userIdPoints = $this->getPointsForUsers(array($userId));

		return reset($userIdPoints);
	}
}
