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
	static function php($string, $chars = 4, $char = ' ')
	{
		$chars = str_repeat(patchwork::string($char), patchwork::string($chars));

		return $chars . str_replace("\n", "\n$chars", patchwork::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$indent = function($string, $chars, $char)
{
	$string = str($string);
	$chars = str($chars, 4);
	$char = str($char, ' ');

	var $char_repeated = $char;
	while (--$chars) $char_repeated += $char;

	return $char_repeated + $string.replace(/\n/g, '\n' + $char_repeated);
}

<?php	}
}
