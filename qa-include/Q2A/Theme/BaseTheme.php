<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Util/Metadata.php
	Description: Some useful metadata handling stuff


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Soqa_html_theme_baseftware Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class Q2A_Theme_BaseTheme
{

	public $template;
	public $content;
	public $rooturl;
	public $isRTL; // (boolean) whether text direction is Right-To-Left

	protected $context = array();

	protected $htmlPrinter;
	protected $layerModules;

	// whether to use new block layout in rankings (true) or fall back to tables (false)
	protected $ranking_block_layout = false;


	public function __construct($template, &$content, $rooturl, $htmlPrinter, $layerModules)
/*
	Initialize the object and assign local variables
*/
	{
		$this->template = $template;
		$this->content = &$content;
		$this->rooturl = $rooturl;
		$this->htmlPrinter = $htmlPrinter;
		$this->layerModules = $layerModules;

		$this->isRTL = isset($content['direction']) && $content['direction'] === 'rtl';
	}

	public function execute_method_in_layers($method, $parameters) {
		foreach ($this->layerModules as $plugin) {
			if (method_exists($plugin, $method))
				qa_call_method($plugin, $method, $parameters);
		}
	}

	public function before_method($method, $parameters) {
		$this->execute_method_in_layers('before_' . $method, $parameters);
	}

	public function around_method($method, $parameters) {
		$this->execute_method_in_layers($method, $parameters);
	}

	public function after_method($method, $parameters) {
		$this->execute_method_in_layers('after_' . $method, $parameters);
	}

	/**
	 * Pre-output initialization. Immediately called after loading of the module. Content and template variables are
	 * already setup at this point. Useful to perform layer initialization in the earliest and safest stage possible
	 */
	public function initialize() { }

	public function finish()
/*
	Post-output cleanup. For now, check that the indenting ended right, and if not, output a warning in an HTML comment
*/
	{
		if ($this->htmlPrinter->isProperlyFormatted()) {
			echo "<!--\nIt's no big deal, but your HTML could not be indented properly. To fix, please:\n".
				"1. Use this->output() to output all HTML.\n".
				"2. Balance all paired tags like <td>...</td> or <div>...</div>.\n".
				"3. Use a slash at the end of unpaired tags like <img/> or <input/>.\n".
				"Thanks!\n-->\n";
		}
	}

	public function getHtmlPrinter()
	{
		return $this->htmlPrinter;
	}

	public function getTemplate()
	{
		return $this->template;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function widgets($region, $place)
/*
	Output the widgets (as provided in $this->content['widgets']) for $region and $place
*/
	{
		if (count(@$this->content['widgets'][$region][$place])) {
			$this->htmlPrinter->output('<div class="qa-widgets-'.$region.' qa-widgets-'.$region.'-'.$place.'">');

			foreach ($this->content['widgets'][$region][$place] as $module) {
				$this->htmlPrinter->output('<div class="qa-widget-'.$region.' qa-widget-'.$region.'-'.$place.'">');
				$module->output_widget($region, $place, $this, $this->template, $this->content);
				$this->htmlPrinter->output('</div>');
			}

			$this->htmlPrinter->output('</div>', '');
		}
	}

//	From here on, we have a large number of class methods which output particular pieces of HTML markup
//	The calling chain is initiated from qa-page.php, or qa-ajax-*.php for refreshing parts of a page,
//	For most HTML elements, the name of the function is similar to the element's CSS class, for example:
//	search() outputs <div class="qa-search">, q_list() outputs <div class="qa-q-list">, etc...

	public function doctype()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<!DOCTYPE html>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function html()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$attribution = '<!-- Powered by Question2Answer - http://www.question2answer.org/ -->';
		$extratags = isset($this->content['html_tags']) ? $this->content['html_tags'] : '';

		$this->htmlPrinter->output(
			'<html'.$extratags.'>',
			$attribution
		);

		$this->around_method(__FUNCTION__, $myArray);

		$this->head();
		$this->body();

		$this->htmlPrinter->output(
			$attribution,
			'</html>'
		);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function head()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(
			'<head>',
			'<meta charset="'.$this->content['charset'].'"/>'
		);

		$this->around_method(__FUNCTION__, $myArray);
		$this->head_title();
		$this->head_metas();
		$this->head_css();
		$this->head_links();
		$this->head_lines();
		$this->head_script();
		$this->head_custom();

		$this->htmlPrinter->output('</head>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function head_title()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$pagetitle = strlen(qa_request()) ? strip_tags(@$this->content['title']) : '';
		$headtitle = (strlen($pagetitle) ? ($pagetitle.' - ') : '').$this->content['site_title'];

		$this->htmlPrinter->output('<title>'.$headtitle.'</title>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function head_metas()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (strlen(@$this->content['description']))
			$this->htmlPrinter->output('<meta name="description" content="'.$this->content['description'].'"/>');

		if (strlen(@$this->content['keywords'])) // as far as I know, meta keywords have zero effect on search rankings or listings
			$this->htmlPrinter->output('<meta name="keywords" content="'.$this->content['keywords'].'"/>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function head_links()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($this->content['canonical']))
			$this->htmlPrinter->output('<link rel="canonical" href="'.$this->content['canonical'].'"/>');

		if (isset($this->content['feed']['url']))
			$this->htmlPrinter->output('<link rel="alternate" type="application/rss+xml" href="'.$this->content['feed']['url'].'" title="'.@$this->content['feed']['label'].'"/>');

		// convert page links to rel=prev and rel=next tags
		if (isset($this->content['page_links']['items'])) {
			foreach ($this->content['page_links']['items'] as $page_link) {
				if (in_array($page_link['type'], array('prev', 'next')))
					$this->htmlPrinter->output('<link rel="' . $page_link['type'] . '" href="' . $page_link['url'] . '" />');
			}
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function head_script()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($this->content['script'])) {
			foreach ($this->content['script'] as $scriptline)
				$this->htmlPrinter->outputRaw($scriptline);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function head_css()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<link rel="stylesheet" href="'.$this->rooturl.$this->css_name().'"/>');

		if (isset($this->content['css_src'])) {
			foreach ($this->content['css_src'] as $css_src)
				$this->htmlPrinter->output('<link rel="stylesheet" href="'.$css_src.'"/>');
		}

		if (!empty($this->content['notices'])) {
			$this->htmlPrinter->output(
				'<style>',
				'.qa-body-js-on .qa-notice {display:none;}',
				'</style>'
			);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function css_name()
	{
		return 'qa-styles.css?'.QA_VERSION;
	}

	public function head_lines()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($this->content['head_lines'])) {
			foreach ($this->content['head_lines'] as $line)
				$this->htmlPrinter->outputRaw($line);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function head_custom()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<body');
		$this->body_tags();
		$this->htmlPrinter->output('>');

		$this->around_method(__FUNCTION__, $myArray);

		$this->body_script();
		$this->body_header();
		$this->body_content();
		$this->body_footer();
		$this->body_hidden();

		$this->htmlPrinter->output('</body>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body_hidden()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$indent = $this->isRTL ? '9999px' : '-9999px';
		$this->htmlPrinter->output('<div style="position:absolute; left:'.$indent.'; top:-9999px;">');
		$this->waiting_template();
		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function waiting_template()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<span id="qa-waiting-template" class="qa-waiting">...</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body_script()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(
			'<script>',
			"var b=document.getElementsByTagName('body')[0];",
			"b.className=b.className.replace('qa-body-js-off', 'qa-body-js-on');",
			'</script>'
		);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body_header()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($this->content['body_header']))
			$this->htmlPrinter->outputRaw($this->content['body_header']);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body_footer()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($this->content['body_footer']))
			$this->htmlPrinter->outputRaw($this->content['body_footer']);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body_content()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->body_prefix();
		$this->notices();

		$this->htmlPrinter->output('<div class="qa-body-wrapper">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->widgets('full', 'top');
		$this->header();
		$this->widgets('full', 'high');
		$this->sidepanel();
		$this->main();
		$this->widgets('full', 'low');
		$this->footer();
		$this->widgets('full', 'bottom');

		$this->htmlPrinter->output('</div>');

		$this->body_suffix();

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body_tags()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$class = 'qa-template-'.qa_html($this->template);

		if (isset($this->content['categoryids'])) {
			foreach ($this->content['categoryids'] as $categoryid)
				$class .= ' qa-category-'.qa_html($categoryid);
		}

		$this->htmlPrinter->output('class="'.$class.' qa-body-js-off"');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body_prefix()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function body_suffix()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function notices()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($this->content['notices'])) {
			foreach ($this->content['notices'] as $notice)
				$this->notice($notice);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function notice($notice)
	{
		$myArray = array(&$notice);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-notice" id="'.$notice['id'].'">');

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($notice['form_tags']))
			$this->htmlPrinter->output('<form '.$notice['form_tags'].'>');

		$this->htmlPrinter->outputRaw($notice['content']);

		$this->htmlPrinter->output('<input '.$notice['close_tags'].' type="submit" value="X" class="qa-notice-close-button"/> ');

		if (isset($notice['form_tags'])) {
			$this->form_hidden_elements(@$notice['form_hidden']);
			$this->htmlPrinter->output('</form>');
		}

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function header()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-header">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->logo();
		$this->nav_user_search();
		$this->nav_main_sub();
		$this->header_clear();

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function nav_user_search()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->nav('user');
		$this->search();

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function nav_main_sub()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->nav('main');
		$this->nav('sub');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function logo()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-logo">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output($this->content['logo']);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function search()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-search">');

		$this->around_method(__FUNCTION__, $myArray);

		$search = $this->content['search'];
		$this->htmlPrinter->output(
			'<form '.$search['form_tags'].'>',
			@$search['form_extra']
		);

		$this->search_field($search);
		$this->search_button($search);

		$this->htmlPrinter->output(
			'</form>',
			'</div>'
		);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function search_field($search)
	{
		$myArray = array(&$search);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<input type="text" '.$search['field_tags'].' value="'.@$search['value'].'" class="qa-search-field"/>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function search_button($search)
	{
		$myArray = array(&$search);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<input type="submit" value="'.$search['button_label'].'" class="qa-search-button"/>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function nav($navtype, $level=null)
	{
		$myArray = array($navtype, $level);

		$this->before_method(__FUNCTION__, $myArray);

		$navigation = @$this->content['navigation'][$navtype];

		if ($navtype == 'user' || isset($navigation)) {
			$this->htmlPrinter->output('<div class="qa-nav-'.$navtype.'">');

			$this->around_method(__FUNCTION__, $myArray);

			if ($navtype == 'user')
				$this->logged_in();

			// reverse order of 'opposite' items since they float right
			foreach (array_reverse($navigation, true) as $key => $navlink) {
				if (@$navlink['opposite']) {
					unset($navigation[$key]);
					$navigation[$key] = $navlink;
				}
			}

			$this->htmlPrinter->setContext('nav_type', $navtype);
			$this->nav_list($navigation, 'nav-'.$navtype, $level);
			$this->nav_clear($navtype);
			$this->htmlPrinter->clearContext('nav_type');

			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function nav_list($navigation, $class, $level=null)
	{
		$myArray = array(&$navigation, $class, $level);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<ul class="qa-'.$class.'-list'.(isset($level) ? (' qa-'.$class.'-list-'.$level) : '').'">');

		$this->around_method(__FUNCTION__, $myArray);

		$index = 0;

		foreach ($navigation as $key => $navlink) {
			$this->htmlPrinter->setContext('nav_key', $key);
			$this->htmlPrinter->setContext('nav_index', $index++);
			$this->nav_item($key, $navlink, $class, $level);
		}

		$this->htmlPrinter->clearContext('nav_key');
		$this->htmlPrinter->clearContext('nav_index');

		$this->htmlPrinter->output('</ul>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function nav_clear($navtype)
	{
		$myArray = array($navtype);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-nav-'.$navtype.'-clear">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function nav_item($key, $navlink, $class, $level=null)
	{
		$myArray = array($key, &$navlink, $class, $level);

		$this->before_method(__FUNCTION__, $myArray);

		$suffix = strtr($key, array( // map special character in navigation key
			'$' => '',
			'/' => '-',
		));

		$this->htmlPrinter->output('<li class="qa-'.$class.'-item'.(@$navlink['opposite'] ? '-opp' : '').
			(@$navlink['state'] ? (' qa-'.$class.'-'.$navlink['state']) : '').' qa-'.$class.'-'.$suffix.'">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->nav_link($navlink, $class);

		if (count(@$navlink['subnav']))
			$this->nav_list($navlink['subnav'], $class, 1+$level);

		$this->htmlPrinter->output('</li>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function nav_link($navlink, $class)
	{
		$myArray = array(&$navlink, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($navlink['url'])) {
			$this->htmlPrinter->output(
				'<a href="'.$navlink['url'].'" class="qa-'.$class.'-link'.
				(@$navlink['selected'] ? (' qa-'.$class.'-selected') : '').
				(@$navlink['favorited'] ? (' qa-'.$class.'-favorited') : '').
				'"'.(strlen(@$navlink['popup']) ? (' title="'.$navlink['popup'].'"') : '').
				(isset($navlink['target']) ? (' target="'.$navlink['target'].'"') : '').'>'.$navlink['label'].
				'</a>'
			);
		}
		else {
			$this->htmlPrinter->output(
				'<span class="qa-'.$class.'-nolink'.(@$navlink['selected'] ? (' qa-'.$class.'-selected') : '').
				(@$navlink['favorited'] ? (' qa-'.$class.'-favorited') : '').'"'.
				(strlen(@$navlink['popup']) ? (' title="'.$navlink['popup'].'"') : '').
				'>'.$navlink['label'].'</span>'
			);
		}

		if (strlen(@$navlink['note']))
			$this->htmlPrinter->output('<span class="qa-'.$class.'-note">'.$navlink['note'].'</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function logged_in()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->outputSplit(@$this->content['loggedin'], 'qa-logged-in', 'div');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function header_clear()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-header-clear">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function sidepanel()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-sidepanel">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->widgets('side', 'top');
		$this->sidebar();
		$this->widgets('side', 'high');
		$this->nav('cat', 1);
		$this->widgets('side', 'low');
		$this->htmlPrinter->outputRaw(@$this->content['sidepanel']);
		$this->feed();
		$this->widgets('side', 'bottom');
		$this->htmlPrinter->output('</div>', '');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function sidebar()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$sidebar = @$this->content['sidebar'];

		if (!empty($sidebar)) {
			$this->htmlPrinter->output('<div class="qa-sidebar">');
			$this->htmlPrinter->outputRaw($sidebar);
			$this->htmlPrinter->output('</div>', '');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function feed()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$feed = @$this->content['feed'];

		if (!empty($feed)) {
			$this->htmlPrinter->output('<div class="qa-feed">');
			$this->htmlPrinter->output('<a href="'.$feed['url'].'" class="qa-feed-link">'.@$feed['label'].'</a>');
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function main()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$content = $this->content;
		$hidden = !empty($content['hidden']) ? ' qa-main-hidden' : '';
		$extratags = isset($this->content['main_tags']) ? $this->content['main_tags'] : '';

		$this->htmlPrinter->output('<div class="qa-main'.$hidden.'"'.$extratags.'>');

		$this->around_method(__FUNCTION__, $myArray);

		$this->widgets('main', 'top');

		$this->page_title_error();

		$this->widgets('main', 'high');

		$this->main_parts($content);

		$this->widgets('main', 'low');

		$this->page_links();
		$this->suggest_next();

		$this->widgets('main', 'bottom');

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function page_title_error()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($this->content['title'])) {
			$favorite = isset($this->content['favorite']) ? $this->content['favorite'] : null;

			if (isset($favorite))
				$this->htmlPrinter->output('<form ' . $favorite['form_tags'] . '>');

			$this->htmlPrinter->output('<h1>');
			$this->favorite();
			$this->title();
			$this->htmlPrinter->output('</h1>');

			if (isset($favorite)) {
				$formhidden = isset($favorite['form_hidden']) ? $favorite['form_hidden'] : null;
				$this->form_hidden_elements($formhidden);
				$this->htmlPrinter->output('</form>');
			}
		}
		if (isset($this->content['error']))
			$this->error($this->content['error']);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function favorite()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$favorite = isset($this->content['favorite']) ? $this->content['favorite'] : null;
		if (isset($favorite)) {
			$favoritetags = isset($favorite['favorite_tags']) ? $favorite['favorite_tags'] : '';
			$this->htmlPrinter->output('<span class="qa-favoriting" ' . $favoritetags . '>');
			$this->favorite_inner_html($favorite);
			$this->htmlPrinter->output('</span>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function title()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$q_view = @$this->content['q_view'];

		// link title where appropriate
		$url = isset($q_view['url']) ? $q_view['url'] : false;

		if (isset($this->content['title'])) {
			$this->htmlPrinter->output(
				$url ? '<a href="'.$url.'">' : '',
				$this->content['title'],
				$url ? '</a>' : ''
			);
		}

		// add closed note in title
		if (!empty($q_view['closed']))
			$this->htmlPrinter->output(' ['.$q_view['closed']['state'].']');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function favorite_inner_html($favorite)
	{
		$myArray = array(&$favorite);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->favorite_button(@$favorite['favorite_add_tags'], 'qa-favorite');
		$this->favorite_button(@$favorite['favorite_remove_tags'], 'qa-unfavorite');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function favorite_button($tags, $class)
	{
		$myArray = array($tags, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($tags))
			$this->htmlPrinter->output('<input '.$tags.' type="submit" value="" class="'.$class.'-button"/> ');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function error($error)
	{
		$myArray = array($error);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (strlen($error)) {
			$this->htmlPrinter->output(
				'<div class="qa-error">',
				$error,
				'</div>'
			);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function main_parts($content)
	{
		$myArray = array(&$content);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		foreach ($content as $key => $part) {
			$this->htmlPrinter->setContext('part', $key);
			$this->main_part($key, $part);
		}

		$this->htmlPrinter->clearContext('part');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function main_part($key, $part)
	{
		$myArray = array($key, $part);

		$this->before_method(__FUNCTION__, $myArray);

		$partdiv = (
			strpos($key, 'custom') === 0 ||
			strpos($key, 'form') === 0 ||
			strpos($key, 'q_list') === 0 ||
			(strpos($key, 'q_view') === 0 && !isset($this->content['form_q_edit'])) ||
			strpos($key, 'a_form') === 0 ||
			strpos($key, 'a_list') === 0 ||
			strpos($key, 'ranking') === 0 ||
			strpos($key, 'message_list') === 0 ||
			strpos($key, 'nav_list') === 0
		);

		if ($partdiv)
			$this->htmlPrinter->output('<div class="qa-part-'.strtr($key, '_', '-').'">'); // to help target CSS to page parts

		$this->around_method(__FUNCTION__, $myArray);

		if (strpos($key, 'custom') === 0)
			$this->htmlPrinter->outputRaw($part);

		elseif (strpos($key, 'form') === 0)
			$this->form($part);

		elseif (strpos($key, 'q_list') === 0)
			$this->q_list_and_form($part);

		elseif (strpos($key, 'q_view') === 0)
			$this->q_view($part);

		elseif (strpos($key, 'a_form') === 0)
			$this->a_form($part);

		elseif (strpos($key, 'a_list') === 0)
			$this->a_list($part);

		elseif (strpos($key, 'ranking') === 0)
			$this->ranking($part);

		elseif (strpos($key, 'message_list') === 0)
			$this->message_list_and_form($part);

		elseif (strpos($key, 'nav_list') === 0) {
			$this->part_title($part);
			$this->nav_list($part['nav'], $part['type'], 1);
		}

		if ($partdiv)
			$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function footer()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-footer">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->nav('footer');
		$this->attribution();
		$this->footer_clear();

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function attribution()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-attribution">');

		$this->around_method(__FUNCTION__, $myArray);

		// Hi there. I'd really appreciate you displaying this link on your Q2A site. Thank you - Gideon
		$this->htmlPrinter->output('Powered by <a href="http://www.question2answer.org/">Question2Answer</a>');
		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function footer_clear()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-footer-clear">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function section($title)
	{
		$myArray = array($title);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->part_title(array('title' => $title));

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function part_title($part)
	{
		$myArray = array(&$part);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (strlen(@$part['title']) || strlen(@$part['title_tags']))
			$this->htmlPrinter->output('<h2'.rtrim(' '.@$part['title_tags']).'>'.@$part['title'].'</h2>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function part_footer($part)
	{
		$myArray = array(&$part);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($part['footer']))
			$this->htmlPrinter->output($part['footer']);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form($form)
	{
		$myArray = array(&$form);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($form)) {
			$this->part_title($form);

			if (isset($form['tags']))
				$this->htmlPrinter->output('<form '.$form['tags'].'>');

			$this->form_body($form);

			if (isset($form['tags']))
				$this->htmlPrinter->output('</form>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_columns($form)
	{
		if (isset($form['ok']) || !empty($form['fields']) )
			$columns = ($form['style'] == 'wide') ? 3 : 1;
		else
			$columns = 0;

		return $columns;
	}

	public function form_spacer($form, $columns)
	{
		$myArray = array(&$form, $columns);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(
			'<tr>',
			'<td colspan="'.$columns.'" class="qa-form-'.$form['style'].'-spacer">'
		);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(
			'&nbsp;',
			'</td>',
			'</tr>'
		);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_body($form)
	{
		$myArray = array(&$form);

		$this->before_method(__FUNCTION__, $myArray);

		if (@$form['boxed'])
			$this->htmlPrinter->output('<div class="qa-form-table-boxed">');

		$columns = $this->form_columns($form);

		if ($columns)
			$this->htmlPrinter->output('<table class="qa-form-'.$form['style'].'-table">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->form_ok($form, $columns);
		$this->form_fields($form, $columns);
		$this->form_buttons($form, $columns);

		if ($columns)
			$this->htmlPrinter->output('</table>');

		$this->form_hidden($form);

		if (@$form['boxed'])
			$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_ok($form, $columns)
	{
		$myArray = array(&$form, $columns);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($form['ok'])) {
			$this->htmlPrinter->output(
				'<tr>',
				'<td colspan="'.$columns.'" class="qa-form-'.$form['style'].'-ok">',
				$form['ok'],
				'</td>',
				'</tr>'
			);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_fields($form, $columns)
	{
		$myArray = array(&$form, $columns);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($form['fields'])) {
			foreach ($form['fields'] as $key => $field) {
				$this->htmlPrinter->setContext('field_key', $key);

				if (@$field['type'] == 'blank')
					$this->form_spacer($form, $columns);
				else
					$this->form_field_rows($form, $columns, $field);
			}

			$this->htmlPrinter->clearContext('field_key');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_field_rows($form, $columns, $field)
	{
		$myArray = array(&$form, $columns, &$field);

		$this->before_method(__FUNCTION__, $myArray);

		$style = $form['style'];

		if (isset($field['style'])) { // field has different style to most of form
			$style = $field['style'];
			$colspan = $columns;
			$columns = ($style == 'wide') ? 3 : 1;
		}
		else
			$colspan = null;

		$prefixed = (@$field['type'] == 'checkbox') && ($columns == 1) && !empty($field['label']);
		$suffixed = (@$field['type'] == 'select' || @$field['type'] == 'number') && $columns == 1 && !empty($field['label']) && !@$field['loose'];
		$skipdata = @$field['tight'];
		$tworows = ($columns == 1) && (!empty($field['label'])) && (!$skipdata) &&
			( (!($prefixed||$suffixed)) || (!empty($field['error'])) || (!empty($field['note'])) );

		if (isset($field['id'])) {
			if ($columns == 1)
				$this->htmlPrinter->output('<tbody id="'.$field['id'].'">', '<tr>');
			else
				$this->htmlPrinter->output('<tr id="'.$field['id'].'">');
		}
		else
			$this->htmlPrinter->output('<tr>');

		$this->around_method(__FUNCTION__, $myArray);

		if ($columns > 1 || !empty($field['label']))
			$this->form_label($field, $style, $columns, $prefixed, $suffixed, $colspan);

		if ($tworows) {
			$this->htmlPrinter->output(
				'</tr>',
				'<tr>'
			);
		}

		if (!$skipdata)
			$this->form_data($field, $style, $columns, !($prefixed||$suffixed), $colspan);

		$this->htmlPrinter->output('</tr>');

		if ($columns == 1 && isset($field['id']))
			$this->htmlPrinter->output('</tbody>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_label($field, $style, $columns, $prefixed, $suffixed, $colspan)
	{
		$myArray = array(&$field, $style, $columns, $prefixed, $suffixed, $colspan);

		$this->before_method(__FUNCTION__, $myArray);

		$extratags = '';

		if ($columns > 1 && (@$field['type'] == 'select-radio' || @$field['rows'] > 1))
			$extratags .= ' style="vertical-align:top;"';

		if (isset($colspan))
			$extratags .= ' colspan="'.$colspan.'"';

		$this->htmlPrinter->output('<td class="qa-form-'.$style.'-label"'.$extratags.'>');

		if ($prefixed) {
			$this->htmlPrinter->output('<label>');
			$this->form_field($field, $style);
		}

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(@$field['label']);

		if ($prefixed)
			$this->htmlPrinter->output('</label>');

		if ($suffixed) {
			$this->htmlPrinter->output('&nbsp;');
			$this->form_field($field, $style);
		}

		$this->htmlPrinter->output('</td>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_data($field, $style, $columns, $showfield, $colspan)
	{
		$myArray = array(&$field, $style, $columns, $showfield, $colspan);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if ($showfield || (!empty($field['error'])) || (!empty($field['note']))) {
			$this->htmlPrinter->output(
				'<td class="qa-form-'.$style.'-data"'.(isset($colspan) ? (' colspan="'.$colspan.'"') : '').'>'
			);

			if ($showfield)
				$this->form_field($field, $style);

			if (!empty($field['error'])) {
				if (@$field['note_force'])
					$this->form_note($field, $style, $columns);

				$this->form_error($field, $style, $columns);
			}
			elseif (!empty($field['note']))
				$this->form_note($field, $style, $columns);

			$this->htmlPrinter->output('</td>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_field($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->form_prefix($field, $style);

		$this->htmlPrinter->outputRaw(@$field['html_prefix']);

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
				$this->htmlPrinter->outputRaw(@$field['html']);
				break;

			default:
				if (@$field['type'] == 'textarea' || @$field['rows'] > 1)
					$this->form_text_multi_row($field, $style);
				else
					$this->form_text_single_row($field, $style);
				break;
		}

		$this->htmlPrinter->outputRaw(@$field['html_suffix']);

		$this->form_suffix($field, $style);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_buttons($form, $columns)
	{
		$myArray = array(&$form, $columns);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($form['buttons'])) {
			$style = @$form['style'];

			if ($columns) {
				$this->htmlPrinter->output(
					'<tr>',
					'<td colspan="'.$columns.'" class="qa-form-'.$style.'-buttons">'
				);
			}

			foreach ($form['buttons'] as $key => $button) {
				$this->htmlPrinter->setContext('button_key', $key);

				if (empty($button))
					$this->form_button_spacer($style);
				else {
					$this->form_button_data($button, $key, $style);
					$this->form_button_note($button, $style);
				}
			}

			$this->htmlPrinter->clearContext('button_key');

			if ($columns) {
				$this->htmlPrinter->output(
					'</td>',
					'</tr>'
				);
			}
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_button_data($button, $key, $style)
	{
		$myArray = array(&$button, $key, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$baseclass = 'qa-form-'.$style.'-button qa-form-'.$style.'-button-'.$key;

		$this->htmlPrinter->output('<input'.rtrim(' '.@$button['tags']).' value="'.@$button['label'].'" title="'.@$button['popup'].'" type="submit"'.
			(isset($style) ? (' class="'.$baseclass.'"') : '').'/>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_button_note($button, $style)
	{
		$myArray = array(&$button, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($button['note'])) {
			$this->htmlPrinter->output(
				'<span class="qa-form-'.$style.'-note">',
				$button['note'],
				'</span>',
				'<br/>'
			);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_button_spacer($style)
	{
		$myArray = array($style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<span class="qa-form-'.$style.'-buttons-spacer">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('&nbsp;</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_hidden($form)
	{
		$myArray = array(&$form);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->form_hidden_elements(@$form['hidden']);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_hidden_elements($hidden)
	{
		$myArray = array(&$hidden);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($hidden)) {
			foreach ($hidden as $name => $value) {
				if (is_array($value)) {
					// new method of outputting tags
					$this->htmlPrinter->output('<input '.@$value['tags'].' type="hidden" value="'.@$value['value'].'"/>');
				}
				else {
					// old method
					$this->htmlPrinter->output('<input name="'.$name.'" type="hidden" value="'.$value.'"/>');
				}
			}
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_prefix($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($field['prefix']))
			$this->htmlPrinter->output('<span class="qa-form-'.$style.'-prefix">'.$field['prefix'].'</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_suffix($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($field['suffix']))
			$this->htmlPrinter->output('<span class="qa-form-'.$style.'-suffix">'.$field['suffix'].'</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_checkbox($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<input '.@$field['tags'].' type="checkbox" value="1"'.(@$field['value'] ? ' checked' : '').' class="qa-form-'.$style.'-checkbox"/>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_static($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<span class="qa-form-'.$style.'-static">'.@$field['value'].'</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_password($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<input '.@$field['tags'].' type="password" value="'.@$field['value'].'" class="qa-form-'.$style.'-text"/>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_number($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<input '.@$field['tags'].' type="text" value="'.@$field['value'].'" class="qa-form-'.$style.'-number"/>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	/**
	 * Output a <select> element. The $field array may contain the following keys:
	 *   options: (required) a key-value array containing all the options in the select.
	 *   tags: any attributes to be added to the select.
	 *   value: the selected value from the 'options' parameter.
	 *   match_by: whether to match the 'value' (default) or 'key' of each option to determine if it is to be selected.
	 */
	public function form_select($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<select ' . (isset($field['tags']) ? $field['tags'] : '') . ' class="qa-form-' . $style . '-select">');

		$this->around_method(__FUNCTION__, $myArray);

		// Only match by key if it is explicitly specified. Otherwise, for backwards compatibility, match by value
		$matchbykey = isset($field['match_by']) && $field['match_by'] === 'key';

		foreach ($field['options'] as $key => $value) {
			$selected = isset($field['value']) && (
				($matchbykey && $key === $field['value']) ||
				(!$matchbykey && $value === $field['value'])
			);
			$this->htmlPrinter->output('<option value="' . $key . '"' . ($selected ? ' selected' : '') . '>' . $value . '</option>');
		}

		$this->htmlPrinter->output('</select>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_select_radio($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$radios = 0;

		foreach ($field['options'] as $tag => $value) {
			if ($radios++)
				$this->htmlPrinter->output('<br/>');

			$this->htmlPrinter->output('<input '.@$field['tags'].' type="radio" value="'.$tag.'"'.(($value == @$field['value']) ? ' checked' : '').' class="qa-form-'.$style.'-radio"/> '.$value);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_image($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-form-'.$style.'-image">'.@$field['html'].'</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_text_single_row($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<input '.@$field['tags'].' type="text" value="'.@$field['value'].'" class="qa-form-'.$style.'-text"/>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_text_multi_row($field, $style)
	{
		$myArray = array(&$field, $style);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<textarea '.@$field['tags'].' rows="'.(int)$field['rows'].'" cols="40" class="qa-form-'.$style.'-text">'.@$field['value'].'</textarea>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_error($field, $style, $columns)
	{
		$myArray = array(&$field, $style, $columns);

		$this->before_method(__FUNCTION__, $myArray);

		$tag = ($columns > 1) ? 'span' : 'div';

		$this->htmlPrinter->output('<'.$tag.' class="qa-form-'.$style.'-error">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output($field['error'].'</'.$tag.'>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function form_note($field, $style, $columns)
	{
		$myArray = array(&$field, $style, $columns);

		$this->before_method(__FUNCTION__, $myArray);

		$tag = ($columns > 1) ? 'span' : 'div';

		$this->htmlPrinter->output('<'.$tag.' class="qa-form-'.$style.'-note">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(@$field['note'].'</'.$tag.'>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function ranking($ranking)
	{
		$myArray = array(&$ranking);

		$this->before_method(__FUNCTION__, $myArray);

		$this->part_title($ranking);

		if (!isset($ranking['type']))
			$ranking['type'] = 'items';

		$class = 'qa-top-'.$ranking['type'];

		$this->around_method(__FUNCTION__, $myArray);

		if (!$this->ranking_block_layout) {
			// old, less semantic table layout
			$this->ranking_table($ranking, $class);
		}
		else {
			// new block layout
			foreach ($ranking['items'] as $item) {
				$this->htmlPrinter->output('<span class="qa-ranking-item '.$class.'-item">');
				$this->ranking_item($item, $class);
				$this->htmlPrinter->output('</span>');
			}
		}

		$this->part_footer($ranking);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function ranking_item($item, $class, $spacer=false) // $spacer is deprecated
	{
		$myArray = array(&$item, $class, $spacer);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!$this->ranking_block_layout) {
			// old table layout
			$this->ranking_table_item($item, $class, $spacer);
			return;
		}

		if (isset($item['count']))
			$this->ranking_count($item, $class);

		if (isset($item['avatar']))
			$this->avatar($item, $class);

		$this->ranking_label($item, $class);

		if (isset($item['score']))
			$this->ranking_score($item, $class);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function ranking_cell($content, $class)
	{
		$myArray = array(&$content, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$tag = $this->ranking_block_layout ? 'span': 'td';
		$this->htmlPrinter->output('<'.$tag.' class="'.$class.'">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output($content . '</'.$tag.'>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function ranking_count($item, $class)
	{
		$myArray = array(&$item, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->ranking_cell($item['count'].' &#215;', $class.'-count');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function ranking_label($item, $class)
	{
		$myArray = array(&$item, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->ranking_cell($item['label'], $class.'-label');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function ranking_score($item, $class)
	{
		$myArray = array(&$item, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->ranking_cell($item['score'], $class.'-score');

		$this->after_method(__FUNCTION__, $myArray);
	}

	/**
	 * @deprecated Table-based layout of users/tags is deprecated from 1.7 onwards and may be
	 * removed in a future version. Themes can switch to the new layout by setting the member
	 * variable $ranking_block_layout to false.
	 */
	public function ranking_table($ranking, $class)
	{
		$myArray = array(&$ranking, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$rows = min($ranking['rows'], count($ranking['items']));

		if ($rows > 0) {
			$this->htmlPrinter->output('<table class="'.$class.'-table">');
			$columns = ceil(count($ranking['items']) / $rows);

			for ($row = 0; $row < $rows; $row++) {
				$this->htmlPrinter->setContext('ranking_row', $row);
				$this->htmlPrinter->output('<tr>');

				for ($column = 0; $column < $columns; $column++) {
					$this->htmlPrinter->setContext('ranking_column', $column);
					$this->ranking_table_item(@$ranking['items'][$column*$rows+$row], $class, $column>0);
				}

				$this->htmlPrinter->clearContext('ranking_column');
				$this->htmlPrinter->output('</tr>');
			}
			$this->htmlPrinter->clearContext('ranking_row');
			$this->htmlPrinter->output('</table>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	/**
	 * @deprecated See ranking_table above.
	 */
	public function ranking_table_item($item, $class, $spacer)
	{
		$myArray = array(&$item, $class, $spacer);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if ($spacer)
			$this->ranking_spacer($class);

		if (empty($item)) {
			$this->ranking_spacer($class);
			$this->ranking_spacer($class);

		} else {
			if (isset($item['count']))
				$this->ranking_count($item, $class);

			if (isset($item['avatar']))
				$item['label'] = $item['avatar'].' '.$item['label'];

			$this->ranking_label($item, $class);

			if (isset($item['score']))
				$this->ranking_score($item, $class);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	/**
	 * @deprecated See ranking_table above.
	 */
	public function ranking_spacer($class)
	{
		$myArray = array($class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<td class="'.$class.'-spacer">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('&nbsp;</td>');

		$this->after_method(__FUNCTION__, $myArray);
	}


	public function message_list_and_form($list)
	{
		$myArray = array(&$list);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($list)) {
			$this->part_title($list);

			$this->error(@$list['error']);

			if (!empty($list['form'])) {
				$this->htmlPrinter->output('<form '.$list['form']['tags'].'>');
				unset($list['form']['tags']); // we already output the tags before the messages
				$this->message_list_form($list);
			}

			$this->message_list($list);

			if (!empty($list['form'])) {
				$this->htmlPrinter->output('</form>');
			}
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function message_list_form($list)
	{
		$myArray = array(&$list);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($list['form'])) {
			$this->htmlPrinter->output('<div class="qa-message-list-form">');
			$this->form($list['form']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function message_list($list)
	{
		$myArray = array(&$list);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($list['messages'])) {
			$this->htmlPrinter->output('<div class="qa-message-list" '.@$list['tags'].'>');

			foreach ($list['messages'] as $message)
				$this->message_item($message);

			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function message_item($message)
	{
		$myArray = array(&$message);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-message-item" '.@$message['tags'].'>');

		$this->around_method(__FUNCTION__, $myArray);

		$this->message_content($message);
		$this->post_avatar_meta($message, 'qa-message');
		$this->message_buttons($message);
		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function message_content($message)
	{
		$myArray = array(&$message);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($message['content'])) {
			$this->htmlPrinter->output('<div class="qa-message-content">');
			$this->htmlPrinter->outputRaw($message['content']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function message_buttons($item)
	{
		$myArray = array(&$item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($item['form'])) {
			$this->htmlPrinter->output('<div class="qa-message-buttons">');
			$this->form($item['form']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function list_vote_disabled($items)
	{
		$disabled = false;

		if (count($items) > 0) {
			$disabled = true;

			foreach ($items as $item) {
				if (@$item['vote_on_page'] != 'disabled')
					$disabled = false;
			}
		}

		return $disabled;
	}

	public function q_list_and_form($q_list)
	{
		$myArray = array(&$q_list);

		$this->before_method(__FUNCTION__, $myArray);

		if (empty($q_list))
			return;

		$this->part_title($q_list);

		if (!empty($q_list['form']))
			$this->htmlPrinter->output('<form '.$q_list['form']['tags'].'>');


		$this->around_method(__FUNCTION__, $myArray);

		$this->q_list($q_list);

		if (!empty($q_list['form'])) {
			unset($q_list['form']['tags']); // we already output the tags before the qs
			$this->q_list_form($q_list);
			$this->htmlPrinter->output('</form>');
		}

		$this->part_footer($q_list);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_list_form($q_list)
	{
		$myArray = array(&$q_list);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_list['form'])) {
			$this->htmlPrinter->output('<div class="qa-q-list-form">');
			$this->form($q_list['form']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_list($q_list)
	{
		$myArray = array(&$q_list);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($q_list['qs'])) {
			$this->htmlPrinter->output('<div class="qa-q-list'.($this->list_vote_disabled($q_list['qs']) ? ' qa-q-list-vote-disabled' : '').'">', '');
			$this->q_list_items($q_list['qs']);
			$this->htmlPrinter->output('</div>', '');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_list_items($q_items)
	{
		$myArray = array(&$q_items);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		foreach ($q_items as $q_item)
			$this->q_list_item($q_item);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_list_item($q_item)
	{
		$myArray = array(&$q_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-q-list-item'.rtrim(' '.@$q_item['classes']).'" '.@$q_item['tags'].'>');

		$this->around_method(__FUNCTION__, $myArray);

		$this->q_item_stats($q_item);
		$this->q_item_main($q_item);
		$this->q_item_clear();

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_item_stats($q_item)
	{
		$myArray = array(&$q_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-q-item-stats">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->voting($q_item);
		$this->a_count($q_item);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_item_main($q_item)
	{
		$myArray = array(&$q_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-q-item-main">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->view_count($q_item);
		$this->q_item_title($q_item);
		$this->q_item_content($q_item);

		$this->post_avatar_meta($q_item, 'qa-q-item');
		$this->post_tags($q_item, 'qa-q-item');
		$this->q_item_buttons($q_item);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_item_clear()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-q-item-clear">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_item_title($q_item)
	{
		$myArray = array(&$q_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-q-item-title">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(
			'<a href="'.$q_item['url'].'">'.$q_item['title'].'</a>',
			// add closed note in title
			empty($q_item['closed']) ? '' : ' ['.$q_item['closed']['state'].']',
			'</div>'
		);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_item_content($q_item)
	{
		$myArray = array(&$q_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_item['content'])) {
			$this->htmlPrinter->output('<div class="qa-q-item-content">');
			$this->htmlPrinter->outputRaw($q_item['content']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_item_buttons($q_item)
	{
		$myArray = array(&$q_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_item['form'])) {
			$this->htmlPrinter->output('<div class="qa-q-item-buttons">');
			$this->form($q_item['form']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function voting($post)
	{
		$myArray = array(&$post);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($post['vote_view'])) {
			$this->htmlPrinter->output('<div class="qa-voting '.(($post['vote_view'] == 'updown') ? 'qa-voting-updown' : 'qa-voting-net').'" '.@$post['vote_tags'].'>');
			$this->voting_inner_html($post);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function voting_inner_html($post)
	{
		$myArray = array(&$post);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->vote_buttons($post);
		$this->vote_count($post);
		$this->vote_clear();

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function vote_buttons($post)
	{
		$myArray = array(&$post);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-vote-buttons '.(($post['vote_view'] == 'updown') ? 'qa-vote-buttons-updown' : 'qa-vote-buttons-net').'">');

		$this->around_method(__FUNCTION__, $myArray);

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

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function vote_count($post)
	{
		$myArray = array(&$post);

		$this->before_method(__FUNCTION__, $myArray);

		// You can also use $post['upvotes_raw'], $post['downvotes_raw'], $post['netvotes_raw'] to get
		// raw integer vote counts, for graphing or showing in other non-textual ways

		$this->htmlPrinter->output('<div class="qa-vote-count '.(($post['vote_view'] == 'updown') ? 'qa-vote-count-updown' : 'qa-vote-count-net').'"'.@$post['vote_count_tags'].'>');

		$this->around_method(__FUNCTION__, $myArray);

		if ($post['vote_view'] == 'updown') {
			$this->htmlPrinter->outputSplit($post['upvotes_view'], 'qa-upvote-count');
			$this->htmlPrinter->outputSplit($post['downvotes_view'], 'qa-downvote-count');
		}
		else
			$this->htmlPrinter->outputSplit($post['netvotes_view'], 'qa-netvote-count');

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function vote_clear()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-vote-clear">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_count($post)
	{
		$myArray = array(&$post);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		// You can also use $post['answers_raw'] to get a raw integer count of answers

		$this->htmlPrinter->outputSplit(@$post['answers'], 'qa-a-count', 'span', 'span',
			@$post['answer_selected'] ? 'qa-a-count-selected' : (@$post['answers_raw'] ? null : 'qa-a-count-zero'));

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function view_count($post)
	{
		$myArray = array(&$post);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		// You can also use $post['views_raw'] to get a raw integer count of views

		$this->htmlPrinter->outputSplit(@$post['views'], 'qa-view-count');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function avatar($item, $class, $prefix=null)
	{
		$myArray = array(&$item, $class, $prefix);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($item['avatar'])) {
			if (isset($prefix))
				$this->htmlPrinter->output($prefix);

			$this->htmlPrinter->output(
				'<span class="'.$class.'-avatar">',
				$item['avatar'],
				'</span>'
			);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_selection($post)
	{
		$myArray = array(&$post);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-a-selection">');

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($post['select_tags']))
			$this->post_hover_button($post, 'select_tags', '', 'qa-a-select');
		elseif (isset($post['unselect_tags']))
			$this->post_hover_button($post, 'unselect_tags', '', 'qa-a-unselect');
		elseif ($post['selected'])
			$this->htmlPrinter->output('<div class="qa-a-selected">&nbsp;</div>');

		if (isset($post['select_text']))
			$this->htmlPrinter->output('<div class="qa-a-selected-text">'.@$post['select_text'].'</div>');

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_hover_button($post, $element, $value, $class)
	{
		$myArray = array(&$post, $element, $value, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($post[$element]))
			$this->htmlPrinter->output('<input '.$post[$element].' type="submit" value="'.$value.'" class="'.$class.'-button"/> ');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_disabled_button($post, $element, $value, $class)
	{
		$myArray = array(&$post, $element, $value, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($post[$element]))
			$this->htmlPrinter->output('<input '.$post[$element].' type="submit" value="'.$value.'" class="'.$class.'-disabled" disabled="disabled"/> ');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_avatar_meta($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<br/>')
	{
		$myArray = array(&$post, $class, $avatarprefix, $metaprefix, $metaseparator);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<span class="'.$class.'-avatar-meta">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->avatar($post, $class, $avatarprefix);
		$this->post_meta($post, $class, $metaprefix, $metaseparator);
		$this->htmlPrinter->output('</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	/**
	 * @deprecated Deprecated from 1.7; please use avatar() instead.
	 */
	public function post_avatar($post, $class, $prefix=null)
	{
		$myArray = array(&$post, $class, $prefix);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->avatar($post, $class, $prefix);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_meta($post, $class, $prefix=null, $separator='<br/>')
	{
		$myArray = array(&$post, $class, $prefix, $separator);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<span class="'.$class.'-meta">');

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($prefix))
			$this->htmlPrinter->output($prefix);

		$order = explode('^', @$post['meta_order']);

		foreach ($order as $element) {
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
		}

		$this->post_meta_flags($post, $class);

		if (!empty($post['what_2'])) {
			$this->htmlPrinter->output($separator);

			foreach ($order as $element) {
				switch ($element) {
					case 'what':
						$this->htmlPrinter->output('<span class="'.$class.'-what">'.$post['what_2'].'</span>');
						break;

					case 'when':
						$this->htmlPrinter->outputSplit(@$post['when_2'], $class.'-when');
						break;

					case 'who':
						$this->htmlPrinter->outputSplit(@$post['who_2'], $class.'-who');
						break;
				}
			}
		}

		$this->htmlPrinter->output('</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_meta_what($post, $class)
	{
		$myArray = array(&$post, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($post['what'])) {
			$classes = $class.'-what';
			if (@$post['what_your'])
				$classes .= ' '.$class.'-what-your';

			if (isset($post['what_url']))
				$this->htmlPrinter->output('<a href="'.$post['what_url'].'" class="'.$classes.'">'.$post['what'].'</a>');
			else
				$this->htmlPrinter->output('<span class="'.$classes.'">'.$post['what'].'</span>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_meta_when($post, $class)
	{
		$myArray = array(&$post, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->outputSplit(@$post['when'], $class.'-when');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_meta_where($post, $class)
	{
		$myArray = array(&$post, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->outputSplit(@$post['where'], $class.'-where');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_meta_who($post, $class)
	{
		$myArray = array(&$post, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($post['who'])) {
			$this->htmlPrinter->output('<span class="'.$class.'-who">');

			if (strlen(@$post['who']['prefix']))
				$this->htmlPrinter->output('<span class="'.$class.'-who-pad">'.$post['who']['prefix'].'</span>');

			if (isset($post['who']['data']))
				$this->htmlPrinter->output('<span class="'.$class.'-who-data">'.$post['who']['data'].'</span>');

			if (isset($post['who']['title']))
				$this->htmlPrinter->output('<span class="'.$class.'-who-title">'.$post['who']['title'].'</span>');

			// You can also use $post['level'] to get the author's privilege level (as a string)

			if (isset($post['who']['points'])) {
				$post['who']['points']['prefix'] = '('.$post['who']['points']['prefix'];
				$post['who']['points']['suffix'] .= ')';
				$this->htmlPrinter->outputSplit($post['who']['points'], $class.'-who-points');
			}

			if (strlen(@$post['who']['suffix']))
				$this->htmlPrinter->output('<span class="'.$class.'-who-pad">'.$post['who']['suffix'].'</span>');

			$this->htmlPrinter->output('</span>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_meta_flags($post, $class)
	{
		$myArray = array(&$post, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->outputSplit(@$post['flags'], $class.'-flags');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_tags($post, $class)
	{
		$myArray = array(&$post, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($post['q_tags'])) {
			$this->htmlPrinter->output('<div class="'.$class.'-tags">');
			$this->post_tag_list($post, $class);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_tag_list($post, $class)
	{
		$myArray = array(&$post, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<ul class="'.$class.'-tag-list">');

		$this->around_method(__FUNCTION__, $myArray);

		foreach ($post['q_tags'] as $taghtml)
			$this->post_tag_item($taghtml, $class);

		$this->htmlPrinter->output('</ul>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function post_tag_item($taghtml, $class)
	{
		$myArray = array($taghtml, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<li class="'.$class.'-tag-item">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output($taghtml.'</li>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function page_links()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$page_links = @$this->content['page_links'];

		if (!empty($page_links)) {
			$this->htmlPrinter->output('<div class="qa-page-links">');

			$this->page_links_label(@$page_links['label']);
			$this->page_links_list(@$page_links['items']);
			$this->page_links_clear();

			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function page_links_label($label)
	{
		$myArray = array($label);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($label))
			$this->htmlPrinter->output('<span class="qa-page-links-label">'.$label.'</span>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function page_links_list($page_items)
	{
		$myArray = array(&$page_items);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($page_items)) {
			$this->htmlPrinter->output('<ul class="qa-page-links-list">');

			$index = 0;

			foreach ($page_items as $page_link) {
				$this->htmlPrinter->setContext('page_index', $index++);
				$this->page_links_item($page_link);

				if ($page_link['ellipsis'])
					$this->page_links_item(array('type' => 'ellipsis'));
			}

			$this->htmlPrinter->clearContext('page_index');

			$this->htmlPrinter->output('</ul>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function page_links_item($page_link)
	{
		$myArray = array(&$page_link);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<li class="qa-page-links-item">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->page_link_content($page_link);
		$this->htmlPrinter->output('</li>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function page_link_content($page_link)
	{
		$myArray = array($page_link);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$label = @$page_link['label'];
		$url = @$page_link['url'];

		switch ($page_link['type']) {
			case 'this':
				$this->htmlPrinter->output('<span class="qa-page-selected">'.$label.'</span>');
				break;

			case 'prev':
				$this->htmlPrinter->output('<a href="'.$url.'" class="qa-page-prev">&laquo; '.$label.'</a>');
				break;

			case 'next':
				$this->htmlPrinter->output('<a href="'.$url.'" class="qa-page-next">'.$label.' &raquo;</a>');
				break;

			case 'ellipsis':
				$this->htmlPrinter->output('<span class="qa-page-ellipsis">...</span>');
				break;

			default:
				$this->htmlPrinter->output('<a href="'.$url.'" class="qa-page-link">'.$label.'</a>');
				break;
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function page_links_clear()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-page-links-clear">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function suggest_next()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$suggest = @$this->content['suggest_next'];

		if (!empty($suggest)) {
			$this->htmlPrinter->output('<div class="qa-suggest-next">');
			$this->htmlPrinter->output($suggest);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view($q_view)
	{
		$myArray = array(&$q_view);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_view)) {
			$this->htmlPrinter->output('<div class="qa-q-view'.(@$q_view['hidden'] ? ' qa-q-view-hidden' : '').rtrim(' '.@$q_view['classes']).'"'.rtrim(' '.@$q_view['tags']).'>');

			if (isset($q_view['main_form_tags']))
				$this->htmlPrinter->output('<form '.$q_view['main_form_tags'].'>'); // form for voting buttons

			$this->q_view_stats($q_view);

			if (isset($q_view['main_form_tags'])) {
				$this->form_hidden_elements(@$q_view['voting_form_hidden']);
				$this->htmlPrinter->output('</form>');
			}

			$this->q_view_main($q_view);
			$this->q_view_clear();

			$this->htmlPrinter->output('</div>', '');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view_stats($q_view)
	{
		$myArray = array(&$q_view);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-q-view-stats">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->voting($q_view);
		$this->a_count($q_view);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view_main($q_view)
	{
		$myArray = array(&$q_view);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-q-view-main">');

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($q_view['main_form_tags']))
			$this->htmlPrinter->output('<form '.$q_view['main_form_tags'].'>'); // form for buttons on question

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
			$this->htmlPrinter->output('</form>');
		}

		$this->c_form(@$q_view['c_form']);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view_content($q_view)
	{
		$myArray = array(&$q_view);

		$this->before_method(__FUNCTION__, $myArray);

		$content = isset($q_view['content']) ? $q_view['content'] : '';

		$this->htmlPrinter->output('<div class="qa-q-view-content">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->outputRaw($content);
		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view_follows($q_view)
	{
		$myArray = array(&$q_view);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_view['follows']))
			$this->htmlPrinter->output(
				'<div class="qa-q-view-follows">',
				$q_view['follows']['label'],
				'<a href="'.$q_view['follows']['url'].'" class="qa-q-view-follows-link">'.$q_view['follows']['title'].'</a>',
				'</div>'
			);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view_closed($q_view)
	{
		$myArray = array(&$q_view);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_view['closed'])) {
			$haslink = isset($q_view['closed']['url']);

			$this->htmlPrinter->output(
				'<div class="qa-q-view-closed">',
				$q_view['closed']['label'],
				($haslink ? ('<a href="'.$q_view['closed']['url'].'"') : '<span').' class="qa-q-view-closed-content">',
				$q_view['closed']['content'],
				$haslink ? '</a>' : '</span>',
				'</div>'
			);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view_extra($q_view)
	{
		$myArray = array(&$q_view);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_view['extra'])) {
			$this->htmlPrinter->output(
				'<div class="qa-q-view-extra">',
				$q_view['extra']['label'],
				'<span class="qa-q-view-extra-content">',
				$q_view['extra']['content'],
				'</span>',
				'</div>'
			);
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view_buttons($q_view)
	{
		$myArray = array(&$q_view);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_view['form'])) {
			$this->htmlPrinter->output('<div class="qa-q-view-buttons">');
			$this->form($q_view['form']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_view_clear()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-q-view-clear">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_form($a_form)
	{
		$myArray = array(&$a_form);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-a-form"'.(isset($a_form['id']) ? (' id="'.$a_form['id'].'"') : '').
			(@$a_form['collapse'] ? ' style="display:none;"' : '').'>');

		$this->around_method(__FUNCTION__, $myArray);

		$this->form($a_form);
		$this->c_list(@$a_form['c_list'], 'qa-a-item');

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_list($a_list)
	{
		$myArray = array(&$a_list);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($a_list)) {
			$this->part_title($a_list);

			$this->htmlPrinter->output('<div class="qa-a-list'.($this->list_vote_disabled($a_list['as']) ? ' qa-a-list-vote-disabled' : '').'" '.@$a_list['tags'].'>', '');
			$this->a_list_items($a_list['as']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_list_items($a_items)
	{
		$myArray = array(&$a_items);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		foreach ($a_items as $a_item)
			$this->a_list_item($a_item);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_list_item($a_item)
	{
		$myArray = array(&$a_item);

		$this->before_method(__FUNCTION__, $myArray);

		$extraclass = @$a_item['classes'].($a_item['hidden'] ? ' qa-a-list-item-hidden' : ($a_item['selected'] ? ' qa-a-list-item-selected' : ''));

		$this->htmlPrinter->output('<div class="qa-a-list-item '.$extraclass.'" '.@$a_item['tags'].'>');

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($a_item['main_form_tags']))
			$this->htmlPrinter->output('<form '.$a_item['main_form_tags'].'>'); // form for voting buttons

		$this->voting($a_item);

		if (isset($a_item['main_form_tags'])) {
			$this->form_hidden_elements(@$a_item['voting_form_hidden']);
			$this->htmlPrinter->output('</form>');
		}

		$this->a_item_main($a_item);
		$this->a_item_clear();

		$this->htmlPrinter->output('</div>', '');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_item_main($a_item)
	{
		$myArray = array(&$a_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-a-item-main">');

		$this->around_method(__FUNCTION__, $myArray);

		if (isset($a_item['main_form_tags']))
			$this->htmlPrinter->output('<form '.$a_item['main_form_tags'].'>'); // form for buttons on answer

		if ($a_item['hidden'])
			$this->htmlPrinter->output('<div class="qa-a-item-hidden">');
		elseif ($a_item['selected'])
			$this->htmlPrinter->output('<div class="qa-a-item-selected">');

		$this->a_selection($a_item);
		$this->error(@$a_item['error']);
		$this->a_item_content($a_item);
		$this->post_avatar_meta($a_item, 'qa-a-item');

		if ($a_item['hidden'] || $a_item['selected'])
			$this->htmlPrinter->output('</div>');

		$this->a_item_buttons($a_item);

		$this->c_list(@$a_item['c_list'], 'qa-a-item');

		if (isset($a_item['main_form_tags'])) {
			$this->form_hidden_elements(@$a_item['buttons_form_hidden']);
			$this->htmlPrinter->output('</form>');
		}

		$this->c_form(@$a_item['c_form']);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_item_clear()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-a-item-clear">');
		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_item_content($a_item)
	{
		$myArray = array(&$a_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-a-item-content">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->outputRaw($a_item['content']);
		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function a_item_buttons($a_item)
	{
		$myArray = array(&$a_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($a_item['form'])) {
			$this->htmlPrinter->output('<div class="qa-a-item-buttons">');
			$this->form($a_item['form']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_form($c_form)
	{
		$myArray = array(&$c_form);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-c-form"'.(isset($c_form['id']) ? (' id="'.$c_form['id'].'"') : '').
			(@$c_form['collapse'] ? ' style="display:none;"' : '').'>');

		$this->around_method(__FUNCTION__, $myArray);

		$this->form($c_form);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_list($c_list, $class)
	{
		$myArray = array(&$c_list, $class);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($c_list)) {
			$this->htmlPrinter->output('', '<div class="'.$class.'-c-list"'.(@$c_list['hidden'] ? ' style="display:none;"' : '').' '.@$c_list['tags'].'>');
			$this->c_list_items($c_list['cs']);
			$this->htmlPrinter->output('</div>', '');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_list_items($c_items)
	{
		$myArray = array(&$c_items);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		foreach ($c_items as $c_item)
			$this->c_list_item($c_item);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_list_item($c_item)
	{
		$myArray = array(&$c_item);

		$this->before_method(__FUNCTION__, $myArray);

		$extraclass = @$c_item['classes'].(@$c_item['hidden'] ? ' qa-c-item-hidden' : '');

		$this->htmlPrinter->output('<div class="qa-c-list-item '.$extraclass.'" '.@$c_item['tags'].'>');

		$this->around_method(__FUNCTION__, $myArray);

		$this->c_item_main($c_item);
		$this->c_item_clear();

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_item_main($c_item)
	{
		$myArray = array(&$c_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->error(@$c_item['error']);

		if (isset($c_item['expand_tags']))
			$this->c_item_expand($c_item);
		elseif (isset($c_item['url']))
			$this->c_item_link($c_item);
		else
			$this->c_item_content($c_item);

		$this->htmlPrinter->output('<div class="qa-c-item-footer">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->post_avatar_meta($c_item, 'qa-c-item');
		$this->c_item_buttons($c_item);
		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_item_link($c_item)
	{
		$myArray = array(&$c_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(
			'<a href="'.$c_item['url'].'" class="qa-c-item-link">'.$c_item['title'].'</a>'
		);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_item_expand($c_item)
	{
		$myArray = array(&$c_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output(
			'<a href="'.$c_item['url'].'" '.$c_item['expand_tags'].' class="qa-c-item-expand">'.$c_item['title'].'</a>'
		);

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_item_content($c_item)
	{
		$myArray = array(&$c_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-c-item-content">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->outputRaw($c_item['content']);
		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_item_buttons($c_item)
	{
		$myArray = array(&$c_item);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($c_item['form'])) {
			$this->htmlPrinter->output('<div class="qa-c-item-buttons">');
			$this->form($c_item['form']);
			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function c_item_clear()
	{
		$myArray = array();

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<div class="qa-c-item-clear">');

		$this->around_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('</div>');

		$this->after_method(__FUNCTION__, $myArray);
	}


	public function q_title_list($q_list, $attrs=null)
/*
	Generic method to output a basic list of question links.
*/
	{
		$myArray = array(&$q_list, $attrs);

		$this->before_method(__FUNCTION__, $myArray);

		$this->htmlPrinter->output('<ul class="qa-q-title-list">');

		$this->around_method(__FUNCTION__, $myArray);

		foreach ($q_list as $q) {
			$this->htmlPrinter->output(
				'<li class="qa-q-title-item">',
				'<a href="' . qa_q_path_html($q['postid'], $q['title']) . '" ' . $attrs . '>' . qa_html($q['title']) . '</a>',
				'</li>'
			);
		}
		$this->htmlPrinter->output('</ul>');

		$this->after_method(__FUNCTION__, $myArray);
	}

	public function q_ask_similar($q_list, $pretext='')
/*
	Output block of similar questions when asking.
*/
	{
		$myArray = array(&$q_list, $pretext);

		$this->before_method(__FUNCTION__, $myArray);

		$this->around_method(__FUNCTION__, $myArray);

		if (!empty($q_list)) {
			$this->htmlPrinter->output('<div class="qa-ask-similar">');

			if (strlen($pretext) > 0)
				$this->htmlPrinter->output('<p class="qa-ask-similar-title">'.$pretext.'</p>');
			$this->q_title_list($q_list, 'target="_blank"');

			$this->htmlPrinter->output('</div>');
		}

		$this->after_method(__FUNCTION__, $myArray);
	}
}
