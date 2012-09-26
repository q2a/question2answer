<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-string.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Some useful string-related stuff


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


//	Functions	

	function qa_string_initialize()
/*
	Set up some global tables to be used by other functions in this file
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_utf8punctuation, $qa_utf8removeaccents;
		
		$qa_utf8punctuation=array( // converts UTF-8 punctuation characters to spaces (or in some cases, hyphens)
			"\xC2\xA1" => ' ', // INVERTED EXCLAMATION MARK
			"\xC2\xA6" => ' ', // BROKEN BAR
			"\xC2\xAB" => ' ', // LEFT-POINTING DOUBLE ANGLE QUOTATION MARK
			"\xC2\xB1" => ' ', // PLUS-MINUS SIGN
			"\xC2\xBB" => ' ', // RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK
			"\xC2\xBF" => ' ', // INVERTED QUESTION MARK
			"\xC3\x97" => ' ', // MULTIPLICATION SIGN
			"\xC3\xB7" => ' ', // DIVISION SIGN
	
			"\xE2\x80\x80" => ' ', // EN QUAD
			"\xE2\x80\x81" => ' ', // EM QUAD
			"\xE2\x80\x82" => ' ', // EN SPACE
			"\xE2\x80\x83" => ' ', // EM SPACE
			"\xE2\x80\x84" => ' ', // THREE-PER-EM SPACE
			"\xE2\x80\x85" => ' ', // FOUR-PER-EM SPACE
			"\xE2\x80\x86" => ' ', // SIX-PER-EM SPACE
			"\xE2\x80\x87" => ' ', // FIGURE SPACE
			"\xE2\x80\x88" => ' ', // PUNCTUATION SPACE
			"\xE2\x80\x89" => ' ', // THIN SPACE
			"\xE2\x80\x8A" => ' ', // HAIR SPACE
			"\xE2\x80\x8B" => ' ', // ZERO WIDTH SPACE
			"\xE2\x80\x8C" => ' ', // ZERO WIDTH NON-JOINER
			"\xE2\x80\x8E" => ' ', // LEFT-TO-RIGHT MARK
			"\xE2\x80\x8F" => ' ', // RIGHT-TO-LEFT MARK
			
			"\xE2\x80\x90" => '-', // HYPHEN
			"\xE2\x80\x91" => '-', // NON-BREAKING HYPHEN
			"\xE2\x80\x92" => '-', // FIGURE DASH
			"\xE2\x80\x93" => '-', // EN DASH
			"\xE2\x80\x94" => '-', // EM DASH
			"\xE2\x80\x95" => '-', // HORIZONTAL BAR
	
			"\xE2\x80\x96" => ' ', // DOUBLE VERTICAL LINE
			"\xE2\x80\x98" => ' ', // LEFT SINGLE QUOTATION MARK
			"\xE2\x80\x99" => "'", // RIGHT SINGLE QUOTATION MARK
			"\xE2\x80\x9A" => ' ', // SINGLE LOW-9 QUOTATION MARK
			"\xE2\x80\x9B" => ' ', // SINGLE HIGH-REVERSED-9 QUOTATION MARK
			"\xE2\x80\x9C" => ' ', // LEFT DOUBLE QUOTATION MARK
			"\xE2\x80\x9D" => ' ', // RIGHT DOUBLE QUOTATION MARK
			"\xE2\x80\x9E" => ' ', // DOUBLE LOW-9 QUOTATION MARK
			"\xE2\x80\x9F" => ' ', // DOUBLE HIGH-REVERSED-9 QUOTATION MARK
	
			"\xE2\x80\xA2" => ' ', // BULLET
			"\xE2\x80\xA4" => ' ', // ONE DOT LEADER
			"\xE2\x80\xA5" => ' ', // TWO DOT LEADER
			"\xE2\x80\xA6" => ' ', // HORIZONTAL ELLIPSIS
			"\xE2\x80\xB9" => ' ', // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
			"\xE2\x80\xBA" => ' ', // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
			"\xE2\x80\xBC" => ' ', // DOUBLE EXCLAMATION MARK
			"\xE2\x80\xBD" => ' ', // INTERROBANG
			"\xE2\x81\x87" => ' ', // DOUBLE QUESTION MARK
			"\xE2\x81\x88" => ' ', // QUESTION EXCLAMATION MARK
			"\xE2\x81\x89" => ' ', // EXCLAMATION QUESTION MARK
			
			"\xE3\x80\x80" => ' ', // IDEOGRAPHIC SPACE
			"\xE3\x80\x81" => ' ', // IDEOGRAPHIC COMMA
			"\xE3\x80\x82" => ' ', // IDEOGRAPHIC FULL STOP
		);
		
		$qa_utf8removeaccents=array( // convert UTF-8 accented characters to basic Roman characters
			"\xC3\x80" => 'A', // LATIN CAPITAL LETTER A WITH GRAVE
			"\xC3\x81" => 'A', // LATIN CAPITAL LETTER A WITH ACUTE
			"\xC3\x82" => 'A', // LATIN CAPITAL LETTER A WITH CIRCUMFLEX
			"\xC3\x83" => 'A', // LATIN CAPITAL LETTER A WITH TILDE
			"\xC3\x84" => 'A', // LATIN CAPITAL LETTER A WITH DIAERESIS
			"\xC3\x85" => 'A', // LATIN CAPITAL LETTER A WITH RING ABOVE
			"\xC3\x86" => 'AE', // LATIN CAPITAL LETTER AE
			"\xC3\x87" => 'C', // LATIN CAPITAL LETTER C WITH CEDILLA
			"\xC3\x88" => 'E', // LATIN CAPITAL LETTER E WITH GRAVE
			"\xC3\x89" => 'E', // LATIN CAPITAL LETTER E WITH ACUTE
			"\xC3\x8A" => 'E', // LATIN CAPITAL LETTER E WITH CIRCUMFLEX
			"\xC3\x8B" => 'E', // LATIN CAPITAL LETTER E WITH DIAERESIS
			"\xC3\x8C" => 'I', // LATIN CAPITAL LETTER I WITH GRAVE
			"\xC3\x8D" => 'I', // LATIN CAPITAL LETTER I WITH ACUTE
			"\xC3\x8E" => 'I', // LATIN CAPITAL LETTER I WITH CIRCUMFLEX
			"\xC3\x8F" => 'I', // LATIN CAPITAL LETTER I WITH DIAERESIS
			"\xC3\x91" => 'N', // LATIN CAPITAL LETTER N WITH TILDE
			"\xC3\x92" => 'O', // LATIN CAPITAL LETTER O WITH GRAVE
			"\xC3\x93" => 'O', // LATIN CAPITAL LETTER O WITH ACUTE
			"\xC3\x94" => 'O', // LATIN CAPITAL LETTER O WITH CIRCUMFLEX
			"\xC3\x95" => 'O', // LATIN CAPITAL LETTER O WITH TILDE
			"\xC3\x96" => 'O', // LATIN CAPITAL LETTER O WITH DIAERESIS
			"\xC3\x98" => 'O', // LATIN CAPITAL LETTER O WITH STROKE
			"\xC3\x99" => 'U', // LATIN CAPITAL LETTER U WITH GRAVE
			"\xC3\x9A" => 'U', // LATIN CAPITAL LETTER U WITH ACUTE
			"\xC3\x9B" => 'U', // LATIN CAPITAL LETTER U WITH CIRCUMFLEX
			"\xC3\x9C" => 'U', // LATIN CAPITAL LETTER U WITH DIAERESIS
			"\xC3\x9D" => 'Y', // LATIN CAPITAL LETTER Y WITH ACUTE
			"\xC3\x9F" => 'ss', // LATIN SMALL LETTER SHARP S
			"\xC3\xA0" => 'a', // LATIN SMALL LETTER A WITH GRAVE
			"\xC3\xA1" => 'a', // LATIN SMALL LETTER A WITH ACUTE
			"\xC3\xA2" => 'a', // LATIN SMALL LETTER A WITH CIRCUMFLEX
			"\xC3\xA3" => 'a', // LATIN SMALL LETTER A WITH TILDE
			"\xC3\xA4" => 'a', // LATIN SMALL LETTER A WITH DIAERESIS
			"\xC3\xA5" => 'a', // LATIN SMALL LETTER A WITH RING ABOVE
			"\xC3\xA6" => 'ae', // LATIN SMALL LETTER AE
			"\xC3\xA7" => 'c', // LATIN SMALL LETTER C WITH CEDILLA
			"\xC3\xA8" => 'e', // LATIN SMALL LETTER E WITH GRAVE
			"\xC3\xA9" => 'e', // LATIN SMALL LETTER E WITH ACUTE
			"\xC3\xAA" => 'e', // LATIN SMALL LETTER E WITH CIRCUMFLEX
			"\xC3\xAB" => 'e', // LATIN SMALL LETTER E WITH DIAERESIS
			"\xC3\xAC" => 'i', // LATIN SMALL LETTER I WITH GRAVE
			"\xC3\xAD" => 'i', // LATIN SMALL LETTER I WITH ACUTE
			"\xC3\xAE" => 'i', // LATIN SMALL LETTER I WITH CIRCUMFLEX
			"\xC3\xAF" => 'i', // LATIN SMALL LETTER I WITH DIAERESIS
			"\xC3\xB1" => 'n', // LATIN SMALL LETTER N WITH TILDE
			"\xC3\xB2" => 'o', // LATIN SMALL LETTER O WITH GRAVE
			"\xC3\xB3" => 'o', // LATIN SMALL LETTER O WITH ACUTE
			"\xC3\xB4" => 'o', // LATIN SMALL LETTER O WITH CIRCUMFLEX
			"\xC3\xB5" => 'o', // LATIN SMALL LETTER O WITH TILDE
			"\xC3\xB6" => 'o', // LATIN SMALL LETTER O WITH DIAERESIS
			"\xC3\xB8" => 'o', // LATIN SMALL LETTER O WITH STROKE
			"\xC3\xB9" => 'u', // LATIN SMALL LETTER U WITH GRAVE
			"\xC3\xBA" => 'u', // LATIN SMALL LETTER U WITH ACUTE
			"\xC3\xBB" => 'u', // LATIN SMALL LETTER U WITH CIRCUMFLEX
			"\xC3\xBC" => 'u', // LATIN SMALL LETTER U WITH DIAERESIS
			"\xC3\xBD" => 'y', // LATIN SMALL LETTER Y WITH ACUTE
			"\xC3\xBF" => 'y', // LATIN SMALL LETTER Y WITH DIAERESIS
			"\xC4\x80" => 'A', // LATIN CAPITAL LETTER A WITH MACRON
			"\xC4\x81" => 'a', // LATIN SMALL LETTER A WITH MACRON
			"\xC4\x82" => 'A', // LATIN CAPITAL LETTER A WITH BREVE
			"\xC4\x83" => 'a', // LATIN SMALL LETTER A WITH BREVE
			"\xC4\x84" => 'A', // LATIN CAPITAL LETTER A WITH OGONEK
			"\xC4\x85" => 'a', // LATIN SMALL LETTER A WITH OGONEK
			"\xC4\x86" => 'C', // LATIN CAPITAL LETTER C WITH ACUTE
			"\xC4\x87" => 'c', // LATIN SMALL LETTER C WITH ACUTE
			"\xC4\x88" => 'C', // LATIN CAPITAL LETTER C WITH CIRCUMFLEX
			"\xC4\x89" => 'c', // LATIN SMALL LETTER C WITH CIRCUMFLEX
			"\xC4\x8A" => 'C', // LATIN CAPITAL LETTER C WITH DOT ABOVE
			"\xC4\x8B" => 'c', // LATIN SMALL LETTER C WITH DOT ABOVE
			"\xC4\x8C" => 'C', // LATIN CAPITAL LETTER C WITH CARON
			"\xC4\x8D" => 'c', // LATIN SMALL LETTER C WITH CARON
			"\xC4\x8E" => 'D', // LATIN CAPITAL LETTER D WITH CARON
			"\xC4\x8F" => 'd', // LATIN SMALL LETTER D WITH CARON
			"\xC4\x90" => 'D', // LATIN CAPITAL LETTER D WITH STROKE
			"\xC4\x91" => 'd', // LATIN SMALL LETTER D WITH STROKE
			"\xC4\x92" => 'E', // LATIN CAPITAL LETTER E WITH MACRON
			"\xC4\x93" => 'e', // LATIN SMALL LETTER E WITH MACRON
			"\xC4\x94" => 'E', // LATIN CAPITAL LETTER E WITH BREVE
			"\xC4\x95" => 'e', // LATIN SMALL LETTER E WITH BREVE
			"\xC4\x96" => 'E', // LATIN CAPITAL LETTER E WITH DOT ABOVE
			"\xC4\x97" => 'e', // LATIN SMALL LETTER E WITH DOT ABOVE
			"\xC4\x98" => 'E', // LATIN CAPITAL LETTER E WITH OGONEK
			"\xC4\x99" => 'e', // LATIN SMALL LETTER E WITH OGONEK
			"\xC4\x9A" => 'E', // LATIN CAPITAL LETTER E WITH CARON
			"\xC4\x9B" => 'e', // LATIN SMALL LETTER E WITH CARON
			"\xC4\x9C" => 'G', // LATIN CAPITAL LETTER G WITH CIRCUMFLEX
			"\xC4\x9D" => 'g', // LATIN SMALL LETTER G WITH CIRCUMFLEX
			"\xC4\x9E" => 'G', // LATIN CAPITAL LETTER G WITH BREVE
			"\xC4\x9F" => 'g', // LATIN SMALL LETTER G WITH BREVE
			"\xC4\xA0" => 'G', // LATIN CAPITAL LETTER G WITH DOT ABOVE
			"\xC4\xA1" => 'g', // LATIN SMALL LETTER G WITH DOT ABOVE
			"\xC4\xA2" => 'G', // LATIN CAPITAL LETTER G WITH CEDILLA
			"\xC4\xA3" => 'g', // LATIN SMALL LETTER G WITH CEDILLA
			"\xC4\xA4" => 'H', // LATIN CAPITAL LETTER H WITH CIRCUMFLEX
			"\xC4\xA5" => 'h', // LATIN SMALL LETTER H WITH CIRCUMFLEX
			"\xC4\xA6" => 'H', // LATIN CAPITAL LETTER H WITH STROKE
			"\xC4\xA7" => 'h', // LATIN SMALL LETTER H WITH STROKE
			"\xC4\xA8" => 'I', // LATIN CAPITAL LETTER I WITH TILDE
			"\xC4\xA9" => 'i', // LATIN SMALL LETTER I WITH TILDE
			"\xC4\xAA" => 'I', // LATIN CAPITAL LETTER I WITH MACRON
			"\xC4\xAB" => 'i', // LATIN SMALL LETTER I WITH MACRON
			"\xC4\xAC" => 'I', // LATIN CAPITAL LETTER I WITH BREVE
			"\xC4\xAD" => 'i', // LATIN SMALL LETTER I WITH BREVE
			"\xC4\xAE" => 'I', // LATIN CAPITAL LETTER I WITH OGONEK
			"\xC4\xAF" => 'i', // LATIN SMALL LETTER I WITH OGONEK
			"\xC4\xB0" => 'I', // LATIN CAPITAL LETTER I WITH DOT ABOVE
			"\xC4\xB1" => 'i', // LATIN SMALL LETTER DOTLESS I
			"\xC4\xB2" => 'IJ', // LATIN CAPITAL LIGATURE IJ
			"\xC4\xB3" => 'ij', // LATIN SMALL LIGATURE IJ
			"\xC4\xB4" => 'j', // LATIN CAPITAL LETTER J WITH CIRCUMFLEX
			"\xC4\xB5" => 'j', // LATIN SMALL LETTER J WITH CIRCUMFLEX
			"\xC4\xB6" => 'K', // LATIN CAPITAL LETTER K WITH CEDILLA
			"\xC4\xB7" => 'k', // LATIN SMALL LETTER K WITH CEDILLA
			"\xC4\xB9" => 'L', // LATIN CAPITAL LETTER L WITH ACUTE
			"\xC4\xBA" => 'l', // LATIN SMALL LETTER L WITH ACUTE
			"\xC4\xBB" => 'L', // LATIN CAPITAL LETTER L WITH CEDILLA
			"\xC4\xBC" => 'l', // LATIN SMALL LETTER L WITH CEDILLA
			"\xC4\xBD" => 'L', // LATIN CAPITAL LETTER L WITH CARON
			"\xC4\xBE" => 'l', // LATIN SMALL LETTER L WITH CARON
			"\xC4\xBF" => 'L', // LATIN CAPITAL LETTER L WITH MIDDLE DOT
			"\xC5\x80" => 'l', // LATIN SMALL LETTER L WITH MIDDLE DOT
			"\xC5\x81" => 'L', // LATIN CAPITAL LETTER L WITH STROKE
			"\xC5\x82" => 'l', // LATIN SMALL LETTER L WITH STROKE
			"\xC5\x83" => 'N', // LATIN CAPITAL LETTER N WITH ACUTE
			"\xC5\x84" => 'n', // LATIN SMALL LETTER N WITH ACUTE
			"\xC5\x85" => 'N', // LATIN CAPITAL LETTER N WITH CEDILLA
			"\xC5\x86" => 'n', // LATIN SMALL LETTER N WITH CEDILLA
			"\xC5\x87" => 'N', // LATIN CAPITAL LETTER N WITH CARON
			"\xC5\x88" => 'n', // LATIN SMALL LETTER N WITH CARON
			"\xC5\x8C" => 'O', // LATIN CAPITAL LETTER O WITH MACRON
			"\xC5\x8D" => 'o', // LATIN SMALL LETTER O WITH MACRON
			"\xC5\x8E" => 'O', // LATIN CAPITAL LETTER O WITH BREVE
			"\xC5\x8F" => 'o', // LATIN SMALL LETTER O WITH BREVE
			"\xC5\x90" => 'O', // LATIN CAPITAL LETTER O WITH DOUBLE ACUTE
			"\xC5\x91" => 'o', // LATIN SMALL LETTER O WITH DOUBLE ACUTE
			"\xC5\x92" => 'OE', // LATIN CAPITAL LIGATURE OE
			"\xC5\x93" => 'oe', // LATIN SMALL LIGATURE OE
			"\xC5\x94" => 'R', // LATIN CAPITAL LETTER R WITH ACUTE
			"\xC5\x95" => 'r', // LATIN SMALL LETTER R WITH ACUTE
			"\xC5\x96" => 'R', // LATIN CAPITAL LETTER R WITH CEDILLA
			"\xC5\x97" => 'r', // LATIN SMALL LETTER R WITH CEDILLA
			"\xC5\x98" => 'R', // LATIN CAPITAL LETTER R WITH CARON
			"\xC5\x99" => 'r', // LATIN SMALL LETTER R WITH CARON
			"\xC5\x9A" => 'S', // LATIN CAPITAL LETTER S WITH ACUTE
			"\xC5\x9B" => 's', // LATIN SMALL LETTER S WITH ACUTE
			"\xC5\x9C" => 'S', // LATIN CAPITAL LETTER S WITH CIRCUMFLEX
			"\xC5\x9D" => 's', // LATIN SMALL LETTER S WITH CIRCUMFLEX
			"\xC5\x9E" => 'S', // LATIN CAPITAL LETTER S WITH CEDILLA
			"\xC5\x9F" => 's', // LATIN SMALL LETTER S WITH CEDILLA
			"\xC5\xA0" => 'S', // LATIN CAPITAL LETTER S WITH CARON
			"\xC5\xA1" => 's', // LATIN SMALL LETTER S WITH CARON
			"\xC5\xA2" => 'T', // LATIN CAPITAL LETTER T WITH CEDILLA
			"\xC5\xA3" => 't', // LATIN SMALL LETTER T WITH CEDILLA
			"\xC5\xA4" => 'T', // LATIN CAPITAL LETTER T WITH CARON
			"\xC5\xA5" => 't', // LATIN SMALL LETTER T WITH CARON
			"\xC5\xA6" => 'T', // LATIN CAPITAL LETTER T WITH STROKE
			"\xC5\xA7" => 't', // LATIN SMALL LETTER T WITH STROKE
			"\xC5\xA8" => 'U', // LATIN CAPITAL LETTER U WITH TILDE
			"\xC5\xA9" => 'u', // LATIN SMALL LETTER U WITH TILDE
			"\xC5\xAA" => 'U', // LATIN CAPITAL LETTER U WITH MACRON
			"\xC5\xAB" => 'u', // LATIN SMALL LETTER U WITH MACRON
			"\xC5\xAC" => 'U', // LATIN CAPITAL LETTER U WITH BREVE
			"\xC5\xAD" => 'u', // LATIN SMALL LETTER U WITH BREVE
			"\xC5\xAE" => 'U', // LATIN CAPITAL LETTER U WITH RING ABOVE
			"\xC5\xAF" => 'u', // LATIN SMALL LETTER U WITH RING ABOVE
			"\xC5\xB0" => 'U', // LATIN CAPITAL LETTER U WITH DOUBLE ACUTE
			"\xC5\xB1" => 'u', // LATIN SMALL LETTER U WITH DOUBLE ACUTE
			"\xC5\xB2" => 'U', // LATIN CAPITAL LETTER U WITH OGONEK
			"\xC5\xB3" => 'u', // LATIN SMALL LETTER U WITH OGONEK
			"\xC5\xB4" => 'W', // LATIN CAPITAL LETTER W WITH CIRCUMFLEX
			"\xC5\xB5" => 'w', // LATIN SMALL LETTER W WITH CIRCUMFLEX
			"\xC5\xB6" => 'Y', // LATIN CAPITAL LETTER Y WITH CIRCUMFLEX
			"\xC5\xB7" => 'y', // LATIN SMALL LETTER Y WITH CIRCUMFLEX
			"\xC5\xB8" => 'Y', // LATIN CAPITAL LETTER Y WITH DIAERESIS
			"\xC5\xB9" => 'Z', // LATIN CAPITAL LETTER Z WITH ACUTE
			"\xC5\xBA" => 'z', // LATIN SMALL LETTER Z WITH ACUTE
			"\xC5\xBB" => 'Z', // LATIN CAPITAL LETTER Z WITH DOT ABOVE
			"\xC5\xBC" => 'z', // LATIN SMALL LETTER Z WITH DOT ABOVE
			"\xC5\xBD" => 'Z', // LATIN CAPITAL LETTER Z WITH CARON
			"\xC5\xBE" => 'z', // LATIN SMALL LETTER Z WITH CARON
			"\xC6\x80" => 'b', // LATIN SMALL LETTER B WITH STROKE
			"\xC6\x81" => 'B', // LATIN CAPITAL LETTER B WITH HOOK
			"\xC6\x82" => 'B', // LATIN CAPITAL LETTER B WITH TOPBAR
			"\xC6\x83" => 'b', // LATIN SMALL LETTER B WITH TOPBAR
			"\xC6\x87" => 'C', // LATIN CAPITAL LETTER C WITH HOOK
			"\xC6\x88" => 'c', // LATIN SMALL LETTER C WITH HOOK
			"\xC6\x89" => 'D', // LATIN CAPITAL LETTER AFRICAN D
			"\xC6\x8A" => 'D', // LATIN CAPITAL LETTER D WITH HOOK
			"\xC6\x8B" => 'D', // LATIN CAPITAL LETTER D WITH TOPBAR
			"\xC6\x8C" => 'd', // LATIN SMALL LETTER D WITH TOPBAR
			"\xC6\x91" => 'F', // LATIN CAPITAL LETTER F WITH HOOK
			"\xC6\x92" => 'f', // LATIN SMALL LETTER F WITH HOOK
			"\xC6\x93" => 'G', // LATIN CAPITAL LETTER G WITH HOOKU+0195
			"\xC6\x97" => 'I', // LATIN CAPITAL LETTER I WITH STROKE
			"\xC6\x98" => 'K', // LATIN CAPITAL LETTER K WITH HOOK
			"\xC6\x99" => 'k', // LATIN SMALL LETTER K WITH HOOK
			"\xC6\x9A" => 'l', // LATIN SMALL LETTER L WITH BAR
			"\xC6\x9D" => 'N', // LATIN CAPITAL LETTER N WITH LEFT HOOK
			"\xC6\x9E" => 'n', // LATIN SMALL LETTER N WITH LONG RIGHT LEG
			"\xC6\x9F" => 'O', // LATIN CAPITAL LETTER O WITH MIDDLE TILDE
			"\xC6\xA0" => 'O', // LATIN CAPITAL LETTER O WITH HORN
			"\xC6\xA1" => 'o', // LATIN SMALL LETTER O WITH HORN
			"\xC6\xA2" => 'OI', // LATIN CAPITAL LETTER OI
			"\xC6\xA3" => 'oi', // LATIN SMALL LETTER OI
			"\xC6\xA4" => 'P', // LATIN CAPITAL LETTER P WITH HOOK
			"\xC6\xA5" => 'p', // LATIN SMALL LETTER P WITH HOOK
			"\xC6\xAB" => 't', // LATIN SMALL LETTER T WITH PALATAL HOOK
			"\xC6\xAC" => 'T', // LATIN CAPITAL LETTER T WITH HOOK
			"\xC6\xAD" => 't', // LATIN SMALL LETTER T WITH HOOK
			"\xC6\xAE" => 'T', // LATIN CAPITAL LETTER T WITH RETROFLEX HOOK
			"\xC6\xAF" => 'U', // LATIN CAPITAL LETTER U WITH HORN
			"\xC6\xB0" => 'u', // LATIN SMALL LETTER U WITH HORN
			"\xC6\xB2" => 'V', // LATIN CAPITAL LETTER V WITH HOOK
			"\xC6\xB3" => 'Y', // LATIN CAPITAL LETTER Y WITH HOOK
			"\xC6\xB4" => 'y', // LATIN SMALL LETTER Y WITH HOOK
			"\xC6\xB5" => 'Z', // LATIN CAPITAL LETTER Z WITH STROKE
			"\xC6\xB6" => 'z', // LATIN SMALL LETTER Z WITH STROKE
			"\xC7\x8D" => 'A', // LATIN CAPITAL LETTER A WITH CARON
			"\xC7\x8E" => 'a', // LATIN SMALL LETTER A WITH CARON
			"\xC7\x8F" => 'I', // LATIN CAPITAL LETTER I WITH CARON
			"\xC7\x90" => 'i', // LATIN SMALL LETTER I WITH CARON
			"\xC7\x91" => 'O', // LATIN CAPITAL LETTER O WITH CARON
			"\xC7\x92" => 'o', // LATIN SMALL LETTER O WITH CARON
			"\xC7\x93" => 'U', // LATIN CAPITAL LETTER U WITH CARON
			"\xC7\x94" => 'u', // LATIN SMALL LETTER U WITH CARON
			"\xC7\x95" => 'U', // LATIN CAPITAL LETTER U WITH DIAERESIS AND MACRON
			"\xC7\x96" => 'u', // LATIN SMALL LETTER U WITH DIAERESIS AND MACRON
			"\xC7\x97" => 'U', // LATIN CAPITAL LETTER U WITH DIAERESIS AND ACUTE
			"\xC7\x98" => 'u', // LATIN SMALL LETTER U WITH DIAERESIS AND ACUTE
			"\xC7\x99" => 'U', // LATIN CAPITAL LETTER U WITH DIAERESIS AND CARON
			"\xC7\x9A" => 'u', // LATIN SMALL LETTER U WITH DIAERESIS AND CARON
			"\xC7\x9B" => 'U', // LATIN CAPITAL LETTER U WITH DIAERESIS AND GRAVE
			"\xC7\x9C" => 'u', // LATIN SMALL LETTER U WITH DIAERESIS AND GRAVE
			"\xC7\x9E" => 'A', // LATIN CAPITAL LETTER A WITH DIAERESIS AND MACRON
			"\xC7\x9F" => 'a', // LATIN SMALL LETTER A WITH DIAERESIS AND MACRON
			"\xC7\xA0" => 'A', // LATIN CAPITAL LETTER A WITH DOT ABOVE AND MACRON
			"\xC7\xA1" => 'a', // LATIN SMALL LETTER A WITH DOT ABOVE AND MACRON
			"\xC7\xA2" => 'AE', // LATIN CAPITAL LETTER AE WITH MACRON
			"\xC7\xA3" => 'ae', // LATIN SMALL LETTER AE WITH MACRON
			"\xC7\xA4" => 'G', // LATIN CAPITAL LETTER G WITH STROKE
			"\xC7\xA5" => 'g', // LATIN SMALL LETTER G WITH STROKE
			"\xC7\xA6" => 'G', // LATIN CAPITAL LETTER G WITH CARON
			"\xC7\xA7" => 'g', // LATIN SMALL LETTER G WITH CARON
			"\xC7\xA8" => 'K', // LATIN CAPITAL LETTER K WITH CARON
			"\xC7\xA9" => 'k', // LATIN SMALL LETTER K WITH CARON
			"\xC7\xAA" => 'O', // LATIN CAPITAL LETTER O WITH OGONEK
			"\xC7\xAB" => 'o', // LATIN SMALL LETTER O WITH OGONEK
			"\xC7\xAC" => 'O', // LATIN CAPITAL LETTER O WITH OGONEK AND MACRON
			"\xC7\xAD" => 'o', // LATIN SMALL LETTER O WITH OGONEK AND MACRON
			"\xC7\xB0" => 'j', // LATIN SMALL LETTER J WITH CARON
			"\xC7\xB4" => 'G', // LATIN CAPITAL LETTER G WITH ACUTE
			"\xC7\xB5" => 'g', // LATIN SMALL LETTER G WITH ACUTE
			"\xC7\xB8" => 'N', // LATIN CAPITAL LETTER N WITH GRAVE
			"\xC7\xB9" => 'n', // LATIN SMALL LETTER N WITH GRAVE
			"\xC7\xBA" => 'A', // LATIN CAPITAL LETTER A WITH RING ABOVE AND ACUTE
			"\xC7\xBB" => 'a', // LATIN SMALL LETTER A WITH RING ABOVE AND ACUTE
			"\xC7\xBC" => 'AE', // LATIN CAPITAL LETTER AE WITH ACUTE
			"\xC7\xBD" => 'ae', // LATIN SMALL LETTER AE WITH ACUTE
			"\xC7\xBE" => 'O', // LATIN CAPITAL LETTER O WITH STROKE AND ACUTE
			"\xC7\xBF" => 'o', // LATIN SMALL LETTER O WITH STROKE AND ACUTE
			"\xC8\x80" => 'A', // LATIN CAPITAL LETTER A WITH DOUBLE GRAVE
			"\xC8\x81" => 'a', // LATIN SMALL LETTER A WITH DOUBLE GRAVE
			"\xC8\x82" => 'A', // LATIN CAPITAL LETTER A WITH INVERTED BREVE
			"\xC8\x83" => 'a', // LATIN SMALL LETTER A WITH INVERTED BREVE
			"\xC8\x84" => 'E', // LATIN CAPITAL LETTER E WITH DOUBLE GRAVE
			"\xC8\x85" => 'e', // LATIN SMALL LETTER E WITH DOUBLE GRAVE
			"\xC8\x86" => 'E', // LATIN CAPITAL LETTER E WITH INVERTED BREVE
			"\xC8\x87" => 'e', // LATIN SMALL LETTER E WITH INVERTED BREVE
			"\xC8\x88" => 'I', // LATIN CAPITAL LETTER I WITH DOUBLE GRAVE
			"\xC8\x89" => 'i', // LATIN SMALL LETTER I WITH DOUBLE GRAVE
			"\xC8\x8A" => 'I', // LATIN CAPITAL LETTER I WITH INVERTED BREVE
			"\xC8\x8B" => 'i', // LATIN SMALL LETTER I WITH INVERTED BREVE
			"\xC8\x8C" => 'O', // LATIN CAPITAL LETTER O WITH DOUBLE GRAVE
			"\xC8\x8D" => 'o', // LATIN SMALL LETTER O WITH DOUBLE GRAVE
			"\xC8\x8E" => 'O', // LATIN CAPITAL LETTER O WITH INVERTED BREVE
			"\xC8\x8F" => 'o', // LATIN SMALL LETTER O WITH INVERTED BREVE
			"\xC8\x90" => 'R', // LATIN CAPITAL LETTER R WITH DOUBLE GRAVE
			"\xC8\x91" => 'r', // LATIN SMALL LETTER R WITH DOUBLE GRAVE
			"\xC8\x92" => 'R', // LATIN CAPITAL LETTER R WITH INVERTED BREVE
			"\xC8\x93" => 'r', // LATIN SMALL LETTER R WITH INVERTED BREVE
			"\xC8\x94" => 'U', // LATIN CAPITAL LETTER U WITH DOUBLE GRAVE
			"\xC8\x95" => 'u', // LATIN SMALL LETTER U WITH DOUBLE GRAVE
			"\xC8\x96" => 'U', // LATIN CAPITAL LETTER U WITH INVERTED BREVE
			"\xC8\x97" => 'u', // LATIN SMALL LETTER U WITH INVERTED BREVE
			"\xC8\x98" => 'S', // LATIN CAPITAL LETTER S WITH COMMA BELOW
			"\xC8\x99" => 's', // LATIN SMALL LETTER S WITH COMMA BELOW
			"\xC8\x9A" => 'T', // LATIN CAPITAL LETTER T WITH COMMA BELOW
			"\xC8\x9B" => 't', // LATIN SMALL LETTER T WITH COMMA BELOW
			"\xC8\x9E" => 'H', // LATIN CAPITAL LETTER H WITH CARON
			"\xC8\x9F" => 'h', // LATIN SMALL LETTER H WITH CARON
			"\xC8\xA0" => 'n', // LATIN CAPITAL LETTER N WITH LONG RIGHT LEG
			"\xC8\xA1" => 'd', // LATIN SMALL LETTER D WITH CURL
			"\xC8\xA4" => 'Z', // LATIN CAPITAL LETTER Z WITH HOOK
			"\xC8\xA5" => 'z', // LATIN SMALL LETTER Z WITH HOOK
			"\xC8\xA6" => 'A', // LATIN CAPITAL LETTER A WITH DOT ABOVE
			"\xC8\xA7" => 'a', // LATIN SMALL LETTER A WITH DOT ABOVE
			"\xC8\xA8" => 'E', // LATIN CAPITAL LETTER E WITH CEDILLA
			"\xC8\xA9" => 'e', // LATIN SMALL LETTER E WITH CEDILLA
			"\xC8\xAA" => 'O', // LATIN CAPITAL LETTER O WITH DIAERESIS AND MACRON
			"\xC8\xAB" => 'o', // LATIN SMALL LETTER O WITH DIAERESIS AND MACRON
			"\xC8\xAC" => 'O', // LATIN CAPITAL LETTER O WITH TILDE AND MACRON
			"\xC8\xAD" => 'o', // LATIN SMALL LETTER O WITH TILDE AND MACRON
			"\xC8\xAE" => 'O', // LATIN CAPITAL LETTER O WITH DOT ABOVE
			"\xC8\xAF" => 'o', // LATIN SMALL LETTER O WITH DOT ABOVE
			"\xC8\xB0" => 'O', // LATIN CAPITAL LETTER O WITH DOT ABOVE AND MACRON
			"\xC8\xB1" => 'o', // LATIN SMALL LETTER O WITH DOT ABOVE AND MACRON
			"\xC8\xB2" => 'Y', // LATIN CAPITAL LETTER Y WITH MACRON
			"\xC8\xB3" => 'y', // LATIN SMALL LETTER Y WITH MACRON
			"\xC8\xB4" => 'l', // LATIN SMALL LETTER L WITH CURL
			"\xC8\xB5" => 'n', // LATIN SMALL LETTER N WITH CURL
			"\xC8\xB6" => 't', // LATIN SMALL LETTER T WITH CURL
			"\xC8\xB7" => 'j', // LATIN SMALL LETTER DOTLESS J
		);
	}
	
	
	function qa_string_to_words($string, $tolowercase=true, $delimiters=false, $splitideographs=true, $splithyphens=true)
/*
	Return the UTF-8 input string converted into an array of words, changed $tolowercase (or not).
	Set $delimiters to true to keep the delimiters after each word and tweak what we used for word
	splitting with $splitideographs and $splithyphens.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_utf8punctuation;
		
		if ($tolowercase)
			$string=qa_strtolower($string);
		
		$string=strtr($string, $qa_utf8punctuation);
		
		$separator=QA_PREG_INDEX_WORD_SEPARATOR;
		if ($splithyphens)
			$separator.='|\-';

		if ($delimiters) {
			if ($splitideographs)
				$separator.='|'.QA_PREG_CJK_IDEOGRAPHS_UTF8;
		
		} else {
			$string=preg_replace("/(\S)'(\S)/", '\1\2', $string); // remove apostrophes in words
			
			if ($splitideographs) // put spaces around CJK ideographs so they're treated as separate words
				$string=preg_replace('/'.QA_PREG_CJK_IDEOGRAPHS_UTF8.'/', ' \0 ', $string);
		}
		
		return preg_split('/('.$separator.'+)/', $string, -1, PREG_SPLIT_NO_EMPTY | ($delimiters ? PREG_SPLIT_DELIM_CAPTURE : 0));
	}
	
	
	function qa_string_remove_accents($string)
/*
	Return UTF-8 input $string with all accents (on Roman characters) removed
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_utf8removeaccents;
		
		return strtr($string, $qa_utf8removeaccents);
	}

	
	function qa_tags_to_tagstring($tags)
/*
	Convert an array of tags into a string for storage in the database
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return implode(',', $tags);
	}

	
	function qa_tagstring_to_tags($tagstring)
/*
	Convert a tag string as stored in the database into an array of tags
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return empty($tagstring) ? array() : explode(',', $tagstring);
	}
	

	function qa_shorten_string_line($string, $length)
/*
	Return no more than $length characters from $string after converting it to a single line, by
	removing words from the middle (and especially towards the end)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$string=strtr($string, "\r\n\t", '   ');
		
		if (qa_strlen($string)>$length) {
			$remaining=$length-5;
			
			$words=qa_string_to_words($string, false, true);
			$countwords=count($words);
			
			$prefix='';
			$suffix='';
			
			for ($addword=0; $addword<$countwords; $addword++) {
				$tosuffix=(($addword%3)==1); // order: prefix, suffix, prefix, prefix, suffix, prefix, ...
				
				if ($tosuffix)
					$word=array_pop($words);
				else
					$word=array_shift($words);
				
				if (qa_strlen($word)>$remaining)
					break;
				
				if ($tosuffix)
					$suffix=$word.$suffix;
				else
					$prefix.=$word;
				
				$remaining-=qa_strlen($word);
			}
			
			$string=$prefix.' ... '.$suffix;
		}
		
		return $string;
	}

	
	function qa_block_words_explode($wordstring)
/*
	Return an array of the words within $wordstring, each of which can contain asterisks
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return preg_split('/'.QA_PREG_BLOCK_WORD_SEPARATOR.'+/', $wordstring, -1, PREG_SPLIT_NO_EMPTY);
	}
	
	
	function qa_block_words_to_preg($wordsstring)
/*
	Return a regular expression fragment corresponding to the block words $wordstring
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$blockwords=qa_block_words_explode($wordsstring);
		$patterns=array();
		
		foreach ($blockwords as $blockword) { // * in rule maps to [^ ]* in regular expression
			$pattern=str_replace('\\*', '[^ ]*', preg_quote(qa_strtolower($blockword), '/'));
			
			if (!preg_match('/^('.QA_PREG_CJK_IDEOGRAPHS_UTF8.')/', $blockword))
				$pattern='(?<= )'.$pattern; // assert leading word delimiter if pattern does not start with CJK
				
			if (!preg_match('/('.QA_PREG_CJK_IDEOGRAPHS_UTF8.')$/', $blockword))
				$pattern=$pattern.'(?= )'; // assert trailing word delimiter if pattern does not end with CJK
				
			$patterns[]=$pattern;
		}
		
		return implode('|', $patterns);
	}

	
	function qa_block_words_match_all($string, $wordspreg)
/*
	Return an array of matches of the regular expression fragment $wordspreg in $string, [offset] => [length]
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_utf8punctuation, $qa_utf8punctuation_keeplength;
		
		if (strlen($wordspreg)) {
			// replace all word separators with spaces of same length
			
			if (!is_array($qa_utf8punctuation_keeplength)) {
				$qa_utf8punctuation_keeplength=array();
				foreach ($qa_utf8punctuation as $key => $value)
					$qa_utf8punctuation_keeplength[$key]=str_repeat(' ', strlen($key));
			}
			
			$string=strtr(qa_strtolower($string), $qa_utf8punctuation_keeplength);
				// assumes UTF-8 case conversion in qa_strtolower does not change byte length
			$string=preg_replace('/'.QA_PREG_BLOCK_WORD_SEPARATOR.'/', ' ', $string);
			
			preg_match_all('/'.$wordspreg.'/', ' '.$string.' ', $pregmatches, PREG_OFFSET_CAPTURE);
			
			$outmatches=array();
			foreach ($pregmatches[0] as $pregmatch)
				$outmatches[$pregmatch[1]-1]=strlen($pregmatch[0]);
				
			return $outmatches;
		}
		
		return array();
	}
	
	
	function qa_block_words_replace($string, $wordspreg, $character='*')
/*
	Return $string with any words matching the regular expression fragment $wordspreg replaced with repeated $character
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if (strlen($wordspreg)) {
			$matches=qa_block_words_match_all($string, $wordspreg);
			krsort($matches, SORT_NUMERIC);
			
			foreach ($matches as $start => $length) // get length again below to deal with multi-byte characters
				$string=substr_replace($string, str_repeat($character, qa_strlen(substr($string, $start, $length))), $start, $length);
		}
			
		return $string;
	}
	
	
	function qa_random_alphanum($length)
/*
	Return a random alphanumeric string (base 36) of $length
*/
	{
		$string='';
		
		while (strlen($string)<$length)
			$string.=str_pad(base_convert(mt_rand(0, 46655), 10, 36), 3, '0', STR_PAD_LEFT);
			
		return substr($string, 0, $length);
	}

	
	function qa_email_validate($email)
