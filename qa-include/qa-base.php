<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-base.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Sets up Q2A environment, plus many globally useful functions


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

	
	define('QA_VERSION', '1.6.3'); // also used as suffix for .js and .css requests
	define('QA_BUILD_DATE', '2014-01-19');

//	Execution section of this file - remainder contains function definitions

	qa_initialize_php();
	qa_initialize_constants_1();

	if (defined('QA_WORDPRESS_LOAD_FILE')) // if relevant, load WordPress integration in global scope
		require_once QA_WORDPRESS_LOAD_FILE;

	qa_initialize_constants_2();
	qa_initialize_modularity();
	qa_register_core_modules();
	qa_load_plugin_files();
	qa_load_override_files();

	require_once QA_INCLUDE_DIR.'qa-db.php';

	qa_db_allow_connect();
	

//	Version comparison functions

	function qa_version_to_float($version)
/*
	Converts the $version string (e.g. 1.6.2.2) to a floating point that can be used for greater/lesser comparisons
	(PHP's version_compare() function is not quite suitable for our needs)
*/
	{
		$value=0.0;

		if (preg_match('/[0-9\.]+/', $version, $matches)) {
			$parts=explode('.', $matches[0]);
			$units=1.0;
			
			foreach ($parts as $part) {
				$value+=min($part, 999)*$units;
				$units/=1000;
			}
		}

		return $value;
	}
	
	
	function qa_qa_version_below($version)
/*
	Returns true if the current Q2A version is lower than $version, if both are valid version strings for qa_version_to_float()
*/
	{
		$minqa=qa_version_to_float($version);
		$thisqa=qa_version_to_float(QA_VERSION);
		
		return $minqa && $thisqa && ($thisqa<$minqa);
	}
	
	
	function qa_php_version_below($version)
/*
	Returns true if the current PHP version is lower than $version, if both are valid version strings for qa_version_to_float()
*/
	{
		$minphp=qa_version_to_float($version);
		$thisphp=qa_version_to_float(phpversion());
		
		return $minphp && $thisphp && ($thisphp<$minphp);
	}
	

//	Initialization functions called above

	function qa_initialize_php()
/*
	Set up and verify the PHP environment for Q2A, including unregistering globals if necessary
*/
	{
		if (qa_php_version_below('4.3'))
			qa_fatal_error('This requires PHP 4.3 or later');
	
		error_reporting(E_ALL); // be ultra-strict about error checking
		
		@ini_set('magic_quotes_runtime', 0);
		
		@setlocale(LC_CTYPE, 'C'); // prevent strtolower() et al affecting non-ASCII characters (appears important for IIS)
		
		if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get'))
			@date_default_timezone_set(@date_default_timezone_get()); // prevent PHP notices where default timezone not set
			
		if (ini_get('register_globals')) {
			$checkarrays=array('_ENV', '_GET', '_POST', '_COOKIE', '_SERVER', '_FILES', '_REQUEST', '_SESSION'); // unregister globals if they're registered
			$keyprotect=array_flip(array_merge($checkarrays, array('GLOBALS')));
			
			foreach ($checkarrays as $checkarray)
				if ( isset(${$checkarray}) && is_array(${$checkarray}) )
					foreach (${$checkarray} as $checkkey => $checkvalue)
						if (isset($keyprotect[$checkkey]))
							qa_fatal_error('My superglobals are not for overriding');
						else
							unset($GLOBALS[$checkkey]);
		}
	}
	
	
	function qa_initialize_constants_1()
/*
	First stage of setting up Q2A constants, before (if necessary) loading WordPress integration
*/
	{
		global $qa_request_map;
		
		define('QA_CATEGORY_DEPTH', 4); // you can't change this number!

		if (!defined('QA_BASE_DIR'))
			define('QA_BASE_DIR', dirname(dirname(__FILE__)).'/'); // try out best if not set in index.php or qa-index.php - won't work with symbolic links
			
		define('QA_EXTERNAL_DIR', QA_BASE_DIR.'qa-external/');
		define('QA_INCLUDE_DIR', QA_BASE_DIR.'qa-include/');
		define('QA_LANG_DIR', QA_BASE_DIR.'qa-lang/');
		define('QA_THEME_DIR', QA_BASE_DIR.'qa-theme/');
		define('QA_PLUGIN_DIR', QA_BASE_DIR.'qa-plugin/');

		if (!file_exists(QA_BASE_DIR.'qa-config.php'))
			qa_fatal_error('The config file could not be found. Please read the instructions in qa-config-example.php.');
		
		require_once QA_BASE_DIR.'qa-config.php';
		
		$qa_request_map=is_array(@$QA_CONST_PATH_MAP) ? $QA_CONST_PATH_MAP : array();

		if (defined('QA_WORDPRESS_INTEGRATE_PATH') && strlen(QA_WORDPRESS_INTEGRATE_PATH)) {
			define('QA_FINAL_WORDPRESS_INTEGRATE_PATH', QA_WORDPRESS_INTEGRATE_PATH.((substr(QA_WORDPRESS_INTEGRATE_PATH, -1)=='/') ? '' : '/'));
			define('QA_WORDPRESS_LOAD_FILE', QA_FINAL_WORDPRESS_INTEGRATE_PATH.'wp-load.php');
	
			if (!is_readable(QA_WORDPRESS_LOAD_FILE))
				qa_fatal_error('Could not find wp-load.php file for WordPress integration - please check QA_WORDPRESS_INTEGRATE_PATH in qa-config.php');
		}
	}
	
	
	function qa_initialize_constants_2()
