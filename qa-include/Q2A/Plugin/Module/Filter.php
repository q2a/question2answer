<?php

abstract class Q2A_Plugin_Module_Filter extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'filter';
	}

	public function filterEmail(&$email, $oldUser) { }

	public function filterHandle(&$handle, $oldUser) { }

	public function filterQuestion(&$question, &$errors, $oldQuestion) { }

	public function filterAnswer(&$answer, &$errors, $question, $oldAnswer) { }

	public function filterComment(&$comment, &$errors, $question, $parent, $oldComment) { }

	public function filterProfile(&$profile, &$errors, $user, $oldProfile) { }

	public function validatePassword($password, $oldUser) { }
}