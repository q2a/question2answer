<?php

class Q2A_Install_ProgressUpdater
{
	public function update($text)
	{
		echo qa_html($text) . str_repeat('    ', 1024) . "<br><br>\n";
		flush();
	}
}