/*
	Second stage of setting up Q2A constants, after (if necessary) loading WordPress integration
*/
	{
	
	//	Default values if not set in qa-config.php
	
		@define('QA_COOKIE_DOMAIN', '');
		@define('QA_HTML_COMPRESSION', true);
		@define('QA_MAX_LIMIT_START', 19999);
		@define('QA_IGNORED_WORDS_FREQ', 10000);
		@define('QA_ALLOW_UNINDEXED_QUERIES', false);
		@define('QA_OPTIMIZE_LOCAL_DB', false);
		@define('QA_OPTIMIZE_DISTANT_DB', false);
		@define('QA_PERSISTENT_CONN_DB', false);
		@define('QA_DEBUG_PERFORMANCE', false);
		
	//	Start performance monitoring
	
		if (QA_DEBUG_PERFORMANCE) {
			require_once 'qa-util-debug.php';
			qa_usage_init();
		}
		
	//	More for WordPress integration
		
		if (defined('QA_FINAL_WORDPRESS_INTEGRATE_PATH')) {
			define('QA_FINAL_MYSQL_HOSTNAME', DB_HOST);
			define('QA_FINAL_MYSQL_USERNAME', DB_USER);
			define('QA_FINAL_MYSQL_PASSWORD', DB_PASSWORD);
			define('QA_FINAL_MYSQL_DATABASE', DB_NAME);
			define('QA_FINAL_EXTERNAL_USERS', true);
			
			// Undo WordPress's addition of magic quotes to various things (leave $_COOKIE as is since WP code might need that)

			function qa_undo_wordpress_quoting($param, $isget)
			{
				if (is_array($param)) { // 
					foreach ($param as $key => $value)
						$param[$key]=qa_undo_wordpress_quoting($value, $isget);
					
				} else {
					$param=stripslashes($param);
					if ($isget)
						$param=strtr($param, array('\\\'' => '\'', '\"' => '"')); // also compensate for WordPress's .htaccess file
				}
				
				return $param;
			}
			
			$_GET=qa_undo_wordpress_quoting($_GET, true);
			$_POST=qa_undo_wordpress_quoting($_POST, false);
			$_SERVER['PHP_SELF']=stripslashes($_SERVER['PHP_SELF']);
		
		} else {
			define('QA_FINAL_MYSQL_HOSTNAME', QA_MYSQL_HOSTNAME);
			define('QA_FINAL_MYSQL_USERNAME', QA_MYSQL_USERNAME);
			define('QA_FINAL_MYSQL_PASSWORD', QA_MYSQL_PASSWORD);
			define('QA_FINAL_MYSQL_DATABASE', QA_MYSQL_DATABASE);
			define('QA_FINAL_EXTERNAL_USERS', QA_EXTERNAL_USERS);
		}
		
	//	Possible URL schemes for Q2A and the string used for url scheme testing

		define('QA_URL_FORMAT_INDEX', 0);  // http://...../index.php/123/why-is-the-sky-blue
		define('QA_URL_FORMAT_NEAT', 1);   // http://...../123/why-is-the-sky-blue [requires .htaccess]
		define('QA_URL_FORMAT_PARAM', 3);  // http://...../?qa=123/why-is-the-sky-blue
		define('QA_URL_FORMAT_PARAMS', 4); // http://...../?qa=123&qa_1=why-is-the-sky-blue
		define('QA_URL_FORMAT_SAFEST', 5); // http://...../index.php?qa=123&qa_1=why-is-the-sky-blue

		define('QA_URL_TEST_STRING', '$&-_~#%\\@^*()=!()][`\';:|".{},<>?# π§½Жש'); // tests escaping, spaces, quote slashing and unicode - but not + and /
	}


	function qa_initialize_modularity()
/*
	Gets everything ready to start using modules, layers and overrides
*/
	{
		global $qa_modules, $qa_layers, $qa_override_files, $qa_overrides, $qa_direct;

		$qa_modules=array();
		$qa_layers=array();
		$qa_override_files=array();
		$qa_overrides=array();
		$qa_direct=array();
	}
	
	
	function qa_register_core_modules()
/*
	Register all modules that come as part of the Q2A core (as opposed to plugins)
*/
	{
		qa_register_module('filter', 'qa-filter-basic.php', 'qa_filter_basic', '');
		qa_register_module('editor', 'qa-editor-basic.php', 'qa_editor_basic', '');
		qa_register_module('viewer', 'qa-viewer-basic.php', 'qa_viewer_basic', '');
		qa_register_module('event', 'qa-event-limits.php', 'qa_event_limits', 'Q2A Event Limits');
		qa_register_module('event', 'qa-event-notify.php', 'qa_event_notify', 'Q2A Event Notify');
		qa_register_module('event', 'qa-event-updates.php', 'qa_event_updates', 'Q2A Event Updates');
		qa_register_module('search', 'qa-search-basic.php', 'qa_search_basic', '');
		qa_register_module('widget', 'qa-widget-activity-count.php', 'qa_activity_count', 'Activity Count');
		qa_register_module('widget', 'qa-widget-ask-box.php', 'qa_ask_box', 'Ask Box');
		qa_register_module('widget', 'qa-widget-related-qs.php', 'qa_related_qs', 'Related Questions');
	}
	
	
	function qa_load_plugin_files()
/*
	Load all the qa-plugin.php files from plugins that are compatible with this version of Q2A
*/
	{
		global $qa_plugin_directory, $qa_plugin_urltoroot;
		
		$pluginfiles=glob(QA_PLUGIN_DIR.'*/qa-plugin.php');

		foreach ($pluginfiles as $pluginfile)
			if (file_exists($pluginfile)) {
				$contents=file_get_contents($pluginfile);
				
				if (preg_match('/Plugin[ \t]*Minimum[ \t]*Question2Answer[ \t]*Version\:[ \t]*([0-9\.]+)\s/i', $contents, $matches))
					if (qa_qa_version_below($matches[1]))
						continue; // skip plugin which requires a later version of Q2A
				
				if (preg_match('/Plugin[ \t]*Minimum[ \t]*PHP[ \t]*Version\:[ \t]*([0-9\.]+)\s/i', $contents, $matches))
					if (qa_php_version_below($matches[1]))
						continue; // skip plugin which requires a later version of PHP
				
				$qa_plugin_directory=dirname($pluginfile).'/';
				$qa_plugin_urltoroot=substr($qa_plugin_directory, strlen(QA_BASE_DIR));
				
				require_once $pluginfile;
				
				$qa_plugin_directory=null;
				$qa_plugin_urltoroot=null;
			}
	}


	function qa_load_override_files()
