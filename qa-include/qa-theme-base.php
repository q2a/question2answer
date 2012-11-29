<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-theme-base.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Default theme class, broken into lots of little functions for easy overriding


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


/*
	How do I make a theme which goes beyond CSS to actually modify the HTML output?
	
	Create a file named qa-theme.php in your new theme directory which defines a class qa_html_theme
	that extends this base class qa_html_theme_base. You can then override any of the methods below,
	referring back to the default method using double colon (qa_html_theme_base::) notation.
	
	Plugins can also do something similar by using a layer. For more information and to see some example
	code, please consult the online Q2A documentation.
*/

	class qa_html_theme_base {
	
		var	$indent=0;
		var $lines=0;
		var $context=array();
		
		var $rooturl;
		var $template;
		var $content;
		var $request;
		
		function qa_html_theme_base($template, $content, $rooturl, $request)
	/*
		Initialize the object and assign local variables
	*/
		{
			$this->template=$template;
			$this->content=$content;
			$this->rooturl=$rooturl;
			$this->request=$request;
		}
		
		function output_array($elements)
	/*
		Output each element in $elements on a separate line, with automatic HTML indenting.
		This should be passed markup which uses the <tag/> form for unpaired tags, to help keep
		track of indenting, although its actual output converts these to <tag> for W3C validation
	*/
		{
			foreach ($elements as $element) {
				$delta=substr_count($element, '<')-substr_count($element, '<!')-2*substr_count($element, '</')-substr_count($element, '/>');
				
				if ($delta<0)
					$this->indent+=$delta;
				
				echo str_repeat("\t", max(0, $this->indent)).str_replace('/>', '>', $element)."\n";
				
				if ($delta>0)
					$this->indent+=$delta;
					
				$this->lines++;
			}
		}

		
		function output() // other parameters picked up via func_get_args()
	/*
		Output each passed parameter on a separate line - see output_array() comments
	*/
		{
			$args=func_get_args();
			$this->output_array($args);
		}

		
		function output_raw($html)
	/*
		Output $html at the current indent level, but don't change indent level based on the markup within.
		Useful for user-entered HTML which is unlikely to follow the rules we need to track indenting
	*/
		{
			if (strlen($html))
				echo str_repeat("\t", max(0, $this->indent)).$html."\n";
		}

		
		function output_split($parts, $class, $outertag='SPAN', $innertag='SPAN', $extraclass=null)
	/*
		Output the three elements ['prefix'], ['data'] and ['suffix'] of $parts (if they're defined),
		with appropriate CSS classes based on $class, using $outertag and $innertag in the markup.
	*/
		{
			if (empty($parts) && ($outertag!='TD'))
				return;
				
			$this->output(
				'<'.$outertag.' CLASS="'.$class.(isset($extraclass) ? (' '.$extraclass) : '').'">',
				(strlen(@$parts['prefix']) ? ('<'.$innertag.' CLASS="'.$class.'-pad">'.$parts['prefix'].'</'.$innertag.'>') : '').
				(strlen(@$parts['data']) ? ('<'.$innertag.' CLASS="'.$class.'-data">'.$parts['data'].'</'.$innertag.'>') : '').
				(strlen(@$parts['suffix']) ? ('<'.$innertag.' CLASS="'.$class.'-pad">'.$parts['suffix'].'</'.$innertag.'>') : ''),
				'</'.$outertag.'>'
			);
		}
		
		
		function set_context($key, $value)
	/*
		Set some context, which be accessed via $this->context for a function to know where it's being used on the page
	*/
		{
			$this->context[$key]=$value;
		}
		
		
		function clear_context($key)
	/*
		Clear some context (used at the end of the appropriate loop)
	*/
		{
			unset($this->context[$key]);
		}

		
		function widgets($region, $place)
	/*
		Output the widgets (as provided in $this->content['widgets']) for $region and $place
	*/
		{
			if (count(@$this->content['widgets'][$region][$place])) {
				$this->output('<DIV CLASS="qa-widgets-'.$region.' qa-widgets-'.$region.'-'.$place.'">');
				
				foreach ($this->content['widgets'][$region][$place] as $module) {
					$this->output('<DIV CLASS="qa-widget-'.$region.' qa-widget-'.$region.'-'.$place.'">');
					$module->output_widget($region, $place, $this, $this->template, $this->request, $this->content);
					$this->output('</DIV>');
				}
				
				$this->output('</DIV>', '');
			}
		}
		
		
		function finish()
	/*
		Post-output cleanup. For now, check that the indenting ended right, and if not, output a warning in an HTML comment
	*/
		{
			if ($this->indent)
				echo "<!--\nIt's no big deal, but your HTML could not be indented properly. To fix, please:\n".
					"1. Use this->output() to output all HTML.\n".
					"2. Balance all paired tags like <TD>...</TD> or <DIV>...</DIV>.\n".
					"3. Use a slash at the end of unpaired tags like <img/> or <input/>.\n".
					"Thanks!\n-->\n";
		}

		
	//	From here on, we have a large number of class methods which output particular pieces of HTML markup
	//	The calling chain is initiated from qa-page.php, or qa-ajax-*.php for refreshing parts of a page, 
	//	For most HTML elements, the name of the function is similar to the element's CSS class, for example:
	//	search() outputs <DIV CLASS="qa-search">, q_list() outputs <DIV CLASS="qa-q-list">, etc...

		function doctype()
		{
			$this->output('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">');
		}
		
		function html()
		{
			$this->output(
				'<HTML>',
				'<!-- Powered by Question2Answer - http://www.question2answer.org/ -->'
			);
			
			$this->head();
			$this->body();
			
			$this->output(
				'<!-- Powered by Question2Answer - http://www.question2answer.org/ -->',
				'</HTML>'
			);
		}
		
		function head()
		{
			$this->output(
				'<HEAD>',
				'<META HTTP-EQUIV="Content-type" CONTENT="'.$this->content['content_type'].'"/>'
			);
			
			$this->head_title();
			$this->head_metas();
			$this->head_css();
			$this->head_links();
			$this->head_lines();
			$this->head_script();
			$this->head_custom();
			
			$this->output('</HEAD>');
		}
		
		function head_title()
		{
			$pagetitle=strlen($this->request) ? strip_tags(@$this->content['title']) : '';
			$headtitle=(strlen($pagetitle) ? ($pagetitle.' - ') : '').$this->content['site_title'];
			
			$this->output('<TITLE>'.$headtitle.'</TITLE>');
		}
		
		function head_metas()
		{
			if (strlen(@$this->content['description']))
				$this->output('<META NAME="description" CONTENT="'.$this->content['description'].'"/>');
			
			if (strlen(@$this->content['keywords'])) // as far as I know, META keywords have zero effect on search rankings or listings
				$this->output('<META NAME="keywords" CONTENT="'.$this->content['keywords'].'"/>');
		}
		
		function head_links()
		{
			if (isset($this->content['canonical']))
				$this->output('<LINK REL="canonical" HREF="'.$this->content['canonical'].'"/>');
				
			if (isset($this->content['feed']['url']))
				$this->output('<LINK REL="alternate" TYPE="application/rss+xml" HREF="'.$this->content['feed']['url'].'" TITLE="'.@$this->content['feed']['label'].'"/>');
				
			if (isset($this->content['page_links']['items'])) // convert page links to rel=prev and rel=next tags
				foreach ($this->content['page_links']['items'] as $page_link)
					if ($page_link['type']=='prev')
						$this->output('<LINK REL="prev" HREF="'.$page_link['url'].'"/>');
					elseif ($page_link['type']=='next')
						$this->output('<LINK REL="next" HREF="'.$page_link['url'].'"/>');
		}
		
		function head_script()
		{
			if (isset($this->content['script']))
				foreach ($this->content['script'] as $scriptline)
					$this->output_raw($scriptline);
		}
		
		function head_css()
		{
			$this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.$this->rooturl.$this->css_name().'"/>');
			
			if (isset($this->content['css_src']))
				foreach ($this->content['css_src'] as $css_src)
					$this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.$css_src.'"/>');
					
			if (!empty($this->content['notices']))
				$this->output(
					'<STYLE><!--',
					'.qa-body-js-on .qa-notice {display:none;}',
					'//--></STYLE>'
				);
		}
		
		function css_name()
		{
			return 'qa-styles.css?'.QA_VERSION;
		}
		
		function head_lines()
		{
			if (isset($this->content['head_lines']))
				foreach ($this->content['head_lines'] as $line)
					$this->output_raw($line);
		}

		function head_custom()
		{
			// abstract method
		}
		
		function body()
		{
			$this->output('<BODY');
			$this->body_tags();
			$this->output('>');
			
			$this->body_script();
			$this->body_header();
			$this->body_content();
			$this->body_footer();
			$this->body_hidden();
				
			$this->output('</BODY>');
		}
		
		function body_hidden()
		{
			$this->output('<DIV STYLE="position:absolute; left:-9999px; top:-9999px;">');
			$this->waiting_template();
			$this->output('</DIV>');
		}
		
		function waiting_template()
		{
			$this->output('<SPAN ID="qa-waiting-template" CLASS="qa-waiting">...</SPAN>');
		}
		
		function body_script()
		{
			$this->output(
				'<SCRIPT TYPE="text/javascript"><!--',
				"var b=document.getElementsByTagName('body')[0];",
				"b.className=b.className.replace('qa-body-js-off', 'qa-body-js-on');",
				'//--></SCRIPT>'
			);
		}
		
		function body_header()
		{
			if (isset($this->content['body_header']))
				$this->output_raw($this->content['body_header']);
		}
		
		function body_footer()
		{
			if (isset($this->content['body_footer']))
				$this->output_raw($this->content['body_footer']);
		}
		
		function body_content()
		{
			$this->body_prefix();
			$this->notices();
			
			$this->output('<DIV CLASS="qa-body-wrapper">', '');

			$this->widgets('full', 'top');
			$this->header();
			$this->widgets('full', 'high');
			$this->sidepanel();
			$this->main();
			$this->widgets('full', 'low');
			$this->footer();
			$this->widgets('full', 'bottom');
			
			$this->output('</DIV> <!-- END body-wrapper -->');
			
			$this->body_suffix();
		}
		
		function body_tags()
		{
			$class='qa-template-'.qa_html($this->template);
			
			if (isset($this->content['categoryids']))
				foreach ($this->content['categoryids'] as $categoryid)
					$class.=' qa-category-'.qa_html($categoryid);
			
			$this->output('CLASS="'.$class.' qa-body-js-off"');
		}

		function body_prefix()
		{
			// abstract method
		}

		function body_suffix()
		{
			// abstract method
		}

		function notices()
		{
			if (!empty($this->content['notices']))
				foreach ($this->content['notices'] as $notice)
					$this->notice($notice);
		}
		
		function notice($notice)
		{
			$this->output('<DIV CLASS="qa-notice" ID="'.$notice['id'].'">');
			
			if (isset($notice['form_tags']))
				$this->output('<FORM '.$notice['form_tags'].'>');
			
			$this->output_raw($notice['content']);
			
			$this->output('<INPUT '.$notice['close_tags'].' TYPE="submit" VALUE="X" CLASS="qa-notice-close-button"/> ');
			
			if (isset($notice['form_tags']))
				$this->output('</FORM>');
			
			$this->output('</DIV>');
		}		
		
		function header()
		{
			$this->output('<DIV CLASS="qa-header">');
			
			$this->logo();
			$this->nav_user_search();
			$this->nav_main_sub();
			$this->header_clear();
			
			$this->output('</DIV> <!-- END qa-header -->', '');
		}
		
		function nav_user_search()
		{
			$this->nav('user');
			$this->search();
		}
		
		function nav_main_sub()
		{
			$this->nav('main');
			$this->nav('sub');
		}
		
		function logo()
		{
			$this->output(
				'<DIV CLASS="qa-logo">',
				$this->content['logo'],
				'</DIV>'
			);
		}
		
		function search()
		{
			$search=$this->content['search'];
			
			$this->output(
				'<DIV CLASS="qa-search">',
				'<FORM '.$search['form_tags'].'>',
				@$search['form_extra']
			);
			
			$this->search_field($search);
			$this->search_button($search);
			
			$this->output(
				'</FORM>',
				'</DIV>'
			);
		}
		
		function search_field($search)
		{
			$this->output('<INPUT TYPE="text" '.$search['field_tags'].' VALUE="'.@$search['value'].'" CLASS="qa-search-field"/>');
		}
		
		function search_button($search)
		{
			$this->output('<INPUT TYPE="submit" VALUE="'.$search['button_label'].'" CLASS="qa-search-button"/>');
		}
		
		function nav($navtype, $level=null)
		{
			$navigation=@$this->content['navigation'][$navtype];
			
			if (($navtype=='user') || isset($navigation)) {
				$this->output('<DIV CLASS="qa-nav-'.$navtype.'">');
				
				if ($navtype=='user')
					$this->logged_in();
					
				// reverse order of 'opposite' items since they float right
				foreach (array_reverse($navigation, true) as $key => $navlink)
					if (@$navlink['opposite']) {
						unset($navigation[$key]);
						$navigation[$key]=$navlink;
					}
				
				$this->set_context('nav_type', $navtype);
				$this->nav_list($navigation, 'nav-'.$navtype, $level);
				$this->nav_clear($navtype);
				$this->clear_context('nav_type');
	
				$this->output('</DIV>');
			}
		}
		
		function nav_list($navigation, $class, $level=null)
		{
			$this->output('<UL CLASS="qa-'.$class.'-list'.(isset($level) ? (' qa-'.$class.'-list-'.$level) : '').'">');

			$index=0;
			
			foreach ($navigation as $key => $navlink) {
				$this->set_context('nav_key', $key);
				$this->set_context('nav_index', $index++);
				$this->nav_item($key, $navlink, $class, $level);
			}

			$this->clear_context('nav_key');
			$this->clear_context('nav_index');
			
			$this->output('</UL>');
		}
		
		function nav_clear($navtype)
		{
			$this->output(
				'<DIV CLASS="qa-nav-'.$navtype.'-clear">',
				'</DIV>'
			);
		}
		
		function nav_item($key, $navlink, $class, $level=null)
		{
			$this->output('<LI CLASS="qa-'.$class.'-item'.(@$navlink['opposite'] ? '-opp' : '').
				(@$navlink['state'] ? (' qa-'.$class.'-'.$navlink['state']) : '').' qa-'.$class.'-'.$key.'">');
			$this->nav_link($navlink, $class);
			
			if (count(@$navlink['subnav']))
				$this->nav_list($navlink['subnav'], $class, 1+$level);
			
			$this->output('</LI>');
		}
		
		function nav_link($navlink, $class)
		{
			if (isset($navlink['url'])) {
				$this->output(
					'<A HREF="'.$navlink['url'].'" CLASS="qa-'.$class.'-link'.
					(@$navlink['selected'] ? (' qa-'.$class.'-selected') : '').'"'.
					(strlen(@$navlink['popup']) ? (' TITLE="'.$navlink['popup'].'"') : '').
					(isset($navlink['target']) ? (' TARGET="'.$navlink['target'].'"') : '').'>'.$navlink['label'].
					'</A>'
				);

			} else
				$this->output($navlink['label']);

			if (strlen(@$navlink['note']))
				$this->output('<SPAN CLASS="qa-'.$class.'-note">'.$navlink['note'].'</SPAN>');
		}
		
		function logged_in()
		{
			$this->output_split(@$this->content['loggedin'], 'qa-logged-in', 'DIV');
		}
		
		function header_clear()
		{
			$this->output(
				'<DIV CLASS="qa-header-clear">',
				'</DIV>'
			);
		}
		
		function sidepanel()
		{
			$this->output('<DIV CLASS="qa-sidepanel">');
			$this->widgets('side', 'top');
			$this->sidebar();
			$this->widgets('side', 'high');
			$this->nav('cat', 1);
			$this->widgets('side', 'low');
			$this->output_raw(@$this->content['sidepanel']);
			$this->feed();
			$this->widgets('side', 'bottom');
			$this->output('</DIV>', '');
		}
		
		function sidebar()
		{
			$sidebar=@$this->content['sidebar'];
			
			if (!empty($sidebar)) {
				$this->output('<DIV CLASS="qa-sidebar">');
				$this->output_raw($sidebar);
				$this->output('</DIV>', '');
			}
		}
		
		function feed()
		{
			$feed=@$this->content['feed'];
			
			if (!empty($feed)) {
				$this->output('<DIV CLASS="qa-feed">');
				$this->output('<A HREF="'.$feed['url'].'" CLASS="qa-feed-link">'.@$feed['label'].'</A>');
				$this->output('</DIV>');
			}
		}
		
		function main()
		{
			$content=$this->content;

			$this->output('<DIV CLASS="qa-main'.(@$this->content['hidden'] ? ' qa-main-hidden' : '').'">');
			
			$this->widgets('main', 'top');
			
			$this->page_title_error();		
			
			$this->widgets('main', 'high');

			/*if (isset($content['main_form_tags']))
				$this->output('<FORM '.$content['main_form_tags'].'>');*/
				
			$this->main_parts($content);
		
			/*if (isset($content['main_form_tags']))
				$this->output('</FORM>');*/
				
			$this->widgets('main', 'low');

			$this->page_links();
			$this->suggest_next();
			
			$this->widgets('main', 'bottom');

			$this->output('</DIV> <!-- END qa-main -->', '');
		}
		
		function page_title_error()
		{
			$favorite=@$this->content['favorite'];
			
			if (isset($favorite))
				$this->output('<FORM '.$favorite['form_tags'].'>');
				
			$this->output('<H1>');
			$this->favorite();
			$this->title();
			$this->output('</H1>');

			if (isset($this->content['error']))
				$this->error(@$this->content['error']);

			if (isset($favorite))
				$this->output('</FORM>');
		}
		
		function favorite()
		{
			$favorite=@$this->content['favorite'];
			
			if (isset($favorite)) {
				$this->output('<SPAN CLASS="qa-favoriting" '.@$favorite['favorite_tags'].'>');
				$this->favorite_inner_html($favorite);
				$this->output('</SPAN>');
			}
		}
		
		function title()
		{
			if (isset($this->content['title']))
				$this->output($this->content['title']);
		}
		
		function favorite_inner_html($favorite)
		{
			$this->favorite_button(@$favorite['favorite_add_tags'], 'qa-favorite');
			$this->favorite_button(@$favorite['favorite_remove_tags'], 'qa-unfavorite');
		}
		
		function favorite_button($tags, $class)
		{
			if (isset($tags))
				$this->output('<INPUT '.$tags.' TYPE="submit" VALUE="" CLASS="'.$class.
					'-button" onmouseover="this.className=\''.$class.'-hover\';" onmouseout="this.className=\''.$class.'-button\';"/> ');
		}
		
		function error($error)
		{
			if (strlen($error))
				$this->output(
					'<DIV CLASS="qa-error">',
					$error,
					'</DIV>'
				);
		}
		
		function main_parts($content)
		{
			foreach ($content as $key => $part) {
				$this->set_context('part', $key);
				$this->main_part($key, $part);
			}

			$this->clear_context('part');
		}
		
		function main_part($key, $part)
		{
			if (strpos($key, 'custom')===0)
				$this->output_raw($part);

			elseif (strpos($key, 'form')===0)
				$this->form($part);
				
			elseif (strpos($key, 'q_list')===0)
				$this->q_list_and_form($part);

			elseif (strpos($key, 'q_view')===0)
				$this->q_view($part);
				
			elseif (strpos($key, 'a_form')===0)
				$this->a_form($part);
			
			elseif (strpos($key, 'a_list')===0)
				$this->a_list($part);
				
			elseif (strpos($key, 'ranking')===0)
				$this->ranking($part);
				
			elseif (strpos($key, 'nav_list')===0) {
				$this->section(@$part['title']);		
				$this->nav_list($part['nav'], $part['type'], 1);
			}
		}
		
		function footer()
		{
			$this->output('<DIV CLASS="qa-footer">');
			
			$this->nav('footer');
			$this->attribution();
			$this->footer_clear();
			
			$this->output('</DIV> <!-- END qa-footer -->', '');
		}
		
		function attribution()
		{
			// Hi there. I'd really appreciate you displaying this link on your Q2A site. Thank you - Gideon
				
			$this->output(
				'<DIV CLASS="qa-attribution">',
				'Powered by <A HREF="http://www.question2answer.org/">Question2Answer</A>',
				'</DIV>'
			);
		}
		
		function footer_clear()
		{
			$this->output(
				'<DIV CLASS="qa-footer-clear">',
				'</DIV>'
			);
		}

		function section($title)
		{
			if (!empty($title))
				$this->output('<H2>'.$title.'</H2>');
		}
		
		function form($form)
		{
			if (!empty($form)) {
				$this->section(@$form['title']);
				
				if (isset($form['tags']))
					$this->output('<FORM '.$form['tags'].'>');
				
				$this->form_body($form);
	
				if (isset($form['tags']))
					$this->output('</FORM>');
			}
		}
		
		function form_columns($form)
		{
			if (isset($form['ok']) || !empty($form['fields']) )
				$columns=($form['style']=='wide') ? 3 : 1;
			else
				$columns=0;
				
			return $columns;
		}
		
		function form_spacer($form, $columns)
		{
			$this->output(
				'<TR>',
				'<TD COLSPAN="'.$columns.'" CLASS="qa-form-'.$form['style'].'-spacer">',
				'&nbsp;',
				'</TD>',
				'</TR>'
			);
		}
		
		function form_body($form)
		{
			$columns=$this->form_columns($form);
			
			if ($columns)
				$this->output('<TABLE CLASS="qa-form-'.$form['style'].'-table">');
			
			$this->form_ok($form, $columns);
			$this->form_fields($form, $columns);
			$this->form_buttons($form, $columns);

			if ($columns)
				$this->output('</TABLE>');

			$this->form_hidden($form);
		}
		
		function form_ok($form, $columns)
		{
			if (!empty($form['ok']))
				$this->output(
					'<TR>',
					'<TD COLSPAN="'.$columns.'" CLASS="qa-form-'.$form['style'].'-ok">',
					$form['ok'],
					'</TD>',
					'</TR>'
				);
		}
		
		function form_fields($form, $columns)
		{
			if (!empty($form['fields'])) {
				foreach ($form['fields'] as $key => $field) {
					$this->set_context('field_key', $key);
					
					if (@$field['type']=='blank')
						$this->form_spacer($form, $columns);
					else
						$this->form_field_rows($form, $columns, $field);
				}
						
				$this->clear_context('field_key');
			}
		}
		
		function form_field_rows($form, $columns, $field)
		{
			$style=$form['style'];
			
			if (isset($field['style'])) { // field has different style to most of form
				$style=$field['style'];
				$colspan=$columns;
				$columns=($style=='wide') ? 3 : 1;
			} else
				$colspan=null;
			
			$prefixed=((@$field['type']=='checkbox') && ($columns==1) && !empty($field['label']));
			$suffixed=(((@$field['type']=='select') || (@$field['type']=='number')) && ($columns==1) && !empty($field['label'])) && (!@$field['loose']);
			$skipdata=@$field['tight'];
			$tworows=($columns==1) && (!empty($field['label'])) && (!$skipdata) &&
				( (!($prefixed||$suffixed)) || (!empty($field['error'])) || (!empty($field['note'])) );
			
			if (($columns==1) && isset($field['id']))
				$this->output('<TBODY ID="'.$field['id'].'">', '<TR>');
			elseif (isset($field['id']))
				$this->output('<TR ID="'.$field['id'].'">');
			else
				$this->output('<TR>');
			
			if (($columns>1) || !empty($field['label']))
				$this->form_label($field, $style, $columns, $prefixed, $suffixed, $colspan);
			
			if ($tworows)
				$this->output(
					'</TR>',
					'<TR>'
				);
			
			if (!$skipdata)
				$this->form_data($field, $style, $columns, !($prefixed||$suffixed), $colspan);
			
			$this->output('</TR>');
			
			if (($columns==1) && isset($field['id']))
				$this->output('</TBODY>');
		}
		
		function form_label($field, $style, $columns, $prefixed, $suffixed, $colspan)
		{
			$extratags='';
			
			if ( ($columns>1) && ((@$field['type']=='select-radio') || (@$field['rows']>1)) )
				$extratags.=' STYLE="vertical-align:top;"';
				
			if (isset($colspan))
				$extratags.=' COLSPAN="'.$colspan.'"';
			
			$this->output('<TD CLASS="qa-form-'.$style.'-label"'.$extratags.'>');
			
			if ($prefixed) {
				$this->output('<LABEL>');
				$this->form_field($field, $style);
			}
					
			$this->output(@$field['label']);
			
			if ($prefixed)
				$this->output('</LABEL>');

			if ($suffixed) {
				$this->output('&nbsp;');
				$this->form_field($field, $style);
			}
			
			$this->output('</TD>');
		}
		
		function form_data($field, $style, $columns, $showfield, $colspan)
		{
			if ($showfield || (!empty($field['error'])) || (!empty($field['note']))) {
				$this->output(
					'<TD CLASS="qa-form-'.$style.'-data"'.(isset($colspan) ? (' COLSPAN="'.$colspan.'"') : '').'>'
				);
							
				if ($showfield)
					$this->form_field($field, $style);
	
				if (!empty($field['error'])) {
					if (@$field['note_force'])
						$this->form_note($field, $style, $columns);
						
					$this->form_error($field, $style, $columns);
				
				} elseif (!empty($field['note']))
					$this->form_note($field, $style, $columns);
				
				$this->output('</TD>');
			}
		}
		
		function form_field($field, $style)
		{
			$this->form_prefix($field, $style);
			
			switch (@$field['type']) {
				case 'checkbox':
					$this->form_checkbox($field, $style);
					break;
				
				case 'static':
					$this->form_static($field, $style);
					break;
				
				case 'password':
					$this->form_password($field, $style);
					break;
				
				case 'number':
					$this->form_number($field, $style);
					break;
				
				case 'select':
					$this->form_select($field, $style);
					break;
					
				case 'select-radio':
					$this->form_select_radio($field, $style);
					break;
					
				case 'image':
					$this->form_image($field, $style);
					break;
				
				case 'custom':
					echo @$field['html'];
					break;
				
				default:
					if ((@$field['type']=='textarea') || (@$field['rows']>1))
						$this->form_text_multi_row($field, $style);
					else
						$this->form_text_single_row($field, $style);
					break;
			}	

			$this->form_suffix($field, $style);
		}
		
		function form_buttons($form, $columns)
		{
			if (!empty($form['buttons'])) {
				$style=@$form['style'];
				
				if ($columns)
					$this->output(
						'<TR>',
						'<TD COLSPAN="'.$columns.'" CLASS="qa-form-'.$style.'-buttons">'
					);

				foreach ($form['buttons'] as $key => $button) {
					$this->set_context('button_key', $key);
					
					if (empty($button))
						$this->form_button_spacer($style);
					else {
						$this->form_button_data($button, $key, $style);
						$this->form_button_note($button, $style);
					}
				}
	
				$this->clear_context('button_key');

				if ($columns)
					$this->output(
						'</TD>',
						'</TR>'
					);
			}
		}
		
		function form_button_data($button, $key, $style)
		{
			$baseclass='qa-form-'.$style.'-button qa-form-'.$style.'-button-'.$key;
			$hoverclass='qa-form-'.$style.'-hover qa-form-'.$style.'-hover-'.$key;
			
			$this->output('<INPUT'.rtrim(' '.@$button['tags']).' VALUE="'.@$button['label'].'" TITLE="'.@$button['popup'].'" TYPE="submit"'.
				(isset($style) ? (' CLASS="'.$baseclass.'" onmouseover="this.className=\''.$hoverclass.'\';" onmouseout="this.className=\''.$baseclass.'\';"') : '').'/>');
		}
		
		function form_button_note($button, $style)
		{
			if (!empty($button['note']))
				$this->output(
					'<SPAN CLASS="qa-form-'.$style.'-note">',
					$button['note'],
					'</SPAN>',
					'<BR/>'
				);
		}
		
		function form_button_spacer($style)
		{
			$this->output('<SPAN CLASS="qa-form-'.$style.'-buttons-spacer">&nbsp;</SPAN>');
		}
		
		function form_hidden($form)
		{
			if (!empty($form['hidden']))
				foreach ($form['hidden'] as $name => $value)
					$this->output('<INPUT TYPE="hidden" NAME="'.$name.'" VALUE="'.$value.'"/>');
		}
		
		function form_prefix($field, $style)
		{
			if (!empty($field['prefix']))
				$this->output('<SPAN CLASS="qa-form-'.$style.'-prefix">'.$field['prefix'].'</SPAN>');
		}
		
		function form_suffix($field, $style)
		{
			if (!empty($field['suffix']))
				$this->output('<SPAN CLASS="qa-form-'.$style.'-suffix">'.$field['suffix'].'</SPAN>');
		}
		
		function form_checkbox($field, $style)
		{
			$this->output('<INPUT '.@$field['tags'].' TYPE="checkbox" VALUE="1"'.(@$field['value'] ? ' CHECKED' : '').' CLASS="qa-form-'.$style.'-checkbox"/>');
		}
		
		function form_static($field, $style)
		{
			$this->output('<SPAN CLASS="qa-form-'.$style.'-static">'.@$field['value'].'</SPAN>');
		}
		
		function form_password($field, $style)
		{
			$this->output('<INPUT '.@$field['tags'].' TYPE="password" VALUE="'.@$field['value'].'" CLASS="qa-form-'.$style.'-text"/>');
		}
		
		function form_number($field, $style)
		{
			$this->output('<INPUT '.@$field['tags'].' TYPE="text" VALUE="'.@$field['value'].'" CLASS="qa-form-'.$style.'-number"/>');
		}
		
		function form_select($field, $style)
		{
			$this->output('<SELECT '.@$field['tags'].' CLASS="qa-form-'.$style.'-select">');
			
			foreach ($field['options'] as $tag => $value)
				$this->output('<OPTION VALUE="'.$tag.'"'.(($value==@$field['value']) ? ' SELECTED' : '').'>'.$value.'</OPTION>');
			
			$this->output('</SELECT>');
		}
		
		function form_select_radio($field, $style)
		{
			$radios=0;
			
			foreach ($field['options'] as $tag => $value) {
				if ($radios++)
					$this->output('<BR/>');
					
				$this->output('<INPUT '.@$field['tags'].' TYPE="radio" VALUE="'.$tag.'"'.(($value==@$field['value']) ? ' CHECKED' : '').' CLASS="qa-form-'.$style.'-radio"/> '.$value);
			}
		}
		
		function form_image($field, $style)
		{
			$this->output('<DIV CLASS="qa-form-'.$style.'-image">'.@$field['html'].'</DIV>');
		}
		
		function form_text_single_row($field, $style)
		{
			$this->output('<INPUT '.@$field['tags'].' TYPE="text" VALUE="'.@$field['value'].'" CLASS="qa-form-'.$style.'-text"/>');
		}
		
		function form_text_multi_row($field, $style)
		{
			$this->output('<TEXTAREA '.@$field['tags'].' ROWS="'.(int)$field['rows'].'" COLS="40" CLASS="qa-form-'.$style.'-text">'.@$field['value'].'</TEXTAREA>');
		}
		
		function form_error($field, $style, $columns)
		{
			$tag=($columns>1) ? 'SPAN' : 'DIV';
			
			$this->output('<'.$tag.' CLASS="qa-form-'.$style.'-error">'.$field['error'].'</'.$tag.'>');
		}
		
		function form_note($field, $style, $columns)
		{
			$tag=($columns>1) ? 'SPAN' : 'DIV';
			
			$this->output('<'.$tag.' CLASS="qa-form-'.$style.'-note">'.@$field['note'].'</'.$tag.'>');
		}
		
		function ranking($ranking)
		{
			$this->section(@$ranking['title']);
			
			$class=(@$ranking['type']=='users') ? 'qa-top-users' : 'qa-top-tags';
			
			$rows=min($ranking['rows'], count($ranking['items']));
			
			if ($rows>0) {
				$this->output('<TABLE CLASS="'.$class.'-table">');
			
				$columns=ceil(count($ranking['items'])/$rows);
				
				for ($row=0; $row<$rows; $row++) {
					$this->set_context('ranking_row', $row);
					$this->output('<TR>');
		
					for ($column=0; $column<$columns; $column++) {
						$this->set_context('ranking_column', $column);
						$this->ranking_item(@$ranking['items'][$column*$rows+$row], $class, $column>0);
					}

					$this->clear_context('ranking_column');
		
					$this->output('</TR>');
				}
			
				$this->clear_context('ranking_row');

				$this->output('</TABLE>');
			}
		}
		
		function ranking_item($item, $class, $spacer)
		{
			if ($spacer)
				$this->ranking_spacer($class);
			
			if (empty($item)) {
				$this->ranking_spacer($class);
				$this->ranking_spacer($class);
			
			} else {
				if (isset($item['count']))
					$this->ranking_count($item, $class);
					
				$this->ranking_label($item, $class);
					
				if (isset($item['score']))
					$this->ranking_score($item, $class);
			}
		}
		
		function ranking_spacer($class)
		{
			$this->output('<TD CLASS="'.$class.'-spacer">&nbsp;</TD>');
		}
		
		function ranking_count($item, $class)
		{
			$this->output('<TD CLASS="'.$class.'-count">'.$item['count'].' &#215;'.'</TD>');
		}
		
		function ranking_label($item, $class)
		{
			$this->output('<TD CLASS="'.$class.'-label">'.$item['label'].'</TD>');
		}
		
		function ranking_score($item, $class)
		{
			$this->output('<TD CLASS="'.$class.'-score">'.$item['score'].'</TD>');
		}
		
		function list_vote_disabled($items)
		{
			$disabled=false;
			
			if (count($items)) {
				$disabled=true;
				
				foreach ($items as $item)
					if (@$item['vote_on_page']!='disabled')
						$disabled=false;
			}
				
			return $disabled;
		}
		
		function q_list_and_form($q_list)
		{
			if (!empty($q_list)) {
				$this->section(@$q_list['title']);
	
				if (!empty($q_list['form']))
					$this->output('<FORM '.$q_list['form']['tags'].'>');
				
				$this->q_list($q_list);
				
				if (!empty($q_list['form'])) {
					unset($q_list['form']['tags']); // we already output the tags before the qs
					$this->q_list_form($q_list);
					$this->output('</FORM>');
				}
			}
		}
		
		function q_list_form($q_list)
		{
			if (!empty($q_list['form'])) {
				$this->output('<DIV CLASS="qa-q-list-form">');
				$this->form($q_list['form']);
				$this->output('</DIV>');
			}
		}
		
		function q_list($q_list)
		{
			if (isset($q_list['qs'])) {
				$this->output('<DIV CLASS="qa-q-list'.($this->list_vote_disabled($q_list['qs']) ? ' qa-q-list-vote-disabled' : '').'">', '');
				
				foreach ($q_list['qs'] as $q_item)
					$this->q_list_item($q_item);
	
				$this->output('</DIV> <!-- END qa-q-list -->', '');
			}
		}
		
		function q_list_item($q_item)
		{
			$this->output('<DIV CLASS="qa-q-list-item'.rtrim(' '.@$q_item['classes']).'" '.@$q_item['tags'].'>');

			$this->q_item_stats($q_item);
			$this->q_item_main($q_item);
			$this->q_item_clear();

			$this->output('</DIV> <!-- END qa-q-list-item -->', '');
		}
		
		function q_item_stats($q_item)
		{
			$this->output('<DIV CLASS="qa-q-item-stats">');
			
			$this->voting($q_item);
			$this->a_count($q_item);

			$this->output('</DIV>');
		}
		
		function q_item_main($q_item)
		{
			$this->output('<DIV CLASS="qa-q-item-main">');
			
			$this->view_count($q_item);
			$this->q_item_title($q_item);
			$this->q_item_content($q_item);
			
			$this->post_avatar_meta($q_item, 'qa-q-item');
			$this->post_tags($q_item, 'qa-q-item');
			$this->q_item_buttons($q_item);
				
			$this->output('</DIV>');
		}
		
		function q_item_clear()
		{
			$this->output(
				'<DIV CLASS="qa-q-item-clear">',
				'</DIV>'
			);
		}
		
		function q_item_title($q_item)
		{
			$this->output(
				'<DIV CLASS="qa-q-item-title">',
				'<A HREF="'.$q_item['url'].'">'.$q_item['title'].'</A>',
				'</DIV>'
			);
		}
		
		function q_item_content($q_item)
		{
			if (!empty($q_item['content'])) {
				$this->output('<DIV CLASS="qa-q-item-content">');
				$this->output_raw($q_item['content']);
				$this->output('</DIV>');
			}
		}
		
		function q_item_buttons($q_item)
		{
			if (!empty($q_item['form'])) {
				$this->output('<DIV CLASS="qa-q-item-buttons">');
				$this->form($q_item['form']);
				$this->output('</DIV>');
			}
		}
		
		function voting($post)
		{
			if (isset($post['vote_view'])) {
				$this->output('<DIV CLASS="qa-voting '.(($post['vote_view']=='updown') ? 'qa-voting-updown' : 'qa-voting-net').'" '.@$post['vote_tags'].'>');
				$this->voting_inner_html($post);
				$this->output('</DIV>');
			}
		}
		
		function voting_inner_html($post)
		{
			$this->vote_buttons($post);
			$this->vote_count($post);
			$this->vote_clear();
		}
		
		function vote_buttons($post)
		{
			$this->output('<DIV CLASS="qa-vote-buttons '.(($post['vote_view']=='updown') ? 'qa-vote-buttons-updown' : 'qa-vote-buttons-net').'">');

			switch (@$post['vote_state'])
			{
				case 'voted_up':
					$this->post_hover_button($post, 'vote_up_tags', '+', 'qa-vote-one-button qa-voted-up');
					break;
					
				case 'voted_up_disabled':
					$this->post_disabled_button($post, 'vote_up_tags', '+', 'qa-vote-one-button qa-vote-up');
					break;
					
				case 'voted_down':
					$this->post_hover_button($post, 'vote_down_tags', '&ndash;', 'qa-vote-one-button qa-voted-down');
					break;
					
				case 'voted_down_disabled':
					$this->post_disabled_button($post, 'vote_down_tags', '&ndash;', 'qa-vote-one-button qa-vote-down');
					break;
					
				case 'up_only':
					$this->post_hover_button($post, 'vote_up_tags', '+', 'qa-vote-first-button qa-vote-up');
					$this->post_disabled_button($post, 'vote_down_tags', '', 'qa-vote-second-button qa-vote-down');
					break;
				
				case 'enabled':
					$this->post_hover_button($post, 'vote_up_tags', '+', 'qa-vote-first-button qa-vote-up');
					$this->post_hover_button($post, 'vote_down_tags', '&ndash;', 'qa-vote-second-button qa-vote-down');
					break;

				default:
					$this->post_disabled_button($post, 'vote_up_tags', '', 'qa-vote-first-button qa-vote-up');
					$this->post_disabled_button($post, 'vote_down_tags', '', 'qa-vote-second-button qa-vote-down');
					break;
			}

			$this->output('</DIV>');
		}
		
		function vote_count($post)
		{
			// You can also use $post['upvotes_raw'], $post['downvotes_raw'], $post['netvotes_raw'] to get
			// raw integer vote counts, for graphing or showing in other non-textual ways
			
			$this->output('<DIV CLASS="qa-vote-count '.(($post['vote_view']=='updown') ? 'qa-vote-count-updown' : 'qa-vote-count-net').'">');

			if ($post['vote_view']=='updown') {
				$this->output_split($post['upvotes_view'], 'qa-upvote-count');
				$this->output_split($post['downvotes_view'], 'qa-downvote-count');
			
			} else
				$this->output_split($post['netvotes_view'], 'qa-netvote-count');

			$this->output('</DIV>');
		}
		
		function vote_clear()
		{
			$this->output(
				'<DIV CLASS="qa-vote-clear">',
				'</DIV>'
			);
		}
		
		function a_count($post)
		{
			// You can also use $post['answers_raw'] to get a raw integer count of answers
			
			$this->output_split(@$post['answers'], 'qa-a-count', 'SPAN', 'SPAN',
				@$post['answer_selected'] ? 'qa-a-count-selected' : (@$post['answers_raw'] ? null : 'qa-a-count-zero'));
		}
		
		function view_count($post)
		{
			// You can also use $post['views_raw'] to get a raw integer count of views
			
			$this->output_split(@$post['views'], 'qa-view-count');
		}
		
		function avatar($post, $class)
		{
			if (isset($post['avatar']))
				$this->output('<SPAN CLASS="'.$class.'-avatar">', $post['avatar'], '</SPAN>');
		}
		
		function a_selection($post)
		{
			$this->output('<DIV CLASS="qa-a-selection">');
			
			if (isset($post['select_tags']))
				$this->post_hover_button($post, 'select_tags', '', 'qa-a-select');
			elseif (isset($post['unselect_tags']))
				$this->post_hover_button($post, 'unselect_tags', '', 'qa-a-unselect');
			elseif ($post['selected'])
				$this->output('<DIV CLASS="qa-a-selected">&nbsp;</DIV>');
			
			if (isset($post['select_text']))
				$this->output('<DIV CLASS="qa-a-selected-text">'.@$post['select_text'].'</DIV>');
			
			$this->output('</DIV>');
		}
		
		function post_hover_button($post, $element, $value, $class)
		{
			if (isset($post[$element]))
				$this->output('<INPUT '.$post[$element].' TYPE="submit" VALUE="'.$value.'" CLASS="'.$class.
					'-button" onmouseover="this.className=\''.$class.'-hover\';" onmouseout="this.className=\''.$class.'-button\';"/> ');
		}
		
		function post_disabled_button($post, $element, $value, $class)
		{
			if (isset($post[$element]))
				$this->output('<INPUT '.$post[$element].' TYPE="submit" VALUE="'.$value.'" CLASS="'.$class.'-disabled" DISABLED="disabled"/> ');
		}
		
		function post_avatar_meta($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<BR/>')
		{
			$this->output('<SPAN CLASS="'.$class.'-avatar-meta">');
			$this->post_avatar($post, $class, $avatarprefix);
			$this->post_meta($post, $class, $metaprefix, $metaseparator);
			$this->output('</SPAN>');
		}
		
		function post_avatar($post, $class, $prefix=null)
		{
			if (isset($post['avatar'])) {
				if (isset($prefix))
					$this->output($prefix);

				$this->output('<SPAN CLASS="'.$class.'-avatar">', $post['avatar'], '</SPAN>');
			}
		}
		
		function post_meta($post, $class, $prefix=null, $separator='<BR/>')
		{
			$this->output('<SPAN CLASS="'.$class.'-meta">');
			
			if (isset($prefix))
				$this->output($prefix);
			
			$order=explode('^', @$post['meta_order']);
			
			foreach ($order as $element)
				switch ($element) {
					case 'what':
						$this->post_meta_what($post, $class);
						break;
						
					case 'when':
						$this->post_meta_when($post, $class);
						break;
						
					case 'where':
						$this->post_meta_where($post, $class);
						break;
						
					case 'who':
						$this->post_meta_who($post, $class);
						break;
				}
				
			$this->post_meta_flags($post, $class);
			
			if (!empty($post['what_2'])) {
				$this->output($separator);
				
				foreach ($order as $element)
					switch ($element) {
						case 'what':
							$this->output('<SPAN CLASS="'.$class.'-what">'.$post['what_2'].'</SPAN>');
							break;
						
						case 'when':
							$this->output_split(@$post['when_2'], $class.'-when');
							break;
						
						case 'who':
							$this->output_split(@$post['who_2'], $class.'-who');
							break;
					}
			}
			
			$this->output('</SPAN>');
		}
		
		function post_meta_what($post, $class)
		{
			if (isset($post['what'])) {
				if (isset($post['what_url']))
					$this->output('<A HREF="'.$post['what_url'].'" CLASS="'.$class.'-what">'.$post['what'].'</A>');
				else
					$this->output('<SPAN CLASS="'.$class.'-what">'.$post['what'].'</SPAN>');
			}
		}
		
		function post_meta_when($post, $class)
		{
			$this->output_split(@$post['when'], $class.'-when');
		}
		
		function post_meta_where($post, $class)
		{
			$this->output_split(@$post['where'], $class.'-where');
		}
		
		function post_meta_who($post, $class)
		{
			if (isset($post['who'])) {
				$this->output('<SPAN CLASS="'.$class.'-who">');
				
				if (strlen(@$post['who']['prefix']))
					$this->output('<SPAN CLASS="'.$class.'-who-pad">'.$post['who']['prefix'].'</SPAN>');
				
				if (isset($post['who']['data']))
					$this->output('<SPAN CLASS="'.$class.'-who-data">'.$post['who']['data'].'</SPAN>');
				
				if (isset($post['who']['title']))
					$this->output('<SPAN CLASS="'.$class.'-who-title">'.$post['who']['title'].'</SPAN>');
					
				// You can also use $post['level'] to get the author's privilege level (as a string)
	
				if (isset($post['who']['points'])) {
					$post['who']['points']['prefix']='('.$post['who']['points']['prefix'];
					$post['who']['points']['suffix'].=')';
					$this->output_split($post['who']['points'], $class.'-who-points');
				}
				
				if (strlen(@$post['who']['suffix']))
					$this->output('<SPAN CLASS="'.$class.'-who-pad">'.$post['who']['suffix'].'</SPAN>');
	
				$this->output('</SPAN>');
			}
		}
		
		function post_meta_flags($post, $class)
		{
			$this->output_split(@$post['flags'], $class.'-flags');
		}
		
		function post_tags($post, $class)
		{
			if (!empty($post['q_tags'])) {
				$this->output('<DIV CLASS="'.$class.'-tags">');
				$this->post_tag_list($post, $class);
				$this->output('</DIV>');
			}
		}
		
		function post_tag_list($post, $class)
		{
			$this->output('<UL CLASS="'.$class.'-tag-list">');
			
			foreach ($post['q_tags'] as $taghtml)
				$this->post_tag_item($taghtml, $class);
				
			$this->output('</UL>');
		}
		
		function post_tag_item($taghtml, $class)
		{
			$this->output('<LI CLASS="'.$class.'-tag-item">'.$taghtml.'</LI>');
		}
	
		function page_links()
		{
			$page_links=@$this->content['page_links'];
			
			if (!empty($page_links)) {
				$this->output('<DIV CLASS="qa-page-links">');
				
				$this->page_links_label(@$page_links['label']);
				$this->page_links_list(@$page_links['items']);
				$this->page_links_clear();
				
				$this->output('</DIV>');
			}
		}
		
		function page_links_label($label)
		{
			if (!empty($label))
				$this->output('<SPAN CLASS="qa-page-links-label">'.$label.'</SPAN>');
		}
		
		function page_links_list($page_items)
		{
			if (!empty($page_items)) {
				$this->output('<UL CLASS="qa-page-links-list">');
				
				$index=0;
				
				foreach ($page_items as $page_link) {
					$this->set_context('page_index', $index++);
					$this->page_links_item($page_link);
					
					if ($page_link['ellipsis'])
						$this->page_links_item(array('type' => 'ellipsis'));
				}
				
				$this->clear_context('page_index');
				
				$this->output('</UL>');
			}
		}
		
		function page_links_item($page_link)
		{
			$this->output('<LI CLASS="qa-page-links-item">');
			$this->page_link_content($page_link);
			$this->output('</LI>');
		}
		
		function page_link_content($page_link)
		{
			$label=@$page_link['label'];
			$url=@$page_link['url'];
			
			switch ($page_link['type']) {
				case 'this':
					$this->output('<SPAN CLASS="qa-page-selected">'.$label.'</SPAN>');
					break;
				
				case 'prev':
					$this->output('<A HREF="'.$url.'" CLASS="qa-page-prev">&laquo; '.$label.'</A>');
					break;
				
				case 'next':
					$this->output('<A HREF="'.$url.'" CLASS="qa-page-next">'.$label.' &raquo;</A>');
					break;
				
				case 'ellipsis':
					$this->output('<SPAN CLASS="qa-page-ellipsis">...</SPAN>');
					break;
				
				default:
					$this->output('<A HREF="'.$url.'" CLASS="qa-page-link">'.$label.'</A>');
					break;
			}
		}
		
		function page_links_clear()
		{
			$this->output(
				'<DIV CLASS="qa-page-links-clear">',
				'</DIV>'
			);
		}

		function suggest_next()
		{
			$suggest=@$this->content['suggest_next'];
			
			if (!empty($suggest)) {
				$this->output('<DIV CLASS="qa-suggest-next">');
				$this->output($suggest);
				$this->output('</DIV>');
			}
		}
		
		function q_view($q_view)
		{
			if (!empty($q_view)) {
				$this->output('<DIV CLASS="qa-q-view'.(@$q_view['hidden'] ? ' qa-q-view-hidden' : '').rtrim(' '.@$q_view['classes']).'"'.rtrim(' '.@$q_view['tags']).'>');
				
				if (isset($q_view['main_form_tags']))
					$this->output('<FORM '.$q_view['main_form_tags'].'>'); // form for voting buttons
				
				$this->voting($q_view);
				
				if (isset($q_view['main_form_tags']))
					$this->output('</FORM>');
					
				$this->a_count($q_view);
				$this->q_view_main($q_view);
				$this->q_view_clear();
				
				$this->output('</DIV> <!-- END qa-q-view -->', '');
			}
		}
		
		function q_view_main($q_view)
		{
			$this->output('<DIV CLASS="qa-q-view-main">');

			if (isset($q_view['main_form_tags']))
				$this->output('<FORM '.$q_view['main_form_tags'].'>'); // form for buttons on question

			$this->q_view_content($q_view);
			$this->q_view_extra($q_view);
			$this->q_view_follows($q_view);
			$this->q_view_closed($q_view);
			$this->post_tags($q_view, 'qa-q-view');
			$this->post_avatar_meta($q_view, 'qa-q-view');
			$this->q_view_buttons($q_view);
			$this->c_list(@$q_view['c_list'], 'qa-q-view');
			
			if (isset($q_view['main_form_tags']))
				$this->output('</FORM>');
			
			$this->c_form(@$q_view['c_form']);
			
			$this->output('</DIV> <!-- END qa-q-view-main -->');
		}
		
		function q_view_content($q_view)
		{
			if (!empty($q_view['content'])) {
				$this->output('<DIV CLASS="qa-q-view-content">');
				$this->output_raw($q_view['content']);
				$this->output('</DIV>');
			}
		}
		
		function q_view_follows($q_view)
		{
			if (!empty($q_view['follows']))
				$this->output(
					'<DIV CLASS="qa-q-view-follows">',
					$q_view['follows']['label'],
					'<A HREF="'.$q_view['follows']['url'].'" CLASS="qa-q-view-follows-link">'.$q_view['follows']['title'].'</A>',
					'</DIV>'
				);
		}
		
		function q_view_closed($q_view)
		{
			if (!empty($q_view['closed'])) {
				$haslink=isset($q_view['closed']['url']);
				
				$this->output(
					'<DIV CLASS="qa-q-view-closed">',
					$q_view['closed']['label'],
					($haslink ? ('<A HREF="'.$q_view['closed']['url'].'"') : '<SPAN').' CLASS="qa-q-view-closed-content">',
					$q_view['closed']['content'],
					$haslink ? '</A>' : '</SPAN>',
					'</DIV>'
				);
			}
		}
		
		function q_view_extra($q_view)
		{
			if (!empty($q_view['extra']))
				$this->output(
					'<DIV CLASS="qa-q-view-extra">',
					$q_view['extra']['label'],
					'<SPAN CLASS="qa-q-view-extra-content">',
					$q_view['extra']['content'],
					'</SPAN>',
					'</DIV>'
				);
		}
		
		function q_view_buttons($q_view)
		{
			if (!empty($q_view['form'])) {
				$this->output('<DIV CLASS="qa-q-view-buttons">');
				$this->form($q_view['form']);
				$this->output('</DIV>');
			}
		}
		
		function q_view_clear()
		{
			$this->output(
				'<DIV CLASS="qa-q-view-clear">',
				'</DIV>'
			);
		}
		
		function a_form($a_form)
		{
			$this->output('<DIV CLASS="qa-a-form"'.(isset($a_form['id']) ? (' ID="'.$a_form['id'].'"') : '').
				(@$a_form['collapse'] ? ' STYLE="display:none;"' : '').'>');

			$this->form($a_form);
			$this->c_list(@$a_form['c_list'], 'qa-a-item');
			
			$this->output('</DIV> <!-- END qa-a-form -->', '');
		}
		
		function a_list($a_list)
		{
			if (!empty($a_list)) {
				$this->section(@$a_list['title']);
				
				$this->output('<DIV CLASS="qa-a-list'.($this->list_vote_disabled($a_list['as']) ? ' qa-a-list-vote-disabled' : '').'" '.@$a_list['tags'].'>', '');
				
				foreach ($a_list['as'] as $a_item)
					$this->a_list_item($a_item);
				
				$this->output('</DIV> <!-- END qa-a-list -->', '');
			}
		}
		
		function a_list_item($a_item)
		{
			$extraclass=@$a_item['classes'].($a_item['hidden'] ? ' qa-a-list-item-hidden' : ($a_item['selected'] ? ' qa-a-list-item-selected' : ''));
			
			$this->output('<DIV CLASS="qa-a-list-item '.$extraclass.'" '.@$a_item['tags'].'>');
			
			if (isset($a_item['main_form_tags']))
				$this->output('<FORM '.$a_item['main_form_tags'].'>'); // form for voting buttons
			
			$this->voting($a_item);
			
			if (isset($a_item['main_form_tags']))
				$this->output('</FORM>');
			
			$this->a_item_main($a_item);
			$this->a_item_clear();

			$this->output('</DIV> <!-- END qa-a-list-item -->', '');
		}
		
		function a_item_main($a_item)
		{
			$this->output('<DIV CLASS="qa-a-item-main">');
			
			if (isset($a_item['main_form_tags']))
				$this->output('<FORM '.$a_item['main_form_tags'].'>'); // form for buttons on answer

			if ($a_item['hidden'])
				$this->output('<DIV CLASS="qa-a-item-hidden">');
			elseif ($a_item['selected'])
				$this->output('<DIV CLASS="qa-a-item-selected">');

			$this->a_selection($a_item);
			$this->error(@$a_item['error']);
			$this->a_item_content($a_item);
			$this->post_avatar_meta($a_item, 'qa-a-item');
			
			if ($a_item['hidden'] || $a_item['selected'])
				$this->output('</DIV>');
			
			$this->a_item_buttons($a_item);
			
			$this->c_list(@$a_item['c_list'], 'qa-a-item');

			if (isset($a_item['main_form_tags']))
				$this->output('</FORM>');

			$this->c_form(@$a_item['c_form']);

			$this->output('</DIV> <!-- END qa-a-item-main -->');
		}
		
		function a_item_clear()
		{
			$this->output(
				'<DIV CLASS="qa-a-item-clear">',
				'</DIV>'
			);
		}
		
		function a_item_content($a_item)
		{
			$this->output('<DIV CLASS="qa-a-item-content">');
			$this->output_raw($a_item['content']);
			$this->output('</DIV>');
		}
		
		function a_item_buttons($a_item)
		{
			if (!empty($a_item['form'])) {
				$this->output('<DIV CLASS="qa-a-item-buttons">');
				$this->form($a_item['form']);
				$this->output('</DIV>');
			}
		}
		
		function c_form($c_form)
		{
			$this->output('<DIV CLASS="qa-c-form"'.(isset($c_form['id']) ? (' ID="'.$c_form['id'].'"') : '').
				(@$c_form['collapse'] ? ' STYLE="display:none;"' : '').'>');

			$this->form($c_form);
			
			$this->output('</DIV> <!-- END qa-c-form -->', '');
		}
		
		function c_list($c_list, $class)
		{
			if (!empty($c_list)) {
				$this->output('', '<DIV CLASS="'.$class.'-c-list"'.(@$c_list['hidden'] ? ' STYLE="display:none;"' : '').' '.@$c_list['tags'].'>');
				
				foreach ($c_list['cs'] as $c_item)
					$this->c_list_item($c_item);
				
				$this->output('</DIV> <!-- END qa-c-list -->', '');
			}
		}
		
		function c_list_item($c_item)
		{
			$extraclass=@$c_item['classes'].(@$c_item['hidden'] ? ' qa-c-item-hidden' : '');
			
			$this->output('<DIV CLASS="qa-c-list-item '.$extraclass.'" '.@$c_item['tags'].'>');

			$this->c_item_main($c_item);
			$this->c_item_clear();

			$this->output('</DIV> <!-- END qa-c-item -->');
		}
		
		function c_item_main($c_item)
		{
			$this->error(@$c_item['error']);

			if (isset($c_item['expand_tags']))
				$this->c_item_expand($c_item);
			elseif (isset($c_item['url']))
				$this->c_item_link($c_item);
			else
				$this->c_item_content($c_item);
			
			$this->output('<DIV CLASS="qa-c-item-footer">');
			$this->post_avatar_meta($c_item, 'qa-c-item');
			$this->c_item_buttons($c_item);
			$this->output('</DIV>');
		}
		
		function c_item_link($c_item)
		{
			$this->output(
				'<A HREF="'.$c_item['url'].'" CLASS="qa-c-item-link">'.$c_item['title'].'</A>'
			);
		}
		
		function c_item_expand($c_item)
		{
			$this->output(
				'<A HREF="'.$c_item['url'].'" '.$c_item['expand_tags'].' CLASS="qa-c-item-expand">'.$c_item['title'].'</A>'
			);
		}

		function c_item_content($c_item)
		{
			$this->output('<DIV CLASS="qa-c-item-content">');
			$this->output_raw($c_item['content']);
			$this->output('</DIV>');
		}
		
		function c_item_buttons($c_item)
		{
			if (!empty($c_item['form'])) {
				$this->output('<DIV CLASS="qa-c-item-buttons">');
				$this->form($c_item['form']);
				$this->output('</DIV>');
			}
		}
		
		function c_item_clear()
		{
			$this->output(
				'<DIV CLASS="qa-c-item-clear">',
				'</DIV>'
			);
		}

	}


/*
	Omit PHP closing tag to help avoid accidental output
*/