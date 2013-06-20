<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-viewer-basic.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Basic viewer module for displaying HTML or plain text


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	class qa_viewer_basic {
	
		var $htmllineseparators;
		var $htmlparagraphseparators;
		

		function load_module($localdir, $htmldir)
		{
			$this->htmllineseparators='br|option';
			$this->htmlparagraphseparators='address|applet|blockquote|center|cite|col|div|dd|dl|dt|embed|form|frame|frameset|h1|h2|h3|h4|h5|h6'.
				'|hr|iframe|input|li|marquee|ol|p|pre|samp|select|spacer|table|tbody|td|textarea|tfoot|th|thead|tr|ul';
		}
		

		function calc_quality($content, $format)
		{
			if ( ($format=='') || ($format=='html') )
				return 1.0;
			else
				return 0.0001; // if there's nothing better this will give an error message for unknown formats
		}

		
		function get_html($content, $format, $options)
		{
			if ($format=='html') {
				$html=qa_sanitize_html($content, @$options['linksnewwindow'], false); // sanitize again for display, for extra safety, and due to new window setting

				if (isset($options['blockwordspreg'])) { // filtering out blocked words inline within HTML is pretty complex, e.g. p<b>oo</b>p must be caught
					require_once QA_INCLUDE_DIR.'qa-util-string.php';

					$html=preg_replace('/<\s*('.$this->htmllineseparators.')[^A-Za-z0-9]/i', "\n\\0", $html); // tags to single new line
					$html=preg_replace('/<\s*('.$this->htmlparagraphseparators.')[^A-Za-z0-9]/i', "\n\n\\0", $html); // tags to double new line
					
					preg_match_all('/<[^>]*>/', $html, $pregmatches, PREG_OFFSET_CAPTURE); // find tag positions and lengths
					$tagmatches=$pregmatches[0];
					$text=preg_replace('/<[^>]*>/', '', $html); // effectively strip_tags() but use same regexp as above to ensure consistency

					$blockmatches=qa_block_words_match_all($text, $options['blockwordspreg']); // search for blocked words within text
					
					$nexttagmatch=array_shift($tagmatches);
					$texttohtml=0;
					$htmlshift=0;

					foreach ($blockmatches as $textoffset => $textlength) {
						while ( isset($nexttagmatch) && ($nexttagmatch[1]<=($textoffset+$texttohtml)) ) { // keep text and html in sync
							$texttohtml+=strlen($nexttagmatch[0]);
							$nexttagmatch=array_shift($tagmatches);
						}
						
						while (1) {
							$replacepart=$textlength;
							if (isset($nexttagmatch))
								$replacepart=min($replacepart, $nexttagmatch[1]-($textoffset+$texttohtml)); // stop replacing early if we hit an HTML tag
							
							$replacelength=qa_strlen(substr($text, $textoffset, $replacepart)); // to work with multi-byte characters
							
							$html=substr_replace($html, str_repeat('*', $replacelength), $textoffset+$texttohtml+$htmlshift, $replacepart);
							$htmlshift+=$replacelength-$replacepart; // HTML might have moved around if we replaced multi-byte characters
							
							if ($replacepart>=$textlength)
								break; // we have replaced everything expected, otherwise more left (due to hitting an HTML tag)
							
							$textlength-=$replacepart;
							$textoffset+=$replacepart;
							$texttohtml+=strlen($nexttagmatch[0]);
							$nexttagmatch=array_shift($tagmatches);
						}
					}
				}
				
				if (@$options['showurllinks']) { // we need to ensure here that we don't put new links inside existing ones
					require_once QA_INCLUDE_DIR.'qa-util-string.php';
					
					$htmlunlinkeds=array_reverse(preg_split('|<[Aa]\s+[^>]+>.*</[Aa]\s*>|', $html, -1, PREG_SPLIT_OFFSET_CAPTURE)); // start from end so we substitute correctly
					
					foreach ($htmlunlinkeds as $htmlunlinked) { // and that we don't detect links inside HTML, e.g. <img src="http://...">
						$thishtmluntaggeds=array_reverse(preg_split('/<[^>]*>/', $htmlunlinked[0], -1, PREG_SPLIT_OFFSET_CAPTURE)); // again, start from end
						
						foreach ($thishtmluntaggeds as $thishtmluntagged) {
							$innerhtml=$thishtmluntagged[0];
							
							if (is_numeric(strpos($innerhtml, '://'))) { // quick test first
								$newhtml=qa_html_convert_urls($innerhtml, qa_opt('links_in_new_window'));
								
								$html=substr_replace($html, $newhtml, $htmlunlinked[1]+$thishtmluntagged[1], strlen($innerhtml));
							}
						}
					}
				}
				
			} elseif ($format=='') {
				if (isset($options['blockwordspreg'])) {
					require_once QA_INCLUDE_DIR.'qa-util-string.php';
					$content=qa_block_words_replace($content, $options['blockwordspreg']);
				}
				
				$html=qa_html($content, true);
				
				if (@$options['showurllinks']) {
					require_once QA_INCLUDE_DIR.'qa-app-format.php';
					$html=qa_html_convert_urls($html, qa_opt('links_in_new_window'));
				}
				
			} else
				$html='[no viewer found for format: '.qa_html($format).']'; // for unknown formats
			
			return $html;
		}


		function get_text($content, $format, $options)
		{
			if ($format=='html') {
				$text=strtr($content, "\n\r\t", '   '); // convert all white space in HTML to spaces
				$text=preg_replace('/<\s*('.$this->htmllineseparators.')[^A-Za-z0-9]/i', "\n\\0", $text); // tags to single new line
				$text=preg_replace('/<\s*('.$this->htmlparagraphseparators.')[^A-Za-z0-9]/i', "\n\n\\0", $text); // tags to double new line
				$text=strip_tags($text); // all tags removed
				$text=preg_replace('/  +/', ' ', $text); // combine multiple spaces into one
				$text=preg_replace('/ *\n */', "\n", $text); // remove spaces either side new lines
				$text=preg_replace('/\n\n\n+/', "\n\n", $text); // more than two new lines combine into two
				$text=strtr($text, array(
					'&#34;' => "\x22",
					'&#38;' => "\x26",
					'&#39;' => "\x27",
					'&#60;' => "\x3C",
					'&#62;' => "\x3E",
					'&nbsp;' => " ",
					'&quot;' => "\x22",
					'&amp;' => "\x26",
					'&lt;' => "\x3C",
					'&gt;' => "\x3E",
				)); // base HTML entities (others should not be stored in database)
				
				$text=trim($text);

			} elseif ($format=='')
				$text=$content;
				
			else
				$text='[no viewer found for format: '.$format.']'; // for unknown formats
				
			if (isset($options['blockwordspreg'])) {
				require_once QA_INCLUDE_DIR.'qa-util-string.php';
				$text=qa_block_words_replace($text, $options['blockwordspreg']);
			}

			return $text;
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/