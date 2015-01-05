<?php
/*
	Plugin Name: CKEditor4
	Plugin URI: 
	Plugin Description: Wrapper for CKEditor4 WYSIWYG rich text editor
	Plugin Version: 1.3
	Plugin Date: 2014-10-03
	Plugin Author: sama55@CMSBOX
	Plugin Author URI: http://www.cmsbox.jp/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.5.1
	Plugin Update Check URI: 
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

qa_register_plugin_module('editor', 'qa-ckeditor4.php', 'qa_ckeditor4', 'CKEditor4');
qa_register_plugin_module('page', 'qa-ckeditor4-upload.php', 'qa_ckeditor4_upload', 'CKEditor4 Upload');
qa_register_plugin_layer('qa-ckeditor4-layer.php', 'CKEditor4 Layer');
qa_register_plugin_overrides('qa-ckeditor4-overrides.php');

/*
	Omit PHP closing tag to help avoid accidental output
*/