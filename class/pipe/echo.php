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


/* Both version (PHP & JS) of this pipe are bugged */

class
{
	static function php($format = '')
	{
		$args = func_get_args();

		if ($format == '') $args = implode('', $args);
		else $args = preg_replace("'%([0-9])'eu", 'isset($args[$1+1])?patchwork::string($args[$1+1]):""', patchwork::string($format));

		return $args;
	}

	static function js()
	{
		?>/*<script>*/

P$echo = function($format)
{
	$format = str($format);

	var $args = P$echo.arguments, $i = 1, $firstChar;

	if ($format != '')
	{
		$format = $format.split('%');

		for (; $i<$format.length; ++$i)
		{
			$firstChar = $format[$i].substr(0, 1);
			if ($firstChar.length && $firstChar != '%')
			{
				if (0 <= $firstChar && $firstChar <= 9) $format[$i] = str($args[$firstChar/1+1]) + $format[$i].substr(1);
				else $format[$i] = '%' + $format[$i];
			}
		}
	}
	else
	{
		$format = [];
		for (; $i<$args.length; ++$i) $format[$i] = $args[$i];
	}

	return $format.join('');
}

<?php	}
}
