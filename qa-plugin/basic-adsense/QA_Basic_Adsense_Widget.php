<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/basic-adsense/qa-basic-adsense.php
	Description: Widget module class for AdSense widget plugin


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

class QA_Basic_Adsense_Widget extends Q2A_Plugin_Module_Widget
{
	public function getInternalId()
	{
		return 'QA_Basic_Adsense';
	}

	public function getDisplayName()
	{
		return 'Basic Adsense';
	}

	public function isAllowedInTemplate($template)
	{
		return $template !== 'admin';
	}


	public function isAllowedInRegion($region)
	{
		return in_array($region, array('main', 'side', 'full'));
	}

	public function output($region, $place, $themeObject, $template, &$qa_content)
	{
		$divstyle='';

		switch ($region) {
			case 'full': // Leaderboard
				$divstyle='width:728px; margin:0 auto;';
				// fall-through

			case 'main': // Leaderboard
				$width=728;
				$height=90;
				$format='728x90_as';
				break;

			case 'side': // Wide skyscraper
				$width=160;
				$height=600;
				$format='160x600_as';
				break;
		}

?>
<div style="<?php echo $divstyle?>">
	<script type="text/javascript">
	google_ad_client = <?php echo qa_js(qa_opt('adsense_publisher_id'))?>;
	google_ad_width = <?php echo qa_js($width)?>;
	google_ad_height = <?php echo qa_js($height)?>;
	google_ad_format = <?php echo qa_js($format)?>;
	google_ad_type = "text_image";
	google_ad_channel = "";
	</script>
	<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
</div>
<?php
	}
}
