<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-widget-ask-box.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Widget module class for ask a question box


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

	class qa_ask_box {
		
		function allow_template($template)
		{
			$allow=false;
			
			switch ($template)
			{
				case 'activity':
				case 'categories':
				case 'custom':
				case 'feedback':
				case 'qa':
				case 'questions':
				case 'hot':
				case 'search':
				case 'tag':
				case 'tags':
				case 'unanswered':
					$allow=true;
					break;
			}
			
			return $allow;
		}

		
		function allow_region($region)
		{
			$allow=false;
			
			switch ($region)
			{
				case 'main':
				case 'side':
				case 'full':
					$allow=true;
					break;
			}
			
			return $allow;
		}
	
	
		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
			if (isset($qa_content['categoryids']))
				$params=array('cat' => end($qa_content['categoryids']));
			else
				$params=null;
?>
<div class="qa-ask-box">
	<form method="post" action="<?php echo qa_path_html('ask', $params); ?>">
		<table class="qa-form-tall-table" style="width:100%">
			<tr style="vertical-align:middle;">
				<td class="qa-form-tall-label" style="padding:8px; white-space:nowrap; <?php echo ($region=='side') ? 'padding-bottom:0;' : 'text-align:right;'?>" width="1">
					<?php echo strtr(qa_lang_html('question/ask_title'), array(' ' => '&nbsp;'))?>:
				</td>
<?php
			if ($region=='side') {
?>
			</tr>
			<tr>
<?php			
			}
?>
				<td class="qa-form-tall-data" style="padding:8px;" width="*">
					<input name="title" type="text" class="qa-form-tall-text" style="width:95%;">
				</td>
			</tr>
		</table>
		<input type="hidden" name="doask1" value="1">
	</form>
</div>
<?php
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/