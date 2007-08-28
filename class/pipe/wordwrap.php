<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($string, $length = 75, $break = "\n", $cut = true)
	{
		// The native PHP wordwrap() is not UTF-8 aware

		$cut = patchwork::string($cut);
		$break = patchwork::string($break);
		$length = (int) patchwork::string($length);
		$string = explode($break, patchwork::string($string));

		$iLen = count($string);
		$result = array();
		$line = '';

		for ($i = 0; $i < $iLen; ++$i)
		{
			$a = explode(' ', $string[$i]);
			$line && $result[] = $line;
			$line = $a[0];
			$jLen = count($a);

			for ($j = 1; $j < $jLen; ++$j)
			{
				$b = $a[$j];

				if (mb_strlen($line) + mb_strlen($b) < $length) $line .= ' ' . $b;
				else
				{
					$result[] = $line;
					$line = '';

					if ($cut) while (mb_strlen($b) > $length)
					{
						$line = mb_substr($b, $length);
						$result[] = mb_substr($b, 0, $length);
						$b = $line;
					}

					if ($b) $line = $b;
				}
			}
		}

		$line && $result[] = $line;

		return implode($break, $result);
	}

	static function js()
	{
		?>/*<script>*/

P$wordwrap = function($string, $length, $break, $cut)
{
	$cut = str($cut, 1);
	$break = str($break, '\n');
	$length = str($length, 80);
	$string = str($string).split($break);

	var $i = 0, $line,
		$j, $a, $b
		$result = [];

	for (; $i < $string.length; ++$i)
	{
		$a = $string[$i].split(' ');
		$line && $result.push($line);
		$line = $a[0];

		for ($j = 1; $j < $a.length; ++$j)
		{
			$b = $a[$j];

			if ($line.length + $b.length < $length) $line += ' ' + $b;
			else
			{
				$result.push($line);
				$line = '';

				if ($cut) while ($b.length > $length)
				{
					$line = $b.substr($length);
					$result.push($b.substr(0, $length));
					$b = $line;
				}

				if ($b) $line = $b;
			}
		}
	}

	$line && $result.push($line);

	return $result.join($break);
}

<?php	}
}
