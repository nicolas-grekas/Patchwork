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
	static function php($string)
	{
		patchwork::setMaxage(1);
		patchwork::setExpires('onmaxage');
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