/*
	Apply all the function overrides in override files that have been registered by plugins
*/
	{
		global $qa_override_files, $qa_overrides;
		
		$functionindex=array();

		foreach ($qa_override_files as $index => $override) {
			$filename=$override['directory'].$override['include'];
			$functionsphp=file_get_contents($filename);
			
			preg_match_all('/\Wfunction\s+(qa_[a-z_]+)\s*\(/im', $functionsphp, $rawmatches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);
			
			$reversematches=array_reverse($rawmatches[1], true); // reverse so offsets remain correct as we step through
			$postreplace=array();
			$suffix='_in_'.preg_replace('/[^A-Za-z0-9_]+/', '_', basename($override['include']));
				// include file name in defined function names to make debugging easier if there is an error
			
			foreach ($reversematches as $rawmatch) {
				$function=strtolower($rawmatch[0]);
				$position=$rawmatch[1];

				if (isset($qa_overrides[$function]))
					$postreplace[$function.'_base']=$qa_overrides[$function];
					
				$newname=$function.'_override_'.(@++$functionindex[$function]).$suffix;
				$functionsphp=substr_replace($functionsphp, $newname, $position, strlen($function));
				$qa_overrides[$function]=$newname;
			}
			
			foreach ($postreplace as $oldname => $newname)
				if (preg_match_all('/\W('.preg_quote($oldname).')\s*\(/im', $functionsphp, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
					$searchmatches=array_reverse($matches[1]);
					foreach ($searchmatches as $searchmatch)
						$functionsphp=substr_replace($functionsphp, $newname, $searchmatch[1], strlen($searchmatch[0]));
				}
			
		//	echo '<pre style="text-align:left;">'.htmlspecialchars($functionsphp).'</pre>'; // to debug munged code
			
			qa_eval_from_file($functionsphp, $filename);
		}
	}
	

//	Functions for registering different varieties of Q2A modularity
	
	function qa_register_module($type, $include, $class, $name, $directory=QA_INCLUDE_DIR, $urltoroot=null)
/*
	Register a module of $type named $name, whose class named $class is defined in file $include (or null if no include necessary)
	If this module comes from a plugin, pass in the local plugin $directory and the $urltoroot relative url for that directory 
*/
	{
		global $qa_modules;
		
		$previous=@$qa_modules[$type][$name];
		
		if (isset($previous))
			qa_fatal_error('A '.$type.' module named '.$name.' already exists. Please check there are no duplicate plugins. '.
				"\n\nModule 1: ".$previous['directory'].$previous['include']."\nModule 2: ".$directory.$include);
		
		$qa_modules[$type][$name]=array(
			'directory' => $directory,
			'urltoroot' => $urltoroot,
			'include' => $include,
			'class' => $class,
		);
	}
	
	
	function qa_register_layer($include, $name, $directory=QA_INCLUDE_DIR, $urltoroot=null)
/*
	Register a layer named $name, defined in file $include. If this layer comes from a plugin (as all currently do),
	pass in the local plugin $directory and the $urltoroot relative url for that directory 
*/
	{
		global $qa_layers;
		
		$previous=@$qa_layers[$name];
		
		if (isset($previous))
			qa_fatal_error('A layer named '.$name.' already exists. Please check there are no duplicate plugins. '.
				"\n\nLayer 1: ".$previous['directory'].$previous['include']."\nLayer 2: ".$directory.$include);
			
		$qa_layers[$name]=array(
			'directory' => $directory,
			'urltoroot' => $urltoroot,
			'include' => $include,
		);
	}
	
	
	function qa_register_overrides($include, $directory=QA_INCLUDE_DIR, $urltoroot=null)
/*
	Register a file $include containing override functions. If this file comes from a plugin (as all currently do),
	pass in the local plugin $directory and the $urltoroot relative url for that directory 
*/
	{
		global $qa_override_files;
		
		$qa_override_files[]=array(
			'directory' => $directory,
			'urltoroot' => $urltoroot,
			'include' => $include
		);
	}
	
	
	function qa_register_phrases($pattern, $name)
/*
	Register a set of language phrases, which should be accessed by the prefix $name/ in the qa_lang_*() functions.
	Pass in the $pattern representing the PHP files that define these phrases, where * in the pattern is replaced with
	the language code (e.g. 'fr') and/or 'default'. These files should be formatted like Q2A's qa-lang-*.php files.
*/
	{
		global $qa_lang_file_pattern;
		
		if (file_exists(QA_INCLUDE_DIR.'qa-lang-'.$name.'.php'))
			qa_fatal_error('The name "'.$name.'" for phrases is reserved and cannot be used by plugins.'."\n\nPhrases: ".$pattern);

		if (isset($qa_lang_file_pattern[$name]))
			qa_fatal_error('A set of phrases named '.$name.' already exists. Please check there are no duplicate plugins. '.
				"\n\nPhrases 1: ".$qa_lang_file_pattern[$name]."\nPhrases 2: ".$pattern);
			
		$qa_lang_file_pattern[$name]=$pattern;
	}


//	Function for registering varieties of Q2A modularity, which are (only) called from qa-plugin.php files	

	function qa_register_plugin_module($type, $include, $class, $name)
/*
	Register a plugin module of $type named $name, whose class named $class is defined in file $include (or null if no include necessary)
	This function relies on some global variable values and can only be called from a plugin's qa-plugin.php file
*/
	{
		global $qa_plugin_directory, $qa_plugin_urltoroot;
		
		if (empty($qa_plugin_directory) || empty($qa_plugin_urltoroot))
			qa_fatal_error('qa_register_plugin_module() can only be called from a plugin qa-plugin.php file');

		qa_register_module($type, $include, $class, $name, $qa_plugin_directory, $qa_plugin_urltoroot);
	}

	
	function qa_register_plugin_layer($include, $name)
/*
	Register a plugin layer named $name, defined in file $include. Can only be called from a plugin's qa-plugin.php file
*/
	{
		global $qa_plugin_directory, $qa_plugin_urltoroot;
		
		if (empty($qa_plugin_directory) || empty($qa_plugin_urltoroot))
			qa_fatal_error('qa_register_plugin_layer() can only be called from a plugin qa-plugin.php file');

		qa_register_layer($include, $name, $qa_plugin_directory, $qa_plugin_urltoroot);
	}
	
	
	function qa_register_plugin_overrides($include)
/*
	Register a plugin file $include containing override functions. Can only be called from a plugin's qa-plugin.php file
*/
	{
		global $qa_plugin_directory, $qa_plugin_urltoroot;

		if (empty($qa_plugin_directory) || empty($qa_plugin_urltoroot))
			qa_fatal_error('qa_register_plugin_overrides() can only be called from a plugin qa-plugin.php file');
			
		qa_register_overrides($include, $qa_plugin_directory, $qa_plugin_urltoroot);
	}
	
	
	function qa_register_plugin_phrases($pattern, $name)
/*
	Register a file name $pattern within a plugin directory containing language phrases accessed by the prefix $name
*/
	{
		global $qa_plugin_directory, $qa_plugin_urltoroot;
		
		if (empty($qa_plugin_directory) || empty($qa_plugin_urltoroot))
			qa_fatal_error('qa_register_plugin_phrases() can only be called from a plugin qa-plugin.php file');

		qa_register_phrases($qa_plugin_directory.$pattern, $name);
	}
	
	
//	Low-level functions used throughout Q2A

	function qa_eval_from_file($eval, $filename)
/*
	Calls eval() on the PHP code in $eval which came from the file $filename. It supplements PHP's regular error reporting by
	displaying/logging (as appropriate) the original source filename, if an error occurred when evaluating the code.
*/
	{
		// could also use ini_set('error_append_string') but apparently it doesn't work for errors logged on disk
		
		global $php_errormsg;
		
		$oldtrackerrors=@ini_set('track_errors', 1);
		$php_errormsg=null; 
		
		eval('?'.'>'.$eval);
		
		if (strlen($php_errormsg)) {
			switch (strtolower(@ini_get('display_errors'))) {
				case 'on': case '1': case 'yes': case 'true': case 'stdout': case 'stderr':
					echo ' of '.qa_html($filename)."\n";
					break;
			}

			@error_log('PHP Question2Answer more info: '.$php_errormsg." in eval()'d code from ".qa_html($filename));
		}
		
		@ini_set('track_errors', $oldtrackerrors);
	}
	
	
	function qa_call($function, $args)
/*
	Call $function with the arguments in the $args array (doesn't work with call-by-reference functions)
*/
	{
		switch (count($args)) { // call_user_func_array(...) is very slow, so we break out most cases
			case 0: return $function();
			case 1: return $function($args[0]);
			case 2: return $function($args[0], $args[1]);
			case 3: return $function($args[0], $args[1], $args[2]);
			case 4: return $function($args[0], $args[1], $args[2], $args[3]);
			case 5: return $function($args[0], $args[1], $args[2], $args[3], $args[4]);
		}
		
		return call_user_func_array($function, $args);
	}

	
	function qa_to_override($function)
/*
	If $function has been overridden by a plugin override, return the name of the overriding function, otherwise return
	null. But if the function is being called with the _base suffix, any override will be bypassed due to $qa_direct
*/
	{
		global $qa_overrides, $qa_direct;
		
		if (strpos($function, '_override_')!==false)
			qa_fatal_error('Override functions should not be calling qa_to_override()!');
		
		if (isset($qa_overrides[$function])) {
			if (@$qa_direct[$function])
				unset($qa_direct[$function]); // bypass the override just this once
			else
				return $qa_overrides[$function];
		}
		
		return null;
	}
	
	
	function qa_call_override($function, $args)
/*
	Call the function which immediately overrides $function with the arguments in the $args array
*/
	{
		global $qa_overrides;
		
		if (strpos($function, '_override_')!==false)
			qa_fatal_error('Override functions should not be calling qa_call_override()!');
		
		if (!function_exists($function.'_base')) // define the base function the first time that it's needed
			eval('function '.$function.'_base() { global $qa_direct; $qa_direct[\''.$function.'\']=true; $args=func_get_args(); return qa_call(\''.$function.'\', $args); }');
		
		return qa_call($qa_overrides[$function], $args);
	}
	
	
	function qa_exit($reason=null)
/*
	Exit PHP immediately after reporting a shutdown with $reason to any installed process modules
*/
	{
		qa_report_process_stage('shutdown', $reason);
		exit;
	}


	function qa_fatal_error($message)
/*
	Display $message in the browser, write it to server error log, and then stop abruptly
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		echo 'Question2Answer fatal error:<p><font color="red">'.qa_html($message, true).'</font></p>';
		@error_log('PHP Question2Answer fatal error: '.$message);
		echo '<p>Stack trace:<p>';

		$backtrace=array_reverse(array_slice(debug_backtrace(), 1));
		foreach ($backtrace as $trace)
			echo '<font color="#'.((strpos(@$trace['file'], '/qa-plugin/')!==false) ? 'f00' : '999').'">'.
				qa_html(@$trace['function'].'() in '.basename(@$trace['file']).':'.@$trace['line']).'</font><br>';	
		
		qa_exit('error');
	}
	

//	Functions for listing, loading and getting info on modules

	function qa_list_module_types()
/*
	Return an array of all the module types for which at least one module has been registered
*/
	{
		global $qa_modules;
		
		return array_keys($qa_modules);
	}

	
	function qa_list_modules($type)
/*
	Return a list of names of registered modules of $type
*/
	{
		global $qa_modules;
		
		return is_array(@$qa_modules[$type]) ? array_keys($qa_modules[$type]) : array();
	}
	
	
	function qa_get_module_info($type, $name)
/*
	Return an array containing information about the module of $type named $name
*/
	{
		global $qa_modules;
		return @$qa_modules[$type][$name];
	}
	
	
	function qa_load_module($type, $name)
/*
	Return an instantiated class for module of $type named $name, whose functions can be called, or null if it doesn't exist
*/
	{
		global $qa_modules;
		
		$module=@$qa_modules[$type][$name];
		
		if (is_array($module)) {
			if (isset($module['object']))
				return $module['object'];
			
			if (strlen(@$module['include']))
				require_once $module['directory'].$module['include'];
			
			if (strlen(@$module['class'])) {
				$object=new $module['class'];
				
				if (method_exists($object, 'load_module'))
					$object->load_module($module['directory'], qa_path_to_root().$module['urltoroot'], $type, $name);
				
				$qa_modules[$type][$name]['object']=$object;
				return $object;
			}
		}
		
		return null;
	}
	
	
	function qa_load_modules_with($type, $method)
/*
	Return an array of instantiated clases for modules of $type which have defined $method
	(other modules of that type are also loaded but not included in the returned array)
*/
	{
		$modules=array();
		
		$trynames=qa_list_modules($type);
		
		foreach ($trynames as $tryname) {
			$module=qa_load_module($type, $tryname);
			
			if (method_exists($module, $method))
				$modules[$tryname]=$module;
		}
		
		return $modules;
	}
	
	
//	HTML and Javascript escaping and sanitization

	function qa_html($string, $multiline=false)
/*
	Return HTML representation of $string, work well with blocks of text if $multiline is true
*/
	{
		$html=htmlspecialchars((string)$string);
		
		if ($multiline) {
			$html=preg_replace('/\r\n?/', "\n", $html);
			$html=preg_replace('/(?<=\s) /', '&nbsp;', $html);
			$html=str_replace("\t", '&nbsp; &nbsp; ', $html);
			$html=nl2br($html);
		}
		
		return $html;
	}

	
	function qa_sanitize_html($html, $linksnewwindow=false, $storage=false)
/*
	Return $html after ensuring it is safe, i.e. removing Javascripts and the like - uses htmLawed library
	Links open in a new window if $linksnewwindow is true. Set $storage to true if sanitization is for
	storing in the database, rather than immediate display to user - some think this should be less strict.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once 'qa-htmLawed.php';
		
		global $qa_sanitize_html_newwindow;
		
		$qa_sanitize_html_newwindow=$linksnewwindow;
		
		$safe=htmLawed($html, array(
			'safe' => 1,
			'elements' => '*+embed+object-form',
			'schemes' => 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https; style: !; classid:clsid',
			'keep_bad' => 0,
			'anti_link_spam' => array('/.*/', ''),
			'hook_tag' => 'qa_sanitize_html_hook_tag',
		));
		
		return $safe;
	}
	
	
	function qa_sanitize_html_hook_tag($element, $attributes=null)
