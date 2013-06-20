<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/basic-adsense/qa-basic-adsense.php
	Version: See define()s at top of qa-include/qa-base.php
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

	class qa_basic_adsense {
		
		function allow_template($template)
		{
			return ($template!='admin');
		}

		
		function allow_region($region)
		{
			$allow=false;
			
			switch ($region)
			{
				case 'main':
				case 'side':
				case 'full':
					$allow=true;
					break;
			}
			
			return $allow;
		}

		
		function admin_form(&$qa_content)
		{
			$saved=false;
			
			if (qa_clicked('adsense_save_button')) {	
				$trimchars="=;\"\' \t\r\n"; // prevent common errors by copying and pasting from Javascript
				qa_opt('adsense_publisher_id', trim(qa_post_text('adsense_publisher_id_field'), $trimchars));
				$saved=true;
			}
			
			return array(
				'ok' => $saved ? 'AdSense settings saved' : null,
				
				'fields' => array(
					array(
						'label' => 'AdSense Publisher ID:',
						'value' => qa_html(qa_opt('adsense_publisher_id')),
						'tags' => 'name="adsense_publisher_id_field"',
						'note' => 'Example: <i>pub-1234567890123456</i>',
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'name="adsense_save_button"',
					),
				),
			);
		}


		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
			$divstyle='';
			
			switch ($region) {
				case 'full': // Leaderboard
					$divstyle='width:728px; margin:0 auto;';
					
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
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
</div>
<?php
		}
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/