<?php
/**
* Copyright (c) 2006 Richard Heyes (http://www.phpguru.org/)
*
* All rights reserved.
*
* This script is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*/

/**
* Common functionality for console scripts
*/

class Console
{
	/**
	* Stdin file pointer
	*/
	public static $stdin;


	/**
	* Returns stdin file pointer
	*
	* @return resource STDIN file pointer
	*/
	private static function GetStdin()
	{
		if (!self::$stdin) {
			self::$stdin = fopen('php://stdin', 'r');
		}
		
		return self::$stdin;
	}


	/**
	* Pauses execution until enter is pressed
	*/
	public static function Pause()
	{
		
		fgets(self::GetStdin(), 8192);
	}
	
	
	/**
	* Asks a boolean style yes/no question. Valid input is:
	*
	*  o Yes: 1/y/yes/true
	*  o No:  0/n/no/false
	*
	* @param string $question The string to print. Should be a yes/no
	*                         question the user can answer. The following
	*                         will be appended to the question:
	*                         "[Yes]/No"
	* @param bool   $default  The default answer if only enter is pressed.
	*/
	public static function BooleanQuestion($question, $default = null)
	{
		if (!is_null($default)) {
			$defaultStr = $default ? '[Yes]/No' : 'Yes/[No]';
		} else {
			$defaultStr = 'Yes/No';
		}
		
		$fp = self::GetStdin();
		
		while (true) {
			echo $question, " ", $defaultStr, ": ";
			$response = trim(fgets($fp, 8192));
			
			if (!is_null($default) AND $response == '') {
				return $default;
			}

			switch (strtolower($response)) {
				case 'y':
				case '1':
				case 'yes':
				case 'true':
					return true;
				
				case 'n':
				case '0':
				case 'no':
				case 'false':
					return false;
				
				default:
					continue;
			}
		}
	}


	/**
	* Clears the screen. Specific to Linux (and possibly bash too)
	*/
	public static function ClearScreen()
	{
		echo chr(033), "c";
	}
	
	
	/**
	* Returns a line of input from the screen with the corresponding
	* LF character appended (if appropriate).
	*
	* @param int $buffer Line buffer. Defaults to 8192
	*/
	public static function GetLine($buffer = 8192)
	{
		return fgets(self::GetStdin(), $buffer);
	}


	/**
	* Shows a console menu
	*
	* @param array $items The menu items. Should be a two dimensional array. 2nd
	*                     dimensional should be associative containing the following
	*                     keys:
	*                      o identifier - The key/combo the user should enter to activate
	*                                     this menu. Usually a single character or number.
	*                                     This is lower cased when used for comparison, so
	*                                     mixing upper/lower case identifiers will not work.
	*                      o text       - The description associated with this menu item.
	*                      o callback   - Optional. If specified this callback is called
	*                                     using call_user_func(). If not specified then the
	*                                     identifier is returned instead, (after the callback
	*                                     has run the identifier is returned also). The callback
	*                                     is given one argument, which is the identifier of the
	*                                     menu item.
	* @param bool  $clear Whether to clear the screen before printing the menu.
	*                     Defaults to false.
	*/
	public static function ShowMenu($items, $clear = false)
	{
		// Find the longest identifier
		$max_length = 0;
		foreach ($items as $k => $v) {
			$identifiers[strtolower($v['identifier'])] = $k;
			$max_length  = max(strlen($v['identifier']), $max_length);
		}
		
		while (true) {
			if ($clear) {
				self::ClearScreen();
			}
			
			echo "Please select one of the following options:\n\n";
			
			// Print the menu
			foreach ($items as $k => $v) {
				echo str_pad($v['identifier'], $max_length, ' ', STR_PAD_LEFT), ") ", $v['text'], "\n";
			}
			
			echo "\nSelect: ";
			$input = trim(strtolower(self::GetLine()));

			// Invalid menu item chosen
			if (!isset($identifiers[$input])) {
				echo "Invalid menu item chosen...\n";
				sleep(1);
				continue;
			
			// Valid menu item chosen
			} else {
				$item = $items[$identifiers[$input]];
				if (!empty($item['callback']) AND is_callable($item['callback'])) {
					call_user_func($item['callback'], $input);
				}
				
				return $input;
			}
		}
	}
}
?>