/*
	htmLawed hook function used to process tags in qa_sanitize_html(...)
*/
	{
		global $qa_sanitize_html_newwindow;

		if (!isset($attributes)) // it's a closing tag
			return '</'.$element.'>';
		
		if ( ($element=='param') && (trim(strtolower(@$attributes['name']))=='allowscriptaccess') )
			$attributes['name']='allowscriptaccess_denied';
			
		if ($element=='embed')
			unset($attributes['allowscriptaccess']);
			
		if (($element=='a') && isset($attributes['href']) && $qa_sanitize_html_newwindow)
			$attributes['target']='_blank';
		
		$html='<'.$element;
		foreach ($attributes as $key => $value)
			$html.=' '.$key.'="'.$value.'"';
			
		return $html.'>';
	}
	
	
	function qa_xml($string)
/*
	Return XML representation of $string, which is similar to HTML but ASCII control characters are also disallowed
*/
	{
		return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$string));
	}
	
	
	function qa_js($value, $forcequotes=false)
/*
	Return JavaScript representation of $value, putting in quotes if non-numeric or if $forcequotes is true
*/
	{
		if (is_numeric($value) && !$forcequotes)
			return $value;
		else
			return "'".strtr($value, array(
				"'" => "\\'",
				'/' => '\\/',
				'\\' => '\\\\',
				"\n" => "\\n",
				"\r" => "\\n",
			))."'";
	}


