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
	static function php($string, $start, $length = false)
	{
		return false == $length
			? substr(CIA::string($string), $start)
			: substr(CIA::string($string), $start, $length);
	}

	static function js()
	{
		?>/*<script>*/

P$substr = function($string, $start, $length)
{
	$string = str($string);
	return t($length)
		? $string.substr($start, $length)
		: $string.substr($start);
}

<?php	}
}
