<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/facebook-login/qa-facebook-layer.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Theme layer class for mouseover layer plugin


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
		
		function head_css()
		{
			qa_html_theme_base::head_css();
			
			if (strlen(qa_opt('facebook_app_id')) && strlen(qa_opt('facebook_app_secret')))
				$this->output(
					'<style><!--',
					'.fb-login-button.fb_iframe_widget.fb_hide_iframes span {display:none;}',
					'--></style>'
				);
		}

	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/