//	Finding out more about the current request
	
	function qa_set_request($request, $relativeroot, $usedformat=null)
/*
	Inform Q2A that the current request is $request (slash-separated, independent of the url scheme chosen),
	that the relative path to the Q2A root apperas to be $relativeroot, and the url scheme appears to be $usedformat
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_request, $qa_root_url_relative, $qa_used_url_format;
		
		$qa_request=$request;
		$qa_root_url_relative=$relativeroot;
		$qa_used_url_format=$usedformat;
	}
	
	
	function qa_request()
/*
	Returns the current Q2A request (slash-separated, independent of the url scheme chosen)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_request;
		return $qa_request;
	}
	
	
	function qa_request_part($part)
/*
	Returns the indexed $part (as separated by slashes) of the current Q2A request, or null if it doesn't exist
*/
	{
		$parts=explode('/', qa_request());
		return @$parts[$part];
	}
	
	
	function qa_request_parts($start=0)
/*
	Returns an array of parts (as separated by slashes) of the current Q2A request, starting at part $start
*/
	{
		return array_slice(explode('/', qa_request()), $start);
	}
	
	
	function qa_gpc_to_string($string)
/*
	Return string for incoming GET/POST/COOKIE value, stripping slashes if appropriate
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return get_magic_quotes_gpc() ? stripslashes($string) : $string;
	}
	

	function qa_string_to_gpc($string)
/*
	Return string with slashes added, if appropriate for later removal by qa_gpc_to_string()
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return get_magic_quotes_gpc() ? addslashes($string) : $string;
	}


	function qa_get($field)
/*
	Return string for incoming GET field, or null if it's not defined
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return isset($_GET[$field]) ? qa_gpc_to_string($_GET[$field]) : null;
	}


	function qa_post_text($field)
/*
	Return string for incoming POST field, or null if it's not defined.
	While we're at it, trim() surrounding white space and converted to Unix line endings.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return isset($_POST[$field]) ? preg_replace('/\r\n?/', "\n", trim(qa_gpc_to_string($_POST[$field]))) : null;
	}

	
	function qa_clicked($name)
/*
	Return true if form button $name was clicked (as type=submit/image) to create this page request, or if a
	simulated click was sent for the button (via 'qa_click' POST field)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return isset($_POST[$name]) || isset($_POST[$name.'_x']) || (qa_post_text('qa_click')==$name);
	}

	
	function qa_remote_ip_address()
/*
	Return the remote IP address of the user accessing the site, if it's available, or null otherwise
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return @$_SERVER['REMOTE_ADDR'];
	}
	
	
	function qa_is_http_post()
/*
	Return true if we are responding to an HTTP POST request
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return ($_SERVER['REQUEST_METHOD']=='POST') || !empty($_POST);
	}

	
	function qa_is_https_probably()
/*
	Return true if we appear to be responding to a secure HTTP request (but hard to be sure)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return (@$_SERVER['HTTPS'] && ($_SERVER['HTTPS']!='off')) || (@$_SERVER['SERVER_PORT']==443);
	}
	
	
	function qa_is_human_probably()
/*
	Return true if it appears the page request is coming from a human using a web browser, rather than a search engine
	or other bot. Based on a whitelist of terms in user agents, this can easily be tricked by a scraper or bad bot.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$useragent=@$_SERVER['HTTP_USER_AGENT'];
		
		return (strlen($useragent)==0) || qa_string_matches_one($useragent, array(
			'MSIE', 'Firefox', 'Chrome', 'Safari', 'Opera', 'Gecko', 'MIDP', 'PLAYSTATION', 'Teleca',
			'BlackBerry', 'UP.Browser', 'Polaris', 'MAUI_WAP_Browser', 'iPad', 'iPhone', 'iPod'
		));
	}
	
	
	function qa_is_mobile_probably()
/*
	Return true if it appears that the page request is coming from a mobile client rather than a desktop/laptop web browser
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		// inspired by: http://dangerousprototypes.com/docs/PhpBB3_MOD:_Replacement_mobile_browser_detection_for_mobile_themes
		
		$loweragent=strtolower(@$_SERVER['HTTP_USER_AGENT']);
		
		if (strpos($loweragent, 'ipad')!==false) // consider iPad as desktop
			return false;
		
		$mobileheaders=array('HTTP_X_OPERAMINI_PHONE', 'HTTP_X_WAP_PROFILE', 'HTTP_PROFILE');
		
		foreach ($mobileheaders as $header)
			if (isset($_SERVER[$header]))
				return true;
				
		if (qa_string_matches_one($loweragent, array(
			'android', 'phone', 'mobile', 'windows ce', 'palm', ' mobi', 'wireless', 'blackberry', 'opera mini', 'symbian',
			'nokia', 'samsung', 'ericsson,', 'vodafone/', 'kindle', 'ipod', 'wap1.', 'wap2.', 'sony', 'sanyo', 'sharp',
			'panasonic', 'philips', 'pocketpc', 'avantgo', 'blazer', 'ipaq', 'up.browser', 'up.link', 'mmp', 'smartphone', 'midp'
		)))
			return true;
		
		return qa_string_matches_one(strtolower(@$_SERVER['HTTP_ACCEPT']), array(
			'application/vnd.wap.xhtml+xml', 'text/vnd.wap.wml'
		));
	}
	
	
//	Language phrase support

	function qa_lang($identifier)
/*
	Return the translated string for $identifier, unless we're using external translation logic.
	This will retrieve the 'site_language' option so make sure you've already loaded/set that if
	loading an option now will cause a problem (see issue in qa_default_option()). The part of
	$identifier before the slash (/) replaces the * in the qa-lang-*.php file references, and the
	part after the / is the key of the array element to be taken from that file's returned result.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_lang_file_pattern, $qa_phrases_custom, $qa_phrases_lang, $qa_phrases_default;
		
		list($group, $label)=explode('/', $identifier, 2);
		
	//	First look for a custom phrase
		
		if (!isset($qa_phrases_custom[$group])) { // only load each language file once
			$phrases=@include QA_LANG_DIR.'custom/qa-lang-'.$group.'.php'; // can tolerate missing file or directory
			$qa_phrases_custom[$group]=is_array($phrases) ? $phrases : array();
		}
		
		if (isset($qa_phrases_custom[$group][$label]))
			return $qa_phrases_custom[$group][$label];
			
	//	Second look for a localized file
	
		$languagecode=qa_opt('site_language');
		
		if (strlen($languagecode)) {
			if (!isset($qa_phrases_lang[$group])) {
				if (isset($qa_lang_file_pattern[$group]))
					$include=str_replace('*', $languagecode, $qa_lang_file_pattern[$group]);
				else
					$include=QA_LANG_DIR.$languagecode.'/qa-lang-'.$group.'.php';
				
				$phrases=@include $include;
				$qa_phrases_lang[$group]=is_array($phrases) ? $phrases : array();
			}
			
			if (isset($qa_phrases_lang[$group][$label]))
				return $qa_phrases_lang[$group][$label];
		}
		
	//	Finally load the default
	
		if (!isset($qa_phrases_default[$group])) { // only load each default language file once
			if (isset($qa_lang_file_pattern[$group]))
				$include=str_replace('*', 'default', $qa_lang_file_pattern[$group]);
			else
				$include=QA_INCLUDE_DIR.'qa-lang-'.$group.'.php';
				
			$qa_phrases_default[$group]=@include_once $include;
		}
		
		if (isset($qa_phrases_default[$group][$label]))
			return $qa_phrases_default[$group][$label];
			
		return '['.$identifier.']'; // as a last resort, return the identifier to help in development
	}


	function qa_lang_sub($identifier, $textparam, $symbol='^')
/*
	Return the translated string for $identifier, with $symbol substituted for $textparam
*/
	{
		return str_replace($symbol, $textparam, qa_lang($identifier));
	}
	

	function qa_lang_html($identifier)
