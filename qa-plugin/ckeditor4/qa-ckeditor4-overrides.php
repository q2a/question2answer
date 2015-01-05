<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

	function qa_sanitize_html($html, $linksnewwindow=false, $storage=false)
/*
	Return $html after ensuring it is safe, i.e. removing Javascripts and the like - uses htmLawed library
	Links open in a new window if $linksnewwindow is true. Set $storage to true if sanitization is for
	storing in the database, rather than immediate display to user - some think this should be less strict.
*/
	{
		if(qa_opt('ckeditor4_htmLawed_controler')) {
			require_once QA_INCLUDE_DIR.'qa-htmLawed.php';
			
			global $qa_sanitize_html_newwindow;
			
			$qa_sanitize_html_newwindow=$linksnewwindow;
			
			$safe=htmLawed($html, array(
				'safe' => qa_opt('ckeditor4_htmLawed_safe'),
				'elements' => qa_opt('ckeditor4_htmLawed_elements'),
				'schemes' => qa_opt('ckeditor4_htmLawed_schemes'),
				'keep_bad' => qa_opt('ckeditor4_htmLawed_keep_bad'),
				'anti_link_spam' => explode(',', qa_opt('ckeditor4_htmLawed_anti_link_spam')),
				'hook_tag' => qa_opt('ckeditor4_htmLawed_hook_tag'),
			));
			
			return $safe;
		} else
			return qa_sanitize_html_base($html, $linksnewwindow, $storage);
		
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/