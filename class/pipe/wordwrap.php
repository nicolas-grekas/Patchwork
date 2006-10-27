<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($string, $length = 80, $break = "\n", $cut = false)
	{
		return wordwrap(CIA::string($string), CIA::string($length), CIA::string($break), CIA::string($cut));
	}

	static function js()
	{
		?>/*<script>*/

P$wordwrap = function($string, $length, $break, $cut)
{
	$cut = str($cut);
	$break = str($break, "\n");
	$length = str($length, 80);
	$string = str($string).split($break);

	var $i = 0,
		$j, $line, $a, $b;

	for (; $i<$string.length; ++$i)
	{
		$line = '';
		$a = $string[$i].split(' ');

		for ($j = 0; $j<$a.length; ++$j)
		{
			$b = $a[$j] || ' ';
			if ($line.length + $b.length <= $length) $line += $b;
			else
			{
				$line += $break;
				if ($b != ' ' && $cut)
				{
					while ($b.length>$length);
					{
						$line += $b.substr(0, $length);
						$b = $b.substr($length);
					}
					$line += $b.substr(0, $length);
				}
			}
		}

		$string[$i] = $line;
	}

	return $string.join($break);
}

<?php	}
}