/*
	Return the translated string for $identifier, converted to HTML
*/
	{
		return qa_html(qa_lang($identifier));
	}

	
	function qa_lang_html_sub($identifier, $htmlparam, $symbol='^')
/*
	Return the translated string for $identifier converted to HTML, with $symbol *then* substituted for $htmlparam
*/
	{
		return str_replace($symbol, $htmlparam, qa_lang_html($identifier));
	}
	

	function qa_lang_html_sub_split($identifier, $htmlparam, $symbol='^')
/*
	Return an array containing the translated string for $identifier converted to HTML, then split into three,
	with $symbol substituted for $htmlparam in the 'data' element, and obvious 'prefix' and 'suffix' elements
*/
	{
		$html=qa_lang_html($identifier);

		$symbolpos=strpos($html, $symbol);
		if (!is_numeric($symbolpos))
			qa_fatal_error('Missing '.$symbol.' in language string '.$identifier);
			
		return array(
			'prefix' => substr($html, 0, $symbolpos),
			'data' => $htmlparam,
			'suffix' => substr($html, $symbolpos+1),
		);
	}

	
//	Request and path generation 

	function qa_path_to_root()
/*
	Return the relative path to the Q2A root (if it's was previously set by qa_set_request())
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_root_url_relative;
		return $qa_root_url_relative;
	}
	
	
	function qa_get_request_map()
/*
	Return an array of mappings of Q2A requests, as defined in the qa-config.php file
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_request_map;
		return $qa_request_map;
	}
	

	function qa_path($request, $params=null, $rooturl=null, $neaturls=null, $anchor=null)
/*
	Return the relative URI path for $request, with optional parameters $params and $anchor.
	Slashes in $request will not be urlencoded, but any other characters will.
	If $neaturls is set, use that, otherwise retrieve the option. If $rooturl is set, take
	that as the root of the Q2A site, otherwise use path to root which was set elsewhere.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if (!isset($neaturls)) {
			require_once QA_INCLUDE_DIR.'qa-app-options.php';
			$neaturls=qa_opt('neat_urls');
		}
		
		if (!isset($rooturl))
			$rooturl=qa_path_to_root();
		
		$url=$rooturl.( (empty($rooturl) || (substr($rooturl, -1)=='/') ) ? '' : '/');
		$paramsextra='';
		
		$requestparts=explode('/', $request);
		$pathmap=qa_get_request_map();
		
		if (isset($pathmap[$requestparts[0]])) {
			$newpart=$pathmap[$requestparts[0]];
			
			if (strlen($newpart))
				$requestparts[0]=$newpart;
			elseif (count($requestparts)==1)
				array_shift($requestparts);
		}
		
		foreach ($requestparts as $index => $requestpart)
			$requestparts[$index]=urlencode($requestpart);
		$requestpath=implode('/', $requestparts);
		
		switch ($neaturls) {
			case QA_URL_FORMAT_INDEX:
				if (!empty($request))
					$url.='index.php/'.$requestpath;
				break;
				
			case QA_URL_FORMAT_NEAT:
				$url.=$requestpath;
				break;
				
			case QA_URL_FORMAT_PARAM:
				if (!empty($request))
					$paramsextra='?qa='.$requestpath;
				break;
				
			default:
				$url.='index.php';
			
			case QA_URL_FORMAT_PARAMS:
				if (!empty($request))
					foreach ($requestparts as $partindex => $requestpart)
						$paramsextra.=(strlen($paramsextra) ? '&' : '?').'qa'.($partindex ? ('_'.$partindex) : '').'='.$requestpart;
				break;
		}
		
		if (isset($params))
			foreach ($params as $key => $value)
				$paramsextra.=(strlen($paramsextra) ? '&' : '?').urlencode($key).'='.urlencode((string)$value);
		
		return $url.$paramsextra.( empty($anchor) ? '' : '#'.urlencode($anchor) );
	}


	function qa_path_html($request, $params=null, $rooturl=null, $neaturls=null, $anchor=null)
/*
	Return HTML representation of relative URI path for $request - see qa_path() for other parameters
*/
	{
		return qa_html(qa_path($request, $params, $rooturl, $neaturls, $anchor));
	}
	
	
	function qa_path_absolute($request, $params=null, $anchor=null)
