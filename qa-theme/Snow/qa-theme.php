<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-theme/Snow/qa-theme.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Override some theme functions for Snow theme


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

	class qa_html_theme extends qa_html_theme_base
	{	
		
		function head_script() // change style of WYSIWYG editor to match theme better
		{
			qa_html_theme_base::head_script();
			
			$this->output(
				'<SCRIPT TYPE="text/javascript"><!--',
				'if (qa_wysiwyg_editor_config)',
				'qa_wysiwyg_editor_config.skin="kama";',
				'//--></SCRIPT>'
			);
		}
		
		function nav_user_search() // outputs login form if user not logged in
		{
			if (!qa_is_logged_in()) {
				$login=@$this->content['navigation']['user']['login'];
				
				if (isset($login)) {
					$this->output(
						'<!--[Begin: login form]-->',				
						'<form id="qa-loginform" action="'.$login['url'].'" method="post">',
							'<input type="text" id="qa-userid" name="emailhandle" placeholder="'.trim(qa_lang_html('users/email_handle_label'), ':').'" />',
							'<input type="password" id="qa-password" name="password" placeholder="'.trim(qa_lang_html('users/password_label'), ':').'" />',
							'<div id="qa-rememberbox"><input type="checkbox" name="remember" id="qa-rememberme" value="1"/>',
							'<label for="qa-rememberme" id="qa-remember">'.qa_lang_html('users/remember').'</label></div>',
							'<input type="submit" value="'.$login['label'].'" id="qa-login" name="dologin" />',
						'</form>',				
						'<!--[End: login form]-->'
					);
					
					unset($this->content['navigation']['user']['login']); // removes regular navigation link to log in page
				}
			}
			
			qa_html_theme_base::nav_user_search();
		}
		
		function logged_in() // adds points count after logged in username
		{
			qa_html_theme_base::logged_in();
			
			if (qa_is_logged_in()) {
				$userpoints=qa_get_logged_in_points();
				
				$pointshtml=($userpoints==1)
					? qa_lang_html_sub('main/1_point', '1', '1')
					: qa_lang_html_sub('main/x_points', qa_html(number_format($userpoints)));
						
				$this->output(
					'<SPAN CLASS="qa-logged-in-points">',
					'('.$pointshtml.')',
					'</SPAN>'
				);
			}
		}
    
		function body_header() // adds login bar, user navigation and search at top of page in place of custom header content
		{
			$this->output('<div id="qa-login-bar"><div id="qa-login-group">');
			$this->nav_user_search();
            $this->output('</div></div>');
        }
		
		function header_custom() // allows modification of custom element shown inside header after logo
		{
			if (isset($this->content['body_header'])) {
				$this->output('<DIV CLASS="header-banner">');
				$this->output_raw($this->content['body_header']);
				$this->output('</DIV>');
			}
		}
		
		function header() // removes user navigation and search from header and replaces with custom header content. Also opens new <DIV>s
		{	
			$this->output('<DIV CLASS="qa-header">');
			
			$this->logo();						
			$this->header_clear();
			$this->header_custom();

			$this->output('</DIV> <!-- END qa-header -->', '');

			$this->output('<DIV CLASS="qa-main-shadow">', '');
			$this->output('<DIV CLASS="qa-main-wrapper">', '');
			$this->nav_main_sub();

		}
		
		function footer() // prevent display of regular footer content (see body_suffix()) and replace with closing new <DIV>s
		{
			$this->output('</DIV> <!-- END main-wrapper -->');
			$this->output('</DIV> <!-- END main-shadow -->');		
		}		
		
		function title() // add RSS feed icon after the page title
		{
			qa_html_theme_base::title();
			
			$feed=@$this->content['feed'];
			
			if (!empty($feed))
				$this->output('<a href="'.$feed['url'].'" title="'.@$feed['label'].'"><img src="'.$this->rooturl.'images/rss.jpg" alt="" width="16" height="16" border="0" CLASS="qa-rss-icon"/></a>');
		}
		
		function q_item_stats($q_item) // add view count to question list
		{
			$this->output('<DIV CLASS="qa-q-item-stats">');
			
			$this->voting($q_item);
			$this->a_count($q_item);
			qa_html_theme_base::view_count($q_item);

			$this->output('</DIV>');
		}
		
		function view_count($q_item) // prevent display of view count in the usual place
		{
		}
		
		function body_suffix() // to replace standard Q2A footer
        {
			$this->output('<div class="qa-footer-bottom-group">');
			qa_html_theme_base::footer();
			$this->output('</DIV> <!-- END footer-bottom-group -->', '');
        }
		
		function attribution()
		{
			$this->output(
				'<DIV CLASS="qa-attribution">',
				'&nbsp;| Snow Theme by <a href="http://www.q2amarket.com">Q2A Market</a>',
				'</DIV>'
			);

			qa_html_theme_base::attribution();
		}
		
	}
	 

/*
	Omit PHP closing tag to help avoid accidental output
*/