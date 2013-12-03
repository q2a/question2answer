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

		
		function output_split($parts, $class, $outertag='span', $innertag='span', $extraclass=null)
	/*
		Output the three elements ['prefix'], ['data'] and ['suffix'] of $parts (if they're defined),
		with appropriate CSS classes based on $class, using $outertag and $innertag in the markup.
	*/
		{
			if (empty($parts) && (strtolower($outertag)!='td'))
				return;
				
			$this->output(
				'<'.$outertag.' class="'.$class.(isset($extraclass) ? (' '.$extraclass) : '').'">',
				(strlen(@$parts['prefix']) ? ('<'.$innertag.' class="'.$class.'-pad">'.$parts['prefix'].'</'.$innertag.'>') : '').
				(strlen(@$parts['data']) ? ('<'.$innertag.' class="'.$class.'-data">'.$parts['data'].'</'.$innertag.'>') : '').
				(strlen(@$parts['suffix']) ? ('<'.$innertag.' class="'.$class.'-pad">'.$parts['suffix'].'</'.$innertag.'>') : ''),
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

		
		function reorder_parts($parts, $beforekey=null, $reorderrelative=true)
	/*
		Reorder the parts of the page according to the $parts array which contains part keys in their new order. Call this
		before main_parts(). See the docs for qa_array_reorder() in qa-util-sort.php for the other parameters.
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';
			
			qa_array_reorder($this->content, $parts, $beforekey, $reorderrelative);
		}
		
		
		function widgets($region, $place)
	/*
		Output the widgets (as provided in $this->content['widgets']) for $region and $place
	*/
		{
			if (count(@$this->content['widgets'][$region][$place])) {
				$this->output('<div class="qa-widgets-'.$region.' qa-widgets-'.$region.'-'.$place.'">');
				
				foreach ($this->content['widgets'][$region][$place] as $module) {
					$this->output('<div class="qa-widget-'.$region.' qa-widget-'.$region.'-'.$place.'">');
					$module->output_widget($region, $place, $this, $this->template, $this->request, $this->content);
					$this->output('</div>');
				}
				
				$this->output('</div>', '');
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
					"2. Balance all paired tags like <td>...</td> or <div>...</div>.\n".
					"3. Use a slash at the end of unpaired tags like <img/> or <input/>.\n".
					"Thanks!\n-->\n";
		}

		
	//	From here on, we have a large number of class methods which output particular pieces of HTML markup
	//	The calling chain is initiated from qa-page.php, or qa-ajax-*.php for refreshing parts of a page, 
	//	For most HTML elements, the name of the function is similar to the element's CSS class, for example:
	//	search() outputs <div class="qa-search">, q_list() outputs <div class="qa-q-list">, etc...

		function doctype()
		{
			$this->output('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">');
		}
		
		function html()
		{
			$this->output(
				'<html>',
				'<!-- Powered by Question2Answer - http://www.question2answer.org/ -->'
			);
			
			$this->head();
			$this->body();
			
			$this->output(
				'<!-- Powered by Question2Answer - http://www.question2answer.org/ -->',
				'</html>'
			);
		}
		
		function head()
		{
			$this->output(
				'<head>',
				'<meta http-equiv="content-type" content="'.$this->content['content_type'].'"/>'
			);
			
			$this->head_title();
			$this->head_metas();
			$this->head_css();
			$this->head_links();
			$this->head_lines();
			$this->head_script();
			$this->head_custom();
			
			$this->output('</head>');
		}
		
		function head_title()
		{
			$pagetitle=strlen($this->request) ? strip_tags(@$this->content['title']) : '';
			$headtitle=(strlen($pagetitle) ? ($pagetitle.' - ') : '').$this->content['site_title'];
			
			$this->output('<title>'.$headtitle.'</title>');
		}
		
		function head_metas()
		{
			if (strlen(@$this->content['description']))
				$this->output('<meta name="description" content="'.$this->content['description'].'"/>');
			
			if (strlen(@$this->content['keywords'])) // as far as I know, meta keywords have zero effect on search rankings or listings
				$this->output('<meta name="keywords" content="'.$this->content['keywords'].'"/>');
		}
		
		function head_links()
		{
			if (isset($this->content['canonical']))
				$this->output('<link rel="canonical" href="'.$this->content['canonical'].'"/>');
				
			if (isset($this->content['feed']['url']))
				$this->output('<link rel="alternate" type="application/rss+xml" href="'.$this->content['feed']['url'].'" title="'.@$this->content['feed']['label'].'"/>');
				
			if (isset($this->content['page_links']['items'])) // convert page links to rel=prev and rel=next tags
				foreach ($this->content['page_links']['items'] as $page_link)
					if ($page_link['type']=='prev')
						$this->output('<link rel="prev" href="'.$page_link['url'].'"/>');
					elseif ($page_link['type']=='next')
						$this->output('<link rel="next" href="'.$page_link['url'].'"/>');
		}
		
		function head_script()
		{
			if (isset($this->content['script']))
				foreach ($this->content['script'] as $scriptline)
					$this->output_raw($scriptline);
		}
		
		function head_css()
		{
			$this->output('<link rel="stylesheet" type="text/css" href="'.$this->rooturl.$this->css_name().'"/>');
			
			if (isset($this->content['css_src']))
				foreach ($this->content['css_src'] as $css_src)
					$this->output('<link rel="stylesheet" type="text/css" href="'.$css_src.'"/>');
					
			if (!empty($this->content['notices']))
				$this->output(
					'<style><!--',
					'.qa-body-js-on .qa-notice {display:none;}',
					'//--></style>'
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
			$this->output('<body');
			$this->body_tags();
			$this->output('>');
			
			$this->body_script();
			$this->body_header();
			$this->body_content();
			$this->body_footer();
			$this->body_hidden();
				
			$this->output('</body>');
		}
		
		function body_hidden()
		{
			$this->output('<div style="position:absolute; left:-9999px; top:-9999px;">');
			$this->waiting_template();
			$this->output('</div>');
		}
		
		function waiting_template()
		{
			$this->output('<span id="qa-waiting-template" class="qa-waiting">...</span>');
		}
		
		function body_script()
		{
			$this->output(
				'<script type="text/javascript">',
				"var b=document.getElementsByTagName('body')[0];",
				"b.className=b.className.replace('qa-body-js-off', 'qa-body-js-on');",
				'</script>'
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
			
			$this->output('<div class="qa-body-wrapper">', '');

			$this->widgets('full', 'top');
			$this->header();
			$this->widgets('full', 'high');
			$this->sidepanel();
			$this->main();
			$this->widgets('full', 'low');
			$this->footer();
			$this->widgets('full', 'bottom');
			
			$this->output('</div> <!-- END body-wrapper -->');
			
			$this->body_suffix();
		}
		
		function body_tags()
		{
			$class='qa-template-'.qa_html($this->template);
			
			if (isset($this->content['categoryids']))
				foreach ($this->content['categoryids'] as $categoryid)
					$class.=' qa-category-'.qa_html($categoryid);
			
			$this->output('class="'.$class.' qa-body-js-off"');
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
			$this->output('<div class="qa-notice" id="'.$notice['id'].'">');
			
			if (isset($notice['form_tags']))
				$this->output('<form '.$notice['form_tags'].'>');
			
			$this->output_raw($notice['content']);
			
			$this->output('<input '.$notice['close_tags'].' type="submit" value="X" class="qa-notice-close-button"/> ');
			
			if (isset($notice['form_tags'])) {
				$this->form_hidden_elements(@$notice['form_hidden']);
				$this->output('</form>');
			}
			
			$this->output('</div>');
		}		
		
		function header()
		{
			$this->output('<div class="qa-header">');
			
			$this->logo();
			$this->nav_user_search();
			$this->nav_main_sub();
			$this->header_clear();
			
			$this->output('</div> <!-- END qa-header -->', '');
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
				'<div class="qa-logo">',
				$this->content['logo'],
				'</div>'
			);
		}
		
		function search()
		{
			$search=$this->content['search'];
			
			$this->output(
				'<div class="qa-search">',
				'<form '.$search['form_tags'].'>',
				@$search['form_extra']
			);
			
			$this->search_field($search);
			$this->search_button($search);
			
			$this->output(
				'</form>',
				'</div>'
			);
		}
		
		function search_field($search)
		{
			$this->output('<input type="text" '.$search['field_tags'].' value="'.@$search['value'].'" class="qa-search-field"/>');
		}
		
		function search_button($search)
		{
			$this->output('<input type="submit" value="'.$search['button_label'].'" class="qa-search-button"/>');
		}
		
		function nav($navtype, $level=null)
		{
			$navigation=@$this->content['navigation'][$navtype];
			
			if (($navtype=='user') || isset($navigation)) {
				$this->output('<div class="qa-nav-'.$navtype.'">');
				
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
	
				$this->output('</div>');
			}
		}
		
		function nav_list($navigation, $class, $level=null)
		{
			$this->output('<ul class="qa-'.$class.'-list'.(isset($level) ? (' qa-'.$class.'-list-'.$level) : '').'">');

			$index=0;
			
			foreach ($navigation as $key => $navlink) {
				$this->set_context('nav_key', $key);
				$this->set_context('nav_index', $index++);
				$this->nav_item($key, $navlink, $class, $level);
			}

			$this->clear_context('nav_key');
			$this->clear_context('nav_index');
			
			$this->output('</ul>');
		}
		
		function nav_clear($navtype)
		{
			$this->output(
				'<div class="qa-nav-'.$navtype.'-clear">',
				'</div>'
			);
		}
		
		function nav_item($key, $navlink, $class, $level=null)
		{
			$suffix=strtr($key, array( // map special character in navigation key
				'$' => '',
				'/' => '-',
			));
			
			$this->output('<li class="qa-'.$class.'-item'.(@$navlink['opposite'] ? '-opp' : '').
				(@$navlink['state'] ? (' qa-'.$class.'-'.$navlink['state']) : '').' qa-'.$class.'-'.$suffix.'">');
			$this->nav_link($navlink, $class);
			
			if (count(@$navlink['subnav']))
				$this->nav_list($navlink['subnav'], $class, 1+$level);
			
			$this->output('</li>');
		}
		
		function nav_link($navlink, $class)
		{
			if (isset($navlink['url']))
				$this->output(
					'<a href="'.$navlink['url'].'" class="qa-'.$class.'-link'.
					(@$navlink['selected'] ? (' qa-'.$class.'-selected') : '').
					(@$navlink['favorited'] ? (' qa-'.$class.'-favorited') : '').
					'"'.(strlen(@$navlink['popup']) ? (' title="'.$navlink['popup'].'"') : '').
					(isset($navlink['target']) ? (' target="'.$navlink['target'].'"') : '').'>'.$navlink['label'].
					'</a>'
				);

			else
				$this->output(
					'<span class="qa-'.$class.'-nolink'.(@$navlink['selected'] ? (' qa-'.$class.'-selected') : '').
					(@$navlink['favorited'] ? (' qa-'.$class.'-favorited') : '').'"'.
					(strlen(@$navlink['popup']) ? (' title="'.$navlink['popup'].'"') : '').
					'>'.$navlink['label'].'</span>'
				);

			if (strlen(@$navlink['note']))
				$this->output('<span class="qa-'.$class.'-note">'.$navlink['note'].'</span>');
		}
		
		function logged_in()
		{
			$this->output_split(@$this->content['loggedin'], 'qa-logged-in', 'div');
		}
		
		function header_clear()
		{
			$this->output(
				'<div class="qa-header-clear">',
				'</div>'
			);
		}
		
		function sidepanel()
		{
			$this->output('<div class="qa-sidepanel">');
			$this->widgets('side', 'top');
			$this->sidebar();
			$this->widgets('side', 'high');
			$this->nav('cat', 1);
			$this->widgets('side', 'low');
			$this->output_raw(@$this->content['sidepanel']);
			$this->feed();
			$this->widgets('side', 'bottom');
			$this->output('</div>', '');
		}
		
		function sidebar()
		{
			$sidebar=@$this->content['sidebar'];
			
			if (!empty($sidebar)) {
				$this->output('<div class="qa-sidebar">');
				$this->output_raw($sidebar);
				$this->output('</div>', '');
			}
		}
		
		function feed()
		{
			$feed=@$this->content['feed'];
			
			if (!empty($feed)) {
				$this->output('<div class="qa-feed">');
				$this->output('<a href="'.$feed['url'].'" class="qa-feed-link">'.@$feed['label'].'</a>');
				$this->output('</div>');
			}
		}
		
		function main()
		{
			$content=$this->content;

			$this->output('<div class="qa-main'.(@$this->content['hidden'] ? ' qa-main-hidden' : '').'">');
			
			$this->widgets('main', 'top');
			
			$this->page_title_error();		
			
			$this->widgets('main', 'high');

			/*if (isset($content['main_form_tags']))
				$this->output('<form '.$content['main_form_tags'].'>');*/
				
			$this->main_parts($content);
		
			/*if (isset($content['main_form_tags']))
				$this->output('</form>');*/
				
			$this->widgets('main', 'low');

			$this->page_links();
			$this->suggest_next();
			
			$this->widgets('main', 'bottom');

			$this->output('</div> <!-- END qa-main -->', '');
		}
		
		function page_title_error()
		{
			$favorite=@$this->content['favorite'];
			
			if (isset($favorite))
				$this->output('<form '.$favorite['form_tags'].'>');
				
			$this->output('<h1>');
			$this->favorite();
			$this->title();
			$this->output('</h1>');

			if (isset($this->content['error']))
				$this->error(@$this->content['error']);

			if (isset($favorite)) {
				$this->form_hidden_elements(@$favorite['form_hidden']);
				$this->output('</form>');
			}
		}
		
		function favorite()
		{
			$favorite=@$this->content['favorite'];
			
			if (isset($favorite)) {
				$this->output('<span class="qa-favoriting" '.@$favorite['favorite_tags'].'>');
				$this->favorite_inner_html($favorite);
				$this->output('</span>');
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
				$this->output('<input '.$tags.' type="submit" value="" class="'.$class.'-button"/> ');
		}
		
		function error($error)
		{
			if (strlen($error))
				$this->output(
					'<div class="qa-error">',
					$error,
					'</div>'
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
			$partdiv=(
				(strpos($key, 'custom')===0) ||
				(strpos($key, 'form')===0) ||
				(strpos($key, 'q_list')===0) ||
				(strpos($key, 'q_view')===0) ||
				(strpos($key, 'a_form')===0) ||
				(strpos($key, 'a_list')===0) ||
				(strpos($key, 'ranking')===0) ||
				(strpos($key, 'message_list')===0) ||
				(strpos($key, 'nav_list')===0)
			);
				
			if ($partdiv)
				$this->output('<div class="qa-part-'.strtr($key, '_', '-').'">'); // to help target CSS to page parts

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
				
			elseif (strpos($key, 'message_list')===0)
				$this->message_list_and_form($part);
				
			elseif (strpos($key, 'nav_list')===0) {
				$this->part_title($part);		
				$this->nav_list($part['nav'], $part['type'], 1);
			}

			if ($partdiv)
				$this->output('</div>');
		}
		
		function footer()
		{
			$this->output('<div class="qa-footer">');
			
			$this->nav('footer');
			$this->attribution();
			$this->footer_clear();
			
			$this->output('</div> <!-- END qa-footer -->', '');
		}
		
		function attribution()
		{
			// Hi there. I'd really appreciate you displaying this link on your Q2A site. Thank you - Gideon
				
			$this->output(
				'<div class="qa-attribution">',
				'Powered by <a href="http://www.question2answer.org/">Question2Answer</a>',
				'</div>'
			);
		}
		
		function footer_clear()
		{
			$this->output(
				'<div class="qa-footer-clear">',
				'</div>'
			);
		}

		function section($title)
		{
			$this->part_title(array('title' => $title));
		}
		
		function part_title($part)
		{
			if (strlen(@$part['title']) || strlen(@$part['title_tags']))
				$this->output('<h2'.rtrim(' '.@$part['title_tags']).'>'.@$part['title'].'</h2>');
		}
		
		function form($form)
		{
			if (!empty($form)) {
				$this->part_title($form);
				
				if (isset($form['tags']))
					$this->output('<form '.$form['tags'].'>');
				
				$this->form_body($form);
	
				if (isset($form['tags']))
					$this->output('</form>');
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
				'<tr>',
				'<td colspan="'.$columns.'" class="qa-form-'.$form['style'].'-spacer">',
				'&nbsp;',
				'</td>',
				'</tr>'
			);
		}
		
		function form_body($form)
		{
			if (@$form['boxed'])
				$this->output('<div class="qa-form-table-boxed">');
			
			$columns=$this->form_columns($form);
			
			if ($columns)
				$this->output('<table class="qa-form-'.$form['style'].'-table">');
			
			$this->form_ok($form, $columns);
			$this->form_fields($form, $columns);
			$this->form_buttons($form, $columns);

			if ($columns)
				$this->output('</table>');

			$this->form_hidden($form);

			if (@$form['boxed'])
				$this->output('</div>');
		}
		
		function form_ok($form, $columns)
		{
			if (!empty($form['ok']))
				$this->output(
					'<tr>',
					'<td colspan="'.$columns.'" class="qa-form-'.$form['style'].'-ok">',
					$form['ok'],
					'</td>',
					'</tr>'
				);
		}
		
		function form_reorder_fields(&$form, $keys, $beforekey=null, $reorderrelative=true)
	/*
		Reorder the fields of $form according to the $keys array which contains the field keys in their new order. Call
		before any fields are output. See the docs for qa_array_reorder() in qa-util-sort.php for the other parameters.
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';
			
			if (is_array($form['fields']))
				qa_array_reorder($form['fields'], $keys, $beforekey, $reorderrelative);
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
				$this->output('<tbody id="'.$field['id'].'">', '<tr>');
			elseif (isset($field['id']))
				$this->output('<tr id="'.$field['id'].'">');
			else
				$this->output('<tr>');
			
			if (($columns>1) || !empty($field['label']))
				$this->form_label($field, $style, $columns, $prefixed, $suffixed, $colspan);
			
			if ($tworows)
				$this->output(
					'</tr>',
					'<tr>'
				);
			
			if (!$skipdata)
				$this->form_data($field, $style, $columns, !($prefixed||$suffixed), $colspan);
			
			$this->output('</tr>');
			
			if (($columns==1) && isset($field['id']))
				$this->output('</tbody>');
		}
		
		function form_label($field, $style, $columns, $prefixed, $suffixed, $colspan)
		{
			$extratags='';
			
			if ( ($columns>1) && ((@$field['type']=='select-radio') || (@$field['rows']>1)) )
				$extratags.=' style="vertical-align:top;"';
				
			if (isset($colspan))
				$extratags.=' colspan="'.$colspan.'"';
			
			$this->output('<td class="qa-form-'.$style.'-label"'.$extratags.'>');
			
			if ($prefixed) {
				$this->output('<label>');
				$this->form_field($field, $style);
			}
					
			$this->output(@$field['label']);
			
			if ($prefixed)
				$this->output('</label>');

			if ($suffixed) {
				$this->output('&nbsp;');
				$this->form_field($field, $style);
			}
			
			$this->output('</td>');
		}
		
		function form_data($field, $style, $columns, $showfield, $colspan)
		{
			if ($showfield || (!empty($field['error'])) || (!empty($field['note']))) {
				$this->output(
					'<td class="qa-form-'.$style.'-data"'.(isset($colspan) ? (' colspan="'.$colspan.'"') : '').'>'
				);
							
				if ($showfield)
					$this->form_field($field, $style);
	
				if (!empty($field['error'])) {
					if (@$field['note_force'])
						$this->form_note($field, $style, $columns);
						
					$this->form_error($field, $style, $columns);
				
				} elseif (!empty($field['note']))
					$this->form_note($field, $style, $columns);
				
				$this->output('</td>');
			}
		}
		
		function form_field($field, $style)
		{
			$this->form_prefix($field, $style);
			
			$this->output_raw(@$field['html_prefix']);

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
					$this->output_raw(@$field['html']);
					break;
				
				default:
					if ((@$field['type']=='textarea') || (@$field['rows']>1))
						$this->form_text_multi_row($field, $style);
					else
						$this->form_text_single_row($field, $style);
					break;
			}	

			$this->output_raw(@$field['html_suffix']);

			$this->form_suffix($field, $style);
		}
		
		function form_reorder_buttons(&$form, $keys, $beforekey=null, $reorderrelative=true)
	/*
		Reorder the buttons of $form according to the $keys array which contains the button keys in their new order. Call
		before any buttons are output. See the docs for qa_array_reorder() in qa-util-sort.php for the other parameters.
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-util-sort.php';
			
			if (is_array($form['buttons']))
				qa_array_reorder($form['buttons'], $keys, $beforekey, $reorderrelative);
		}

		function form_buttons($form, $columns)
		{
			if (!empty($form['buttons'])) {
				$style=@$form['style'];
				
				if ($columns)
					$this->output(
						'<tr>',
						'<td colspan="'.$columns.'" class="qa-form-'.$style.'-buttons">'
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
						'</td>',
						'</tr>'
					);
			}
		}
		
		function form_button_data($button, $key, $style)
		{
			$baseclass='qa-form-'.$style.'-button qa-form-'.$style.'-button-'.$key;
			
			$this->output('<input'.rtrim(' '.@$button['tags']).' value="'.@$button['label'].'" title="'.@$button['popup'].'" type="submit"'.
				(isset($style) ? (' class="'.$baseclass.'"') : '').'/>');
		}
		
		function form_button_note($button, $style)
		{
			if (!empty($button['note']))
				$this->output(
					'<span class="qa-form-'.$style.'-note">',
					$button['note'],
					'</span>',
					'<br/>'
				);
		}
		
		function form_button_spacer($style)
		{
			$this->output('<span class="qa-form-'.$style.'-buttons-spacer">&nbsp;</span>');
		}
		
		function form_hidden($form)
		{
			$this->form_hidden_elements(@$form['hidden']);
		}
		
		function form_hidden_elements($hidden)
		{
			if (!empty($hidden))
				foreach ($hidden as $name => $value)
					$this->output('<input type="hidden" name="'.$name.'" value="'.$value.'"/>');
		}
		
		function form_prefix($field, $style)
		{
			if (!empty($field['prefix']))
				$this->output('<span class="qa-form-'.$style.'-prefix">'.$field['prefix'].'</span>');
		}
		
		function form_suffix($field, $style)
		{
			if (!empty($field['suffix']))
				$this->output('<span class="qa-form-'.$style.'-suffix">'.$field['suffix'].'</span>');
		}
		
		function form_checkbox($field, $style)
		{
			$this->output('<input '.@$field['tags'].' type="checkbox" value="1"'.(@$field['value'] ? ' checked' : '').' class="qa-form-'.$style.'-checkbox"/>');
		}
		
		function form_static($field, $style)
		{
			$this->output('<span class="qa-form-'.$style.'-static">'.@$field['value'].'</span>');
		}
		
		function form_password($field, $style)
		{
			$this->output('<input '.@$field['tags'].' type="password" value="'.@$field['value'].'" class="qa-form-'.$style.'-text"/>');
		}
		
		function form_number($field, $style)
		{
			$this->output('<input '.@$field['tags'].' type="text" value="'.@$field['value'].'" class="qa-form-'.$style.'-number"/>');
		}
		
		function form_select($field, $style)
		{
			$this->output('<select '.@$field['tags'].' class="qa-form-'.$style.'-select">');
			
			foreach ($field['options'] as $tag => $value)
				$this->output('<option value="'.$tag.'"'.(($value==@$field['value']) ? ' selected' : '').'>'.$value.'</option>');
			
			$this->output('</select>');
		}
		
		function form_select_radio($field, $style)
		{
			$radios=0;
			
			foreach ($field['options'] as $tag => $value) {
				if ($radios++)
					$this->output('<br/>');
					
				$this->output('<input '.@$field['tags'].' type="radio" value="'.$tag.'"'.(($value==@$field['value']) ? ' checked' : '').' class="qa-form-'.$style.'-radio"/> '.$value);
			}
		}
		
		function form_image($field, $style)
		{
			$this->output('<div class="qa-form-'.$style.'-image">'.@$field['html'].'</div>');
		}
		
		function form_text_single_row($field, $style)
		{
			$this->output('<input '.@$field['tags'].' type="text" value="'.@$field['value'].'" class="qa-form-'.$style.'-text"/>');
		}
		
		function form_text_multi_row($field, $style)
		{
			$this->output('<textarea '.@$field['tags'].' rows="'.(int)$field['rows'].'" cols="40" class="qa-form-'.$style.'-text">'.@$field['value'].'</textarea>');
		}
		
		function form_error($field, $style, $columns)
		{
			$tag=($columns>1) ? 'span' : 'div';
			
			$this->output('<'.$tag.' class="qa-form-'.$style.'-error">'.$field['error'].'</'.$tag.'>');
		}
		
		function form_note($field, $style, $columns)
		{
			$tag=($columns>1) ? 'span' : 'div';
			
			$this->output('<'.$tag.' class="qa-form-'.$style.'-note">'.@$field['note'].'</'.$tag.'>');
		}
		
		function ranking($ranking)
		{
			$this->part_title($ranking);
			
			$class=(@$ranking['type']=='users') ? 'qa-top-users' : 'qa-top-tags';
			
			$rows=min($ranking['rows'], count($ranking['items']));
			
			if ($rows>0) {
				$this->output('<table class="'.$class.'-table">');
			
				$columns=ceil(count($ranking['items'])/$rows);
				
				for ($row=0; $row<$rows; $row++) {
					$this->set_context('ranking_row', $row);
					$this->output('<tr>');
		
					for ($column=0; $column<$columns; $column++) {
						$this->set_context('ranking_column', $column);
						$this->ranking_item(@$ranking['items'][$column*$rows+$row], $class, $column>0);
					}

					$this->clear_context('ranking_column');
		
					$this->output('</tr>');
				}
			
				$this->clear_context('ranking_row');

				$this->output('</table>');
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
			$this->output('<td class="'.$class.'-spacer">&nbsp;</td>');
		}
		
		function ranking_count($item, $class)
		{
			$this->output('<td class="'.$class.'-count">'.$item['count'].' &#215;'.'</td>');
		}
		
		function ranking_label($item, $class)
		{
			$this->output('<td class="'.$class.'-label">'.$item['label'].'</td>');
		}
		
		function ranking_score($item, $class)
		{
			$this->output('<td class="'.$class.'-score">'.$item['score'].'</td>');
		}
		
		function message_list_and_form($list)
		{
			if (!empty($list)) {
				$this->part_title($list);
				
				$this->error(@$list['error']);

				if (!empty($list['form'])) {
					$this->output('<form '.$list['form']['tags'].'>');
					unset($list['form']['tags']); // we already output the tags before the messages
					$this->message_list_form($list);
				}
					
				$this->message_list($list);
				
				if (!empty($list['form'])) {
					$this->output('</form>');
				}
			}		
		}
		
		function message_list_form($list)
		{
			if (!empty($list['form'])) {
				$this->output('<div class="qa-message-list-form">');
				$this->form($list['form']);
				$this->output('</div>');
			}
		}
		
		function message_list($list)
		{
			if (isset($list['messages'])) {
				$this->output('<div class="qa-message-list" '.@$list['tags'].'>');
				
				foreach ($list['messages'] as $message)
					$this->message_item($message);
				
				$this->output('</div> <!-- END qa-message-list -->', '');
			}
		}
		
		function message_item($message)
		{
			$this->output('<div class="qa-message-item" '.@$message['tags'].'>');
			$this->message_content($message);
			$this->post_avatar_meta($message, 'qa-message');
			$this->message_buttons($message);
			$this->output('</div> <!-- END qa-message-item -->', '');
		}
		
		function message_content($message)
		{
			if (!empty($message['content'])) {
				$this->output('<div class="qa-message-content">');
				$this->output_raw($message['content']);
				$this->output('</div>');
			}
		}
		
		function message_buttons($item)
		{
			if (!empty($item['form'])) {
				$this->output('<div class="qa-message-buttons">');
				$this->form($item['form']);
				$this->output('</div>');
			}
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
				$this->part_title($q_list);
	
				if (!empty($q_list['form']))
					$this->output('<form '.$q_list['form']['tags'].'>');
				
				$this->q_list($q_list);
				
				if (!empty($q_list['form'])) {
					unset($q_list['form']['tags']); // we already output the tags before the qs
					$this->q_list_form($q_list);
					$this->output('</form>');
				}
			}
		}
		
		function q_list_form($q_list)
		{
			if (!empty($q_list['form'])) {
				$this->output('<div class="qa-q-list-form">');
				$this->form($q_list['form']);
				$this->output('</div>');
			}
		}
		
		function q_list($q_list)
		{
			if (isset($q_list['qs'])) {
				$this->output('<div class="qa-q-list'.($this->list_vote_disabled($q_list['qs']) ? ' qa-q-list-vote-disabled' : '').'">', '');
				$this->q_list_items($q_list['qs']);
				$this->output('</div> <!-- END qa-q-list -->', '');
			}
		}
		
		function q_list_items($q_items)
		{
			foreach ($q_items as $q_item)
				$this->q_list_item($q_item);
		}
		
		function q_list_item($q_item)
		{
			$this->output('<div class="qa-q-list-item'.rtrim(' '.@$q_item['classes']).'" '.@$q_item['tags'].'>');

			$this->q_item_stats($q_item);
			$this->q_item_main($q_item);
			$this->q_item_clear();

			$this->output('</div> <!-- END qa-q-list-item -->', '');
		}
		
		function q_item_stats($q_item)
		{
			$this->output('<div class="qa-q-item-stats">');
			
			$this->voting($q_item);
			$this->a_count($q_item);

			$this->output('</div>');
		}
		
		function q_item_main($q_item)
		{
			$this->output('<div class="qa-q-item-main">');
			
			$this->view_count($q_item);
			$this->q_item_title($q_item);
			$this->q_item_content($q_item);
			
			$this->post_avatar_meta($q_item, 'qa-q-item');
			$this->post_tags($q_item, 'qa-q-item');
			$this->q_item_buttons($q_item);
				
			$this->output('</div>');
		}
		
		function q_item_clear()
		{
			$this->output(
				'<div class="qa-q-item-clear">',
				'</div>'
			);
		}
		
		function q_item_title($q_item)
		{
			$this->output(
				'<div class="qa-q-item-title">',
				'<a href="'.$q_item['url'].'">'.$q_item['title'].'</a>',
				'</div>'
			);
		}
		
		function q_item_content($q_item)
		{
			if (!empty($q_item['content'])) {
				$this->output('<div class="qa-q-item-content">');
				$this->output_raw($q_item['content']);
				$this->output('</div>');
			}
		}
		
		function q_item_buttons($q_item)
		{
			if (!empty($q_item['form'])) {
				$this->output('<div class="qa-q-item-buttons">');
				$this->form($q_item['form']);
				$this->output('</div>');
			}
		}
		
		function voting($post)
		{
			if (isset($post['vote_view'])) {
				$this->output('<div class="qa-voting '.(($post['vote_view']=='updown') ? 'qa-voting-updown' : 'qa-voting-net').'" '.@$post['vote_tags'].'>');
				$this->voting_inner_html($post);
				$this->output('</div>');
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
			$this->output('<div class="qa-vote-buttons '.(($post['vote_view']=='updown') ? 'qa-vote-buttons-updown' : 'qa-vote-buttons-net').'">');

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

			$this->output('</div>');
		}
		
		function vote_count($post)
		{
			// You can also use $post['upvotes_raw'], $post['downvotes_raw'], $post['netvotes_raw'] to get
			// raw integer vote counts, for graphing or showing in other non-textual ways
			
			$this->output('<div class="qa-vote-count '.(($post['vote_view']=='updown') ? 'qa-vote-count-updown' : 'qa-vote-count-net').'"'.@$post['vote_count_tags'].'>');

			if ($post['vote_view']=='updown') {
				$this->output_split($post['upvotes_view'], 'qa-upvote-count');
				$this->output_split($post['downvotes_view'], 'qa-downvote-count');
			
			} else
				$this->output_split($post['netvotes_view'], 'qa-netvote-count');

			$this->output('</div>');
		}
		
		function vote_clear()
		{
			$this->output(
				'<div class="qa-vote-clear">',
				'</div>'
			);
		}
		
		function a_count($post)
		{
			// You can also use $post['answers_raw'] to get a raw integer count of answers
			
			$this->output_split(@$post['answers'], 'qa-a-count', 'span', 'span',
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
				$this->output('<span class="'.$class.'-avatar">', $post['avatar'], '</span>');
		}
		
		function a_selection($post)
		{
			$this->output('<div class="qa-a-selection">');
			
			if (isset($post['select_tags']))
				$this->post_hover_button($post, 'select_tags', '', 'qa-a-select');
			elseif (isset($post['unselect_tags']))
				$this->post_hover_button($post, 'unselect_tags', '', 'qa-a-unselect');
			elseif ($post['selected'])
				$this->output('<div class="qa-a-selected">&nbsp;</div>');
			
			if (isset($post['select_text']))
				$this->output('<div class="qa-a-selected-text">'.@$post['select_text'].'</div>');
			
			$this->output('</div>');
		}
		
		function post_hover_button($post, $element, $value, $class)
		{
			if (isset($post[$element]))
				$this->output('<input '.$post[$element].' type="submit" value="'.$value.'" class="'.$class.'-button"/> ');
		}
		
		function post_disabled_button($post, $element, $value, $class)
		{
			if (isset($post[$element]))
				$this->output('<input '.$post[$element].' type="submit" value="'.$value.'" class="'.$class.'-disabled" disabled="disabled"/> ');
		}
		
		function post_avatar_meta($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<br/>')
		{
			$this->output('<span class="'.$class.'-avatar-meta">');
			$this->post_avatar($post, $class, $avatarprefix);
			$this->post_meta($post, $class, $metaprefix, $metaseparator);
			$this->output('</span>');
		}
		
		function post_avatar($post, $class, $prefix=null)
		{
			if (isset($post['avatar'])) {
				if (isset($prefix))
					$this->output($prefix);

				$this->output(
					'<span class="'.$class.'-avatar">',
					$post['avatar'],
					'</span>'
				);
			}
		}
		
		function post_meta($post, $class, $prefix=null, $separator='<br/>')
		{
			$this->output('<span class="'.$class.'-meta">');
			
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
							$this->output('<span class="'.$class.'-what">'.$post['what_2'].'</span>');
							break;
						
						case 'when':
							$this->output_split(@$post['when_2'], $class.'-when');
							break;
						
						case 'who':
							$this->output_split(@$post['who_2'], $class.'-who');
							break;
					}
			}
			
			$this->output('</span>');
		}
		
		function post_meta_what($post, $class)
		{
			if (isset($post['what'])) {
				$classes=$class.'-what';
				if (@$post['what_your'])
					$classes.=' '.$class.'-what-your';
				
				if (isset($post['what_url']))
					$this->output('<a href="'.$post['what_url'].'" class="'.$classes.'">'.$post['what'].'</a>');
				else
					$this->output('<span class="'.$classes.'">'.$post['what'].'</span>');
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
				$this->output('<span class="'.$class.'-who">');
				
				if (strlen(@$post['who']['prefix']))
					$this->output('<span class="'.$class.'-who-pad">'.$post['who']['prefix'].'</span>');
				
				if (isset($post['who']['data']))
					$this->output('<span class="'.$class.'-who-data">'.$post['who']['data'].'</span>');
				
				if (isset($post['who']['title']))
					$this->output('<span class="'.$class.'-who-title">'.$post['who']['title'].'</span>');
					
				// You can also use $post['level'] to get the author's privilege level (as a string)
	
				if (isset($post['who']['points'])) {
					$post['who']['points']['prefix']='('.$post['who']['points']['prefix'];
					$post['who']['points']['suffix'].=')';
					$this->output_split($post['who']['points'], $class.'-who-points');
				}
				
				if (strlen(@$post['who']['suffix']))
					$this->output('<span class="'.$class.'-who-pad">'.$post['who']['suffix'].'</span>');
	
				$this->output('</span>');
			}
		}
		
		function post_meta_flags($post, $class)
		{
			$this->output_split(@$post['flags'], $class.'-flags');
		}
		
		function post_tags($post, $class)
		{
			if (!empty($post['q_tags'])) {
				$this->output('<div class="'.$class.'-tags">');
				$this->post_tag_list($post, $class);
				$this->output('</div>');
			}
		}
		
		function post_tag_list($post, $class)
		{
			$this->output('<ul class="'.$class.'-tag-list">');
			
			foreach ($post['q_tags'] as $taghtml)
				$this->post_tag_item($taghtml, $class);
				
			$this->output('</ul>');
		}
		
		function post_tag_item($taghtml, $class)
		{
			$this->output('<li class="'.$class.'-tag-item">'.$taghtml.'</li>');
		}
	
		function page_links()
		{
			$page_links=@$this->content['page_links'];
			
			if (!empty($page_links)) {
				$this->output('<div class="qa-page-links">');
				
				$this->page_links_label(@$page_links['label']);
				$this->page_links_list(@$page_links['items']);
				$this->page_links_clear();
				
				$this->output('</div>');
			}
		}
		
		function page_links_label($label)
		{
			if (!empty($label))
				$this->output('<span class="qa-page-links-label">'.$label.'</span>');
		}
		
		function page_links_list($page_items)
		{
			if (!empty($page_items)) {
				$this->output('<ul class="qa-page-links-list">');
				
				$index=0;
				
				foreach ($page_items as $page_link) {
					$this->set_context('page_index', $index++);
					$this->page_links_item($page_link);
					
					if ($page_link['ellipsis'])
						$this->page_links_item(array('type' => 'ellipsis'));
				}
				
				$this->clear_context('page_index');
				
				$this->output('</ul>');
			}
		}
		
		function page_links_item($page_link)
		{
			$this->output('<li class="qa-page-links-item">');
			$this->page_link_content($page_link);
			$this->output('</li>');
		}
		
		function page_link_content($page_link)
		{
			$label=@$page_link['label'];
			$url=@$page_link['url'];
			
			switch ($page_link['type']) {
				case 'this':
					$this->output('<span class="qa-page-selected">'.$label.'</span>');
					break;
				
				case 'prev':
					$this->output('<a href="'.$url.'" class="qa-page-prev">&laquo; '.$label.'</a>');
					break;
				
				case 'next':
					$this->output('<a href="'.$url.'" class="qa-page-next">'.$label.' &raquo;</a>');
					break;
				
				case 'ellipsis':
					$this->output('<span class="qa-page-ellipsis">...</span>');
					break;
				
				default:
					$this->output('<a href="'.$url.'" class="qa-page-link">'.$label.'</a>');
					break;
			}
		}
		
		function page_links_clear()
		{
			$this->output(
				'<div class="qa-page-links-clear">',
				'</div>'
			);
		}

		function suggest_next()
		{
			$suggest=@$this->content['suggest_next'];
			
			if (!empty($suggest)) {
				$this->output('<div class="qa-suggest-next">');
				$this->output($suggest);
				$this->output('</div>');
			}
		}
		
		function q_view($q_view)
		{
			if (!empty($q_view)) {
				$this->output('<div class="qa-q-view'.(@$q_view['hidden'] ? ' qa-q-view-hidden' : '').rtrim(' '.@$q_view['classes']).'"'.rtrim(' '.@$q_view['tags']).'>');
				
				if (isset($q_view['main_form_tags']))
					$this->output('<form '.$q_view['main_form_tags'].'>'); // form for voting buttons
				
				$this->q_view_stats($q_view);
				
				if (isset($q_view['main_form_tags'])) {
					$this->form_hidden_elements(@$q_view['voting_form_hidden']);
					$this->output('</form>');
				}
					
				$this->q_view_main($q_view);
				$this->q_view_clear();
				
				$this->output('</div> <!-- END qa-q-view -->', '');
			}
		}
		
		function q_view_stats($q_view)
		{
			$this->output('<div class="qa-q-view-stats">');
			
			$this->voting($q_view);
			$this->a_count($q_view);
			
			$this->output('</div>');
		}
		
		function q_view_main($q_view)
		{
			$this->output('<div class="qa-q-view-main">');

			if (isset($q_view['main_form_tags']))
				$this->output('<form '.$q_view['main_form_tags'].'>'); // form for buttons on question

			$this->view_count($q_view);
			$this->q_view_content($q_view);
			$this->q_view_extra($q_view);
			$this->q_view_follows($q_view);
			$this->q_view_closed($q_view);
			$this->post_tags($q_view, 'qa-q-view');
			$this->post_avatar_meta($q_view, 'qa-q-view');
			$this->q_view_buttons($q_view);
			$this->c_list(@$q_view['c_list'], 'qa-q-view');
			
			if (isset($q_view['main_form_tags'])) {
				$this->form_hidden_elements(@$q_view['buttons_form_hidden']);
				$this->output('</form>');
			}
			
			$this->c_form(@$q_view['c_form']);
			
			$this->output('</div> <!-- END qa-q-view-main -->');
		}
		
		function q_view_content($q_view)
		{
			if (!empty($q_view['content'])) {
				$this->output('<div class="qa-q-view-content">');
				$this->output_raw($q_view['content']);
				$this->output('</div>');
			}
		}
		
		function q_view_follows($q_view)
		{
			if (!empty($q_view['follows']))
				$this->output(
					'<div class="qa-q-view-follows">',
					$q_view['follows']['label'],
					'<a href="'.$q_view['follows']['url'].'" class="qa-q-view-follows-link">'.$q_view['follows']['title'].'</a>',
					'</div>'
				);
		}
		
		function q_view_closed($q_view)
		{
			if (!empty($q_view['closed'])) {
				$haslink=isset($q_view['closed']['url']);
				
				$this->output(
					'<div class="qa-q-view-closed">',
					$q_view['closed']['label'],
					($haslink ? ('<a href="'.$q_view['closed']['url'].'"') : '<span').' class="qa-q-view-closed-content">',
					$q_view['closed']['content'],
					$haslink ? '</a>' : '</span>',
					'</div>'
				);
			}
		}
		
		function q_view_extra($q_view)
		{
			if (!empty($q_view['extra']))
				$this->output(
					'<div class="qa-q-view-extra">',
					$q_view['extra']['label'],
					'<span class="qa-q-view-extra-content">',
					$q_view['extra']['content'],
					'</span>',
					'</div>'
				);
		}
		
		function q_view_buttons($q_view)
		{
			if (!empty($q_view['form'])) {
				$this->output('<div class="qa-q-view-buttons">');
				$this->form($q_view['form']);
				$this->output('</div>');
			}
		}
		
		function q_view_clear()
		{
			$this->output(
				'<div class="qa-q-view-clear">',
				'</div>'
			);
		}
		
		function a_form($a_form)
		{
			$this->output('<div class="qa-a-form"'.(isset($a_form['id']) ? (' id="'.$a_form['id'].'"') : '').
				(@$a_form['collapse'] ? ' style="display:none;"' : '').'>');

			$this->form($a_form);
			$this->c_list(@$a_form['c_list'], 'qa-a-item');
			
			$this->output('</div> <!-- END qa-a-form -->', '');
		}
		
		function a_list($a_list)
		{
			if (!empty($a_list)) {
				$this->part_title($a_list);
				
				$this->output('<div class="qa-a-list'.($this->list_vote_disabled($a_list['as']) ? ' qa-a-list-vote-disabled' : '').'" '.@$a_list['tags'].'>', '');
				$this->a_list_items($a_list['as']);
				$this->output('</div> <!-- END qa-a-list -->', '');
			}
		}
		
		function a_list_items($a_items)
		{
			foreach ($a_items as $a_item)
				$this->a_list_item($a_item);
		}
		
		function a_list_item($a_item)
		{
			$extraclass=@$a_item['classes'].($a_item['hidden'] ? ' qa-a-list-item-hidden' : ($a_item['selected'] ? ' qa-a-list-item-selected' : ''));
			
			$this->output('<div class="qa-a-list-item '.$extraclass.'" '.@$a_item['tags'].'>');
			
			if (isset($a_item['main_form_tags']))
				$this->output('<form '.$a_item['main_form_tags'].'>'); // form for voting buttons
			
			$this->voting($a_item);
			
			if (isset($a_item['main_form_tags'])) {
				$this->form_hidden_elements(@$a_item['voting_form_hidden']);
				$this->output('</form>');
			}
			
			$this->a_item_main($a_item);
			$this->a_item_clear();

			$this->output('</div> <!-- END qa-a-list-item -->', '');
		}
		
		function a_item_main($a_item)
		{
			$this->output('<div class="qa-a-item-main">');
			
			if (isset($a_item['main_form_tags']))
				$this->output('<form '.$a_item['main_form_tags'].'>'); // form for buttons on answer

			if ($a_item['hidden'])
				$this->output('<div class="qa-a-item-hidden">');
			elseif ($a_item['selected'])
				$this->output('<div class="qa-a-item-selected">');

			$this->a_selection($a_item);
			$this->error(@$a_item['error']);
			$this->a_item_content($a_item);
			$this->post_avatar_meta($a_item, 'qa-a-item');
			
			if ($a_item['hidden'] || $a_item['selected'])
				$this->output('</div>');
			
			$this->a_item_buttons($a_item);
			
			$this->c_list(@$a_item['c_list'], 'qa-a-item');

			if (isset($a_item['main_form_tags'])) {
				$this->form_hidden_elements(@$a_item['buttons_form_hidden']);
				$this->output('</form>');
			}

			$this->c_form(@$a_item['c_form']);

			$this->output('</div> <!-- END qa-a-item-main -->');
		}
		
		function a_item_clear()
		{
			$this->output(
				'<div class="qa-a-item-clear">',
				'</div>'
			);
		}
		
		function a_item_content($a_item)
		{
			$this->output('<div class="qa-a-item-content">');
			$this->output_raw($a_item['content']);
			$this->output('</div>');
		}
		
		function a_item_buttons($a_item)
		{
			if (!empty($a_item['form'])) {
				$this->output('<div class="qa-a-item-buttons">');
				$this->form($a_item['form']);
				$this->output('</div>');
			}
		}
		
		function c_form($c_form)
		{
			$this->output('<div class="qa-c-form"'.(isset($c_form['id']) ? (' id="'.$c_form['id'].'"') : '').
				(@$c_form['collapse'] ? ' style="display:none;"' : '').'>');

			$this->form($c_form);
			
			$this->output('</div> <!-- END qa-c-form -->', '');
		}
		
		function c_list($c_list, $class)
		{
			if (!empty($c_list)) {
				$this->output('', '<div class="'.$class.'-c-list"'.(@$c_list['hidden'] ? ' style="display:none;"' : '').' '.@$c_list['tags'].'>');
				$this->c_list_items($c_list['cs']);
				$this->output('</div> <!-- END qa-c-list -->', '');
			}
		}
		
		function c_list_items($c_items)
		{
			foreach ($c_items as $c_item)
				$this->c_list_item($c_item);
		}
		
		function c_list_item($c_item)
		{
			$extraclass=@$c_item['classes'].(@$c_item['hidden'] ? ' qa-c-item-hidden' : '');
			
			$this->output('<div class="qa-c-list-item '.$extraclass.'" '.@$c_item['tags'].'>');

			$this->c_item_main($c_item);
			$this->c_item_clear();

			$this->output('</div> <!-- END qa-c-item -->');
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
			
			$this->output('<div class="qa-c-item-footer">');
			$this->post_avatar_meta($c_item, 'qa-c-item');
			$this->c_item_buttons($c_item);
			$this->output('</div>');
		}
		
		function c_item_link($c_item)
		{
			$this->output(
				'<a href="'.$c_item['url'].'" class="qa-c-item-link">'.$c_item['title'].'</a>'
			);
		}
		
		function c_item_expand($c_item)
		{
			$this->output(
				'<a href="'.$c_item['url'].'" '.$c_item['expand_tags'].' class="qa-c-item-expand">'.$c_item['title'].'</a>'
			);
		}

		function c_item_content($c_item)
		{
			$this->output('<div class="qa-c-item-content">');
			$this->output_raw($c_item['content']);
			$this->output('</div>');
		}
		
		function c_item_buttons($c_item)
		{
			if (!empty($c_item['form'])) {
				$this->output('<div class="qa-c-item-buttons">');
				$this->form($c_item['form']);
				$this->output('</div>');
			}
		}
		
		function c_item_clear()
		{
			$this->output(
				'<div class="qa-c-item-clear">',
				'</div>'
			);
		}

	}


/*
	Omit PHP closing tag to help avoid accidental output
*/