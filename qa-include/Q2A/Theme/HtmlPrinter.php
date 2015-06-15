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

class Q2A_Theme_HtmlPrinter
{

	protected $indent = 0;
	protected $lines = 0;
	protected $context = array();

	public function outputArray($elements)
/*
	Output each element in $elements on a separate line, with automatic HTML indenting.
	This should be passed markup which uses the <tag/> form for unpaired tags, to help keep
	track of indenting, although its actual output converts these to <tag> for W3C validation
*/
	{
		foreach ($elements as $element) {
			$delta = substr_count($element, '<') - substr_count($element, '<!') - 2*substr_count($element, '</') - substr_count($element, '/>');

			if ($delta < 0)
				$this->indent += $delta;

			echo str_repeat("\t", max(0, $this->indent)).str_replace('/>', '>', $element)."\n";

			if ($delta > 0)
				$this->indent += $delta;

			$this->lines++;
		}
	}


	public function output() // other parameters picked up via func_get_args()
/*
	Output each passed parameter on a separate line - see output_array() comments
*/
	{
		$args = func_get_args();
		$this->outputArray($args);
	}


	public function outputRaw($html)
/*
	Output $html at the current indent level, but don't change indent level based on the markup within.
	Useful for user-entered HTML which is unlikely to follow the rules we need to track indenting
*/
	{
		if (strlen($html))
			echo str_repeat("\t", max(0, $this->indent)).$html."\n";
	}


	public function outputSplit($parts, $class, $outertag='span', $innertag='span', $extraclass=null)
/*
	Output the three elements ['prefix'], ['data'] and ['suffix'] of $parts (if they're defined),
	with appropriate CSS classes based on $class, using $outertag and $innertag in the markup.
*/
	{
		if (empty($parts) && strtolower($outertag) != 'td')
			return;

		$this->output(
			'<'.$outertag.' class="'.$class.(isset($extraclass) ? (' '.$extraclass) : '').'">',
			(strlen(@$parts['prefix']) ? ('<'.$innertag.' class="'.$class.'-pad">'.$parts['prefix'].'</'.$innertag.'>') : '').
			(strlen(@$parts['data']) ? ('<'.$innertag.' class="'.$class.'-data">'.$parts['data'].'</'.$innertag.'>') : '').
			(strlen(@$parts['suffix']) ? ('<'.$innertag.' class="'.$class.'-pad">'.$parts['suffix'].'</'.$innertag.'>') : ''),
			'</'.$outertag.'>'
		);
	}


	public function setContext($key, $value)
/*
	Set some context, which be accessed via $this->context for a function to know where it's being used on the page
*/
	{
		$this->context[$key] = $value;
	}


	public function clearContext($key)
/*
	Clear some context (used at the end of the appropriate loop)
*/
	{
		unset($this->context[$key]);
	}

	public function isProperlyFormatted()
	{
		return $this->indent == 0;
	}


}
