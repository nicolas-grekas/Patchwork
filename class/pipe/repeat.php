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
	static function php($string, $num)
	{
		return str_repeat(patchwork::string($string), patchwork::string($num));
	}

	static function js()
	{
		?>/*<script>*/

P$repeat = function($string, $num)
{
	var $str = '';
	$string = str($string);
	while (--$num>=0) $str += $string;
	return $str;
}

<?php	}
}
