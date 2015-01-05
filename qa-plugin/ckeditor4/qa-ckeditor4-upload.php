<?php

class qa_ckeditor4_upload {

	function match_request($request) {
		return ($request=='wysiwyg-editor-upload');
	}
	
	function process_request($request) {
		$message='';
		$url='';
		
		if (is_array($_FILES) && count($_FILES)) {
			if (!qa_opt('ckeditor4_upload_images'))
				$message=qa_lang('users/no_permission');
				
			require_once QA_INCLUDE_DIR.'qa-app-upload.php';
			
			$upload=qa_upload_file_one(
				qa_opt('ckeditor4_upload_max_size'),
				qa_get('qa_only_image') || !qa_opt('ckeditor4_upload_all'),
				qa_get('qa_only_image') ? 600 : null, // max width if it's an image upload
				null // no max height
			);
			
			$message=@$upload['error'];
			$url=@$upload['bloburl'];
		}
		
		echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction(".qa_js(qa_get('CKEditorFuncNum')).
			", ".qa_js($url).", ".qa_js($message).");</script>";
		
		return null;
	}
}

/*
	Omit PHP closing tag to help avoid accidental output
*/