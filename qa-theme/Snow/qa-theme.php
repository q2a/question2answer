<?php


	class qa_html_theme extends qa_html_theme_base
	{	

		function head_script() // change style of WYSIWYG editor to match theme better
		{
			qa_html_theme_base::head_script();
			
			$this->output(
				'<script type="text/javascript">',
				"if (typeof qa_wysiwyg_editor_config == 'object')",
				"\tqa_wysiwyg_editor_config.skin='kama';",
				'</script>'
			);
		}
		
		function nav_user_search() // outputs login form if user not logged in
		{
			if (!qa_is_logged_in()) {
				$login=@$this->content['navigation']['user']['login'];
				
				if (isset($login) && !QA_FINAL_EXTERNAL_USERS) {
					$this->output(
						'<!--[Begin: login form]-->',				
						'<form id="qa-loginform" action="'.$login['url'].'" method="post">',
							'<input type="text" id="qa-userid" name="emailhandle" placeholder="'.trim(qa_lang_html(qa_opt('allow_login_email_only') ? 'users/email_label' : 'users/email_handle_label'), ':').'" />',
							'<input type="password" id="qa-password" name="password" placeholder="'.trim(qa_lang_html('users/password_label'), ':').'" />',
							'<div id="qa-rememberbox"><input type="checkbox" name="remember" id="qa-rememberme" value="1"/>',
							'<label for="qa-rememberme" id="qa-remember">'.qa_lang_html('users/remember').'</label></div>',
							'<input type="hidden" name="code" value="'.qa_html(qa_get_form_security_code('login')).'"/>',
							'<input type="submit" value="'.$login['label'].'" id="qa-login" name="dologin" />',
						'</form>',				
						'<!--[End: login form]-->'
					);
					
					unset($this->content['navigation']['user']['login']); // removes regular navigation link to log in page
				}
			}
			
			qa_html_theme_base::nav_user_search();
		}
		
		function logged_in() 
		{
			if (qa_is_logged_in()) // output user avatar to login bar
				$this->output(
					'<div class="qa-logged-in-avatar">',
					QA_FINAL_EXTERNAL_USERS
					? qa_get_external_avatar_html(qa_get_logged_in_userid(), 24, true)
					: qa_get_user_avatar_html(qa_get_logged_in_flags(), qa_get_logged_in_email(), qa_get_logged_in_handle(),
						qa_get_logged_in_user_field('avatarblobid'), qa_get_logged_in_user_field('avatarwidth'), qa_get_logged_in_user_field('avatarheight'),
						24, true),
            		'</div>'
            	);				
			
			qa_html_theme_base::logged_in();
			
			if (qa_is_logged_in()) { // adds points count after logged in username
				$userpoints=qa_get_logged_in_points();
				
				$pointshtml=($userpoints==1)
					? qa_lang_html_sub('main/1_point', '1', '1')
					: qa_lang_html_sub('main/x_points', qa_html(number_format($userpoints)));
						
				$this->output(
					'<span class="qa-logged-in-points">',
					'('.$pointshtml.')',
					'</span>'
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
				$this->output('<div class="header-banner">');
				$this->output_raw($this->content['body_header']);
				$this->output('</div>');
			}
		}
		
		function header() // removes user navigation and search from header and replaces with custom header content. Also opens new <div>s
		{	
			$this->output('<div class="qa-header">');
			
			$this->logo();						
			$this->header_clear();
			$this->header_custom();

			$this->output('</div> <!-- END qa-header -->', '');

			$this->output('<div class="qa-main-shadow">', '');
			$this->output('<div class="qa-main-wrapper">', '');
			$this->nav_main_sub();

		}
		
		function sidepanel() // removes sidebar for user profile pages
		{
			if ($this->template!='user')
				qa_html_theme_base::sidepanel();
		}
		
		function footer() // prevent display of regular footer content (see body_suffix()) and replace with closing new <div>s
		{
			$this->output('</div> <!-- END main-wrapper -->');
			$this->output('</div> <!-- END main-shadow -->');		
		}		
		
		function title() // add RSS feed icon after the page title
		{
			qa_html_theme_base::title();
			
			$feed=@$this->content['feed'];
			
			if (!empty($feed))
				$this->output('<a href="'.$feed['url'].'" title="'.@$feed['label'].'"><img src="'.$this->rooturl.'images/rss.jpg" alt="" width="16" height="16" border="0" class="qa-rss-icon"/></a>');
		}
		
		function q_item_stats($q_item) // add view count to question list
		{
			$this->output('<div class="qa-q-item-stats">');
			
			$this->voting($q_item);
			$this->a_count($q_item);
			qa_html_theme_base::view_count($q_item);

			$this->output('</div>');
		}
		
		function view_count($q_item) // prevent display of view count in the usual place
		{	
			if ($this->template=='question')
				qa_html_theme_base::view_count($q_item);
		}
		
		function body_suffix() // to replace standard Q2A footer
        {
			$this->output('<div class="qa-footer-bottom-group">');
			qa_html_theme_base::footer();
			$this->output('</div> <!-- END footer-bottom-group -->', '');
        }
		
		function attribution()
		{
			$this->output(
				'<div class="qa-attribution">',
				'&nbsp;| Snow Theme by <a href="http://www.q2amarket.com">Q2A Market</a>',
				'</div>'
			);

			qa_html_theme_base::attribution();
		}
		
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/