/*
	Return true or false to indicate whether $email is a valid email (this is pretty flexible compared to most real emails out there)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return preg_match("/^[\-\!\#\$\%\&\'\*\+\/\=\?\_\`\{\|\}\~a-zA-Z0-9\.\^]+\@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\.\-]+$/", $email) ? true : false;
	}
	
	
	function qa_strlen($string)
/*
	Return the number of characters in $string, preferably using PHP's multibyte string functions
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return function_exists('mb_strlen') ? mb_strlen($string, 'UTF-8') : strlen($string);
	}


	function qa_strtolower($string)
/*
	Return a lower case version of $string, preferably using PHP's multibyte string functions
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return function_exists('mb_strtolower') ? mb_strtolower($string, 'UTF-8') : strtolower($string);
	}
	
	
	function qa_substr($string, $start, $length=2147483647)
/*
	Return $length characters from $string, starting from $start, preferably using PHP's multibyte string functions
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return function_exists('mb_substr') ? mb_substr($string, $start, $length, 'UTF-8') : substr($string, $start, $length);
	}
	

	function qa_has_multibyte()
/*
	Return whether this version of PHP has been compiled with multibyte string support
*/
	{
		return function_exists('mb_strlen') && function_exists('mb_strtolower');
	}


	function qa_string_matches_one($string, $matches)
