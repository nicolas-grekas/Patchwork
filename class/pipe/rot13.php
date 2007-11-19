<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($string)
	{
		return str_rot13(p::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$rot13 = function($string)
{
	$string = str($string);

	var $result = '', $len = $string.length, $i = 0, $b;

	for(; $i < $len; ++$i)
	{
		$b = $string.charCodeAt($i);

		if ((64 < $b && $b < 78) || (96 < $b && $b < 110))
		{
			$b += 13;
		}
		else if ((77 < $b && $b < 91) || (109 < $b && $b < 123))
		{
			$b -= 13;
		}

		$result += String.fromCharCode($b);
	}

	return $result;
}
<?php 	}
}
