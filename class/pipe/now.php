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
		CIA::setMaxage(1);
		CIA::setExpires('onmaxage');
		return $_SERVER['REQUEST_TIME'];
	}

	static function js()
	{
		?>/*<script>*/

P$now = function()
{
	return parseInt(new Date/1000);
}

<?php	}
}
