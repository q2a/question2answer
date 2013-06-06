<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/opensearch-support/qa-opensearch-page.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Page module class for XML sitemap plugin


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

	class qa_opensearch_xml {
		
		function match_request($request)
		{
			return ($request=='opensearch.xml');
		}

		function process_request($request)
		{
			@ini_set('display_errors', 0); // we don't want to show PHP errors inside XML

			$titlexml=qa_xml(qa_opt('site_title'));
			$template=str_replace('_searchTerms_placeholder_', '{searchTerms}', qa_path_absolute('search', array('q' => '_searchTerms_placeholder_')));

			header('Content-type: text/xml; charset=utf-8');
			
			echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
			echo '<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">'."\n";
			
			echo "\t<ShortName>".$titlexml."</ShortName>\n";
			echo "\t<Description>".qa_xml(qa_lang('main/search_button')).' '.$titlexml."</Description>\n";
			echo "\t".'<Url type="text/html" method="get" template="'.qa_xml($template).'"/>'."\n";
			echo "\t<InputEncoding>UTF-8</InputEncoding>\n";
			
			echo '</OpenSearchDescription>'."\n";
			
			return null;
		}
		
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/