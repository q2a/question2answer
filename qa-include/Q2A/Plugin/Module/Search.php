<?php

abstract class Q2A_Plugin_Module_Search extends Q2A_Plugin_Module
{
	public function getType()
	{
		return 'search';
	}

	public function indexPost($postId, $type, $questionId, $parentId, $title, $content, $format, $text, $tagstring, $categoryId) { }

	public function unindexPost($postId) { }

	public function movePost($postId, $categoryId) { }

	public function indexPage($pageId, $request, $title, $content, $format, $text) { }

	public function unindexPage($pageId) { }

	public function processSearch($query, $start, $count, $userId, $absoluteUrls, $fullContent) { }

	public abstract function getDisplayName(); // Used in admin/lists
}