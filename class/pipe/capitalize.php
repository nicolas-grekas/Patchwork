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
	static function php($string)
	{
		return preg_replace("/\b./eu", "u::strtoupper('$0')", patchwork::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$capitalize = function($string)
{
	$string = str($string).split(/\b/g);

	var $i = $string.length;
	while ($i--) $string[$i] = $string[$i].substr(0,1).toUpperCase() + $string[$i].substr(1);

	return $string.join('');
}
<?php 	}
}
