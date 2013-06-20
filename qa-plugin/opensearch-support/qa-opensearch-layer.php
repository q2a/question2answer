<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/opensearch-support/qa-opensearch-layer.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Theme layer class for OpenSearch plugin


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

	class qa_html_theme_layer extends qa_html_theme_base {
		
		function head_links()
		{
			qa_html_theme_base::head_links();
			
			$this->output('<link rel="search" type="application/opensearchdescription+xml" title="'.qa_html(qa_opt('site_title')).'" href="'.qa_path_html('opensearch.xml').'"/>');
		}

	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/