/*
	Return the absolute URI for $request - see qa_path() for other parameters
*/
	{
		return qa_path($request, $params, qa_opt('site_url'), null, $anchor);
	}

	
	function qa_q_request($questionid, $title)
/*
	Return the Q2A request for question $questionid, and make it search-engine friendly based on $title, which is
	shortened if necessary by removing shorter words which are generally less meaningful.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
	
		$title=qa_block_words_replace($title, qa_get_block_words_preg());
		
		$words=qa_string_to_words($title, true, false, false);

		$wordlength=array();
		foreach ($words as $index => $word)
			$wordlength[$index]=qa_strlen($word);

		$remaining=qa_opt('q_urls_title_length');
		
		if (array_sum($wordlength)>$remaining) {
			arsort($wordlength, SORT_NUMERIC); // sort with longest words first
			
			foreach ($wordlength as $index => $length) {
				if ($remaining>0)
					$remaining-=$length;
				else
					unset($words[$index]);
			}
		}
		
		$title=implode('-', $words);
		if (qa_opt('q_urls_remove_accents'))
			$title=qa_string_remove_accents($title);
		
		return (int)$questionid.'/'.$title;
	}
	
	
	function qa_anchor($basetype, $postid)
/*
	Return the HTML anchor that should be used for post $postid with $basetype (Q/A/C)
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return strtolower($basetype).$postid; // used to be $postid only but this violated HTML spec
	}
	
	
	function qa_q_path($questionid, $title, $absolute=false, $showtype=null, $showid=null)
/*
	Return the URL for question $questionid with $title, possibly using $absolute URLs.
	To link to a specific answer or comment in a question, set $showtype and $showid accordingly.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		if ( (($showtype=='Q') || ($showtype=='A') || ($showtype=='C')) && isset($showid))  {
			$params=array('show' => $showid); // due to pagination
			$anchor=qa_anchor($showtype, $showid);
		
		} else {
			$params=null;
			$anchor=null;
		}
		
		return qa_path(qa_q_request($questionid, $title), $params, $absolute ? qa_opt('site_url') : null, null, $anchor);
	}
	
	
	function qa_q_path_html($questionid, $title, $absolute=false, $showtype=null, $showid=null)
/*
	Return the HTML representation of the URL for $questionid - other parameters as for qa_q_path()
*/
	{
		return qa_html(qa_q_path($questionid, $title, $absolute, $showtype, $showid));
	}

	
	function qa_feed_request($feed)
