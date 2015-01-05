<?php
if (!defined('QA_VERSION')) { 
	require_once '../../qa-include/qa-base.php';
}
require_once QA_INCLUDE_DIR.'qa-app-options.php';
require_once QA_INCLUDE_DIR.'qa-app-users.php';
require_once QA_INCLUDE_DIR.'qa-app-posts.php';

if($_SERVER["REQUEST_METHOD"] != "POST"){
	header('Location: ../../');
	exit;
}

header("Content-Type: text/html; charset=UTF-8");
$id = qa_post_text('id');
$url = qa_post_text('url');
$content = qa_post_text('text');
$postid = str_replace('_content', '', substr($id, 1));
if(is_numeric($postid))
	qa_post_set_content($postid, null, $content,null, null, null, null, qa_get_logged_in_userid(), null, null);

/*
	Omit PHP closing tag to help avoid accidental output
*/