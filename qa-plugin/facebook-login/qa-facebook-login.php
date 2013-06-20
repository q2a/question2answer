<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/facebook-login/qa-facebook-login.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Login module class for Facebook login plugin


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

	class qa_facebook_login {
		
		function match_source($source)
		{
			return $source=='facebook';
		}

		
		function login_html($tourl, $context)
		{
			$app_id=qa_opt('facebook_app_id');

			if (!strlen($app_id))
				return;
				
			$this->facebook_html(qa_path_absolute('facebook-login', array('to' => $tourl)), false, $context);
		}

		
		function logout_html($tourl)
		{
			$app_id=qa_opt('facebook_app_id');

			if (!strlen($app_id))
				return;
				
			$this->facebook_html($tourl, true, 'menu');
		}
		

		function facebook_html($tourl, $logout, $context)
		{
			if (($context=='login') || ($context=='register'))
				$size='large';
			else
				$size='medium';

?>
      <div id="fb-root" style="display:inline;"></div>
      <script>
        window.fbAsyncInit = function() {
          FB.init({
            appId      : <?php echo qa_js(qa_opt('facebook_app_id'), true)?>,
            status     : true, 
            cookie     : true,
            xfbml      : true,
            oauth      : true
          });

         FB.Event.subscribe('<?php echo $logout ? 'auth.logout' : 'auth.login'?>', function(response) {
           setTimeout("window.location=<?php echo qa_js($tourl)?>", 100);
         });
        };
        (function(d){
           var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
           js = d.createElement('script'); js.id = id; js.async = true;
           js.src = "//connect.facebook.net/en_US/all.js";
           d.getElementsByTagName('head')[0].appendChild(js);
         }(document));
      </script>
      <div class="fb-login-button" style="display:inline; vertical-align:middle;" size="<?php echo $size?>" <?php echo $logout ? 'autologoutlink="true"' : 'scope="email,user_about_me,user_location,user_website"'?>>
      </div>

<?php
		
		}
		
		
		function admin_form()
		{
			$saved=false;
			
			if (qa_clicked('facebook_save_button')) {
				qa_opt('facebook_app_id', qa_post_text('facebook_app_id_field'));
				qa_opt('facebook_app_secret', qa_post_text('facebook_app_secret_field'));
				$saved=true;
			}
			
			$ready=strlen(qa_opt('facebook_app_id')) && strlen(qa_opt('facebook_app_secret'));
			
			return array(
				'ok' => $saved ? 'Facebook application details saved' : null,
				
				'fields' => array(
					array(
						'label' => 'Facebook App ID:',
						'value' => qa_html(qa_opt('facebook_app_id')),
						'tags' => 'name="facebook_app_id_field"',
					),

					array(
						'label' => 'Facebook App Secret:',
						'value' => qa_html(qa_opt('facebook_app_secret')),
						'tags' => 'name="facebook_app_secret_field"',
						'error' => $ready ? null : 'To use Facebook Login, please <a href="http://developers.facebook.com/setup/" target="_blank">set up a Facebook application</a>.',
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'name="facebook_save_button"',
					),
				),
			);
		}
		
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/