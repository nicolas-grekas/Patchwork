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
	static function php($string, $spacify_char = ' ')
	{
		preg_match_all("'.'u", patchwork::string($string), $string);
		return implode(patchwork::string($spacify_char), $string[0]);
	}

	static function js()
	{
		?>/*<script>*/

P$spacify = function($string, $spacify_char)
{
	return str($string).split('').join(str($spacify_char, ' '));
}

<?php	}
}
