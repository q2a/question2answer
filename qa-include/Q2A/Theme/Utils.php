<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Util/Metadata.php
	Description: Some useful metadata handling stuff


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

class Q2A_Theme_Utils
{

	public function reorder_parts(&$qa_content, $parts, $beforekey=null, $reorderrelative=true)
/*
	Reorder the parts of the page according to the $parts array which contains part keys in their new order. Call this
	before main_parts() in your theme. See the docs for qa_array_reorder() in util/sort.php for the other parameters.
*/
	{
		require_once QA_INCLUDE_DIR.'util/sort.php';

		qa_array_reorder($qa_content, $parts, $beforekey, $reorderrelative);
	}



	public function form_reorder_fields(&$form, $keys, $beforekey=null, $reorderrelative=true)
/*
	Reorder the fields of $form according to the $keys array which contains the field keys in their new order. Call
	before any fields are output. See the docs for qa_array_reorder() in util/sort.php for the other parameters.
*/
	{
		require_once QA_INCLUDE_DIR.'util/sort.php';

		if (is_array($form['fields']))
			qa_array_reorder($form['fields'], $keys, $beforekey, $reorderrelative);
	}

	public function form_reorder_buttons(&$form, $keys, $beforekey=null, $reorderrelative=true)
/*
	Reorder the buttons of $form according to the $keys array which contains the button keys in their new order. Call
	before any buttons are output. See the docs for qa_array_reorder() in util/sort.php for the other parameters.
*/
	{
		require_once QA_INCLUDE_DIR.'util/sort.php';

		if (is_array($form['buttons']))
			qa_array_reorder($form['buttons'], $keys, $beforekey, $reorderrelative);
	}

}
