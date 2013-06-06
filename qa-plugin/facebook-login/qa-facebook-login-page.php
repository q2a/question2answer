<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/facebook-login/qa-facebook-login-page.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Page which performs Facebook login action


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

	class qa_facebook_login_page {
		
		var $directory;
		var $urltoroot;

		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}

		function match_request($request)
		{
			return ($request=='facebook-login');
		}
		
		function process_request($request)
		{
			if ($request=='facebook-login') {
				$app_id=qa_opt('facebook_app_id');
				$app_secret=qa_opt('facebook_app_secret');
				$tourl=qa_get('to');
				if (!strlen($tourl))
					$tourl=qa_path_absolute('');

				if (strlen($app_id) && strlen($app_secret)) {
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
						'appId'  => $app_id,
						'secret' => $app_secret,
						'cookie' => true,
					));
			
					$fb_userid=$facebook->getUser();
					
					if ($fb_userid) {
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
									'avatar' => strlen(@$user['picture']['data']['url']) ? qa_retrieve_url($user['picture']['data']['url']) : null,
								));

						} catch (FacebookApiException $e) {
						}

					} else {
						qa_redirect_raw($facebook->getLoginUrl(array('redirect_uri' => $tourl)));
					}
				}
				
				qa_redirect_raw($tourl);
			}
		}
		
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/