/*
	Return the request for the specified $feed
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		return 'feed/'.$feed.'.rss';
	}
	
	
	function qa_self_html()
/*
	Return an HTML-ready relative URL for the current page, preserving GET parameters - this is useful for action="..." in HTML forms
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_used_url_format;
		
		return qa_path_html(qa_request(), $_GET, null, $qa_used_url_format);
	}
	

	function qa_path_form_html($request, $params=null, $rooturl=null, $neaturls=null, $anchor=null)
/*
	Return HTML for hidden fields to insert into a <form method="get"...> on the page.
	This is needed because any parameters on the URL will be lost when the form is submitted.
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$path=qa_path($request, $params, $rooturl, $neaturls, $anchor);
		$formhtml='';
		
		$questionpos=strpos($path, '?');
		if (is_numeric($questionpos)) {
			$params=explode('&', substr($path, $questionpos+1));
			
			foreach ($params as $param)
				if (preg_match('/^([^\=]*)(\=(.*))?$/', $param, $matches))
					$formhtml.='<input type="hidden" name="'.qa_html(urldecode($matches[1])).'" value="'.qa_html(urldecode(@$matches[3])).'"/>';
		}
		
		return $formhtml;
	}
	
	
	function qa_redirect($request, $params=null, $rooturl=null, $neaturls=null, $anchor=null)
/*
	Redirect the user's web browser to $request and then we're done - see qa_path() for other parameters
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		qa_redirect_raw(qa_path($request, $params, $rooturl, $neaturls, $anchor));
	}
	
	
	function qa_redirect_raw($url)
/*
	Redirect the user's web browser to page $path which is already a URL
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		header('Location: '.$url);
		qa_exit('redirect');
	}
	

//	General utilities

	function qa_retrieve_url($url)
/*
	Return the contents of remote $url, using file_get_contents() if possible, otherwise curl functions
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		$contents=@file_get_contents($url);
		
		if ((!strlen($contents)) && function_exists('curl_exec')) { // try curl as a backup (if allow_url_fopen not set)
			$curl=curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$contents=@curl_exec($curl);
			curl_close($curl);
		}
		
		return $contents;
	}


	function qa_opt($name, $value=null)
/*
	Shortcut to get or set an option value without specifying database
*/
	{
		global $qa_options_cache;
		
		if ((!isset($value)) && isset($qa_options_cache[$name]))
			return $qa_options_cache[$name]; // quick shortcut to reduce calls to qa_get_options()
		
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		
		if (isset($value))
			qa_set_option($name, $value);	
		
		$options=qa_get_options(array($name));

		return $options[$name];
	}
	
	
//	Event and process stage reporting

	function qa_suspend_event_reports($suspend=true)
/*
	Suspend the reporting of events to event modules via qa_report_event(...) if $suspend is
	true, otherwise reinstate it. A counter is kept to allow multiple calls.
*/
	{
		global $qa_event_reports_suspended;
		
		$qa_event_reports_suspended+=($suspend ? 1 : -1);
	}
	
	
	function qa_report_event($event, $userid, $handle, $cookieid, $params=array())
/*
	Send a notification of event $event by $userid, $handle and $cookieid to all event modules, with extra $params
*/
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }
		
		global $qa_event_reports_suspended;
		
		if ($qa_event_reports_suspended>0)
			return;
		
		$eventmodules=qa_load_modules_with('event', 'process_event');
		foreach ($eventmodules as $eventmodule)
			$eventmodule->process_event($event, $userid, $handle, $cookieid, $params);
	}
	
	
	function qa_report_process_stage($method) // can have extra params
	{
		global $qa_process_reports_suspended;
		
		if (@$qa_process_reports_suspended)
			return;
			
		$qa_process_reports_suspended=true; // prevent loop, e.g. because of an error
		
		$args=func_get_args();
		$args=array_slice($args, 1);
		
		$processmodules=qa_load_modules_with('process', $method);
		foreach ($processmodules as $processmodule)
			call_user_func_array(array($processmodule, $method), $args);

		$qa_process_reports_suspended=null;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/