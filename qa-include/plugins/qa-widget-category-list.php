<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Widget module class for activity count plugin


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

class qa_category_list
{
	private $themeobject;

	public function allow_template($template)
	{
		return true;
	}

	public function allow_region($region)
	{
		return $region == 'side';
	}

	public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		$this->themeobject = $themeobject;

		if (isset($qa_content['navigation']['cat'])) {
			$nav = $qa_content['navigation']['cat'];
		} else {
			$selectspec = qa_db_category_nav_selectspec(null, true, false, true);
			$selectspec['caching'] = array(
				'key' => 'qa_db_category_nav_selectspec:default:full',
				'ttl' => qa_opt('caching_catwidget_time'),
			);
			$navcategories = qa_db_single_select($selectspec);
			$nav = qa_category_navigation($navcategories);
		}

		$this->themeobject->output('<h2>' . qa_lang_html('main/nav_categories') . '</h2>');
		$this->themeobject->set_context('nav_type', 'cat');
		$this->themeobject->nav_list($nav, 'nav-cat', 1);
		$this->themeobject->nav_clear('cat');
		$this->themeobject->clear_context('nav_type');
	}
}
