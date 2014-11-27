<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/Q2A/Util/Metadata.php
	Description: Some useful metadata handling stuff


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

class Q2A_Util_Metadata
{

	const METADATA_FILE_JSON = 'metadata.json';

	/**
	 * Return an array from a JSON string
	 * @param mixed $json The JSON string to turn into an array
	 * @return array Always return an array containing the decoded JSON or an empty array in case the
	 * $json parameter is not a valid JSON string
	 */
	private function getArrayFromJson($json)
	{
		$result = json_decode($json, true);
		return is_array($result) ? $result : array();
	}

	/**
	 * Fetch metadata information from an addon path
	 * @param string $path Path to the addon
	 * @return array The metadata fetched from the metadata.json file in the addon path
	 */
	public function fetchFromAddonPath($path)
	{
		$metadataFile = $path . self::METADATA_FILE_JSON;
		if (is_file($metadataFile)) {
			$content = file_get_contents($metadataFile);
			return $this->getArrayFromJson($content);
		}
		return array();
	}

}