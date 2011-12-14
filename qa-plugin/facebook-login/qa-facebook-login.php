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
		
		var $directory;
		var $urltoroot;

		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}

		
		function check_login()
		{
			// Based on sample code: http://developers.facebook.com/docs/guides/web
			
			$testfacebook=false;
			
			foreach ($_COOKIE as $key => $value)
				if (substr($key, 0, 5)=='fbsr_')
					$testfacebook=true;
					
			if (!$testfacebook) // to save making a database query for qa_opt() if there's no point
				return;
				
			$app_id=qa_opt('facebook_app_id');
			$app_secret=qa_opt('facebook_app_secret');
			
			if (!(strlen($app_id) && strlen($app_secret)))
				return;
				
			if (!function_exists('json_decode')) { // work around fact that PHP might not have JSON extension installed
				require_once $this->directory.'JSON.php';
				
				function json_decode($json)
				{
					$decoder=new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
					return $decoder->decode($json);
				}
			}
			
			require_once $this->directory.'facebook.php';
			
			$facebook = new Facebook(array(
				'appId'  => qa_opt('facebook_app_id'),
				'secret' => qa_opt('facebook_app_secret'),
				'cookie' => true,
			));
			
			$fb_userid=$facebook->getUser();
			
			if ($fb_userid)
				try {
					$user=$facebook->api('/me?fields=email,name,verified,location,website,about,picture');
				
					if (is_array($user))
						qa_log_in_external_user('facebook', $fb_userid, array(
							'email' => @$user['email'],
							'handle' => @$user['name'],
							'confirmed' => @$user['verified'],
							'name' => @$user['name'],
							'location' => @$user['location']['name'],
							'website' => @$user['website'],
							'about' => @$user['bio'],
							'avatar' => strlen(@$user['picture']) ? qa_retrieve_url($user['picture']) : null,
						));

				} catch (FacebookApiException $e) {
					$facebookuserid=null;
				}
		}
		

		function match_source($source)
		{
			return $source=='facebook';
		}

		
		function login_html($tourl, $context)
		{
			$app_id=qa_opt('facebook_app_id');

			if (!strlen($app_id))
				return;
				
			$this->facebook_html($tourl, false);
		}

		
		function logout_html($tourl)
		{
			$app_id=qa_opt('facebook_app_id');

			if (!strlen($app_id))
				return;
				
			if (isset($_COOKIE['fbsr_'.$app_id])) // check we still have a Facebook cookie ...
				$this->facebook_html($tourl, true);
			else // ... if not, show a standard logout link, since sometimes the redirect to Q2A's logout page doesn't complete
				echo '<A HREF="'.qa_html($tourl).'">'.qa_lang_html('main/nav_logout').'</A>';
		}
		

		function facebook_html($tourl, $logout)
		{

?>
      <div id="fb-root" style="display:inline;"></div>
      <script>
        window.fbAsyncInit = function() {
          FB.init({
            appId      : <?php echo qa_js(qa_opt('facebook_app_id'), true)?>,
            status     : true, 
            cookie     : true,
            xfbml      : true,
            oauth      : true,
          });

         FB.Event.subscribe('<?php echo $logout ? 'auth.logout' : 'auth.login'?>', function(response) {
           window.location=<?php echo qa_js($tourl)?>;
         });
        };
        (function(d){
           var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
           js = d.createElement('script'); js.id = id; js.async = true;
           js.src = "//connect.facebook.net/en_US/all.js";
           d.getElementsByTagName('head')[0].appendChild(js);
         }(document));
      </script>
      <div class="fb-login-button" style="display:inline;" <?php echo $logout ? 'autologoutlink="true"' : 'scope="email,user_about_me,user_location,user_website"'?>>
      	<?php echo qa_lang_html($logout ? 'main/nav_logout' : 'main/nav_login')?>
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
						'tags' => 'NAME="facebook_app_id_field"',
					),

					array(
						'label' => 'Facebook App Secret:',
						'value' => qa_html(qa_opt('facebook_app_secret')),
						'tags' => 'NAME="facebook_app_secret_field"',
						'error' => $ready ? null : 'To use Facebook Login, please <A HREF="http://developers.facebook.com/setup/" TARGET="_blank">set up a Facebook application</A>.',
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="facebook_save_button"',
					),
				),
			);
		}
		
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/