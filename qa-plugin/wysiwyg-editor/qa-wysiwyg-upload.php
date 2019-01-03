<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/wysiwyg-editor/qa-wysiwyg-upload.php
	Description: Page module class for WYSIWYG editor (CKEditor) file upload receiver


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


class qa_wysiwyg_upload
{
	public function match_request($request)
	{
		return $request === 'wysiwyg-editor-upload';
	}

	public function process_request($request)
	{
		$message = '';
		$url = '';

		if (is_array($_FILES) && count($_FILES)) {
			if (qa_opt('wysiwyg_editor_upload_images')) {
				require_once QA_INCLUDE_DIR . 'app/upload.php';

				$onlyImage = qa_get('qa_only_image');
				$upload = qa_upload_file_one(
					qa_opt('wysiwyg_editor_upload_max_size'),
					$onlyImage || !qa_opt('wysiwyg_editor_upload_all'),
					$onlyImage ? 600 : null, // max width if it's an image upload
					null // no max height
				);

				if (isset($upload['error'])) {
					$message = $upload['error'];
				} else {
					$url = $upload['bloburl'];
				}
			} else {
				$message = qa_lang_html('users/no_permission');
			}
		}

		echo sprintf(
			'<script>window.parent.CKEDITOR.tools.callFunction(%s, %s, %s);</script>',
			qa_js(qa_get('CKEditorFuncNum')),
			qa_js($url),
			qa_js($message)
		);

		return null;
	}
}
