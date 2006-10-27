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
		$string = htmlspecialchars( CIA::string($string) );

		return '<a href="mailto:'
			. str_replace('@', '[&#97;t]', $string) . '">'
			. str_replace('@', '<span style="display:none">@</span>&#64;', $string)
			. '</a>';
	}

	static function js()
	{
		?>/*<script>*/

P$mailto = function($string)
{
	$string = esc( str($string) );

	return '<a href="mailto:' + $string + '">' + $string + '</a>';
}

<?php	}
}
