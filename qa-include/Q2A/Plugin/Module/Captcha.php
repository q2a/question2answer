<?php

abstract class Q2A_Plugin_Module_Captcha extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'captcha';
	}

	public function shouldShowCaptcha()
	{
		return true;
	}

	public function getHtml(&$qa_content, &$error) {
		return '';
	}

	public function validatePost(&$error)
	{
		return true;
	}

	public abstract function getDisplayName(); // Used in admin/spam
}
