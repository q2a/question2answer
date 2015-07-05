<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-editor-basic.php
	Description: Basic editor module for plain text editing


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

class Q2A_Editor_Basic extends Q2A_Plugin_Module_Editor
{
	public function getInternalId() {
		return 'Q2A_Editor_Basic';
	}

	public function getDisplayName() {
		return 'Basic Editor';
	}

	public function calculateQuality($content, $format)
	{
		if ($format=='')
			return 1.0;

		if ($format=='html')
			return 0.2;

		return 0;
	}

	public function getField(&$qa_content, $content, $format, $fieldName, $rows)
	{
		return array(
			'type' => 'textarea',
			'tags' => 'name="' . $fieldName . '" id="' . $fieldName . '"',
			'value' => qa_html($content),
			'rows' => $rows,
		);
	}

	public function getFocusScript($fieldName)
	{
		return sprintf("document.getElementById('%s').focus();", $fieldName);
	}

	public function readPost($fieldName)
	{
		return array(
			'format' => '',
			'content' => qa_post_text($fieldName),
		);
	}

}