/*
	Return true if one of the elements in $matches is contained within $string
*/
	{
		if (strlen($string))
			foreach ($matches as $match)
				if (strpos($string, $match)!==false)
					return true;
				
		return false;
	}

	
//	Some static definitions and initialization

	@define('QA_PREG_INDEX_WORD_SEPARATOR', '[\n\r\t\ \!\"\\\'\(\)\*\+\,\.\/\:\;\<\=\>\?\[\\\\\]\^\`\{\|\}\~]');
		// Notable exclusions here: $ & - _ # % @
		
	@define('QA_PREG_BLOCK_WORD_SEPARATOR', '[\n\r\t\ \!\"\\\'\(\)\+\,\.\/\:\;\<\=\>\?\[\\\\\]\^\`\{\|\}\~\$\&\-\_\#\%\@]');
		// Asterisk (*) excluded here because it's used to match anything
		
	@define('QA_PREG_CJK_IDEOGRAPHS_UTF8', '\xE2[\xBA-\xBF][\x80-\xBF]|\xE3[\x80\x88-\xBF][\x80-\xBF]|[\xE4-\xE9][\x80-\xBF][\x80-\xBF]|\xEF[\xA4-\xAB][\x80-\xBF]|\xF0[\xA0-\xAF][\x80-\xBF][\x80-\xBF]');
		// Pattern to match Chinese/Japanese/Korean ideographic symbols in UTF-8 encoding
	
	qa_string_initialize();


/*
	Omit PHP closing tag to help avoid accidental output
*/