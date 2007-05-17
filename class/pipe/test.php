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
	static function php($test, $ifData, $elseData = '')
	{
		return patchwork::string($test) ? $ifData : $elseData;
	}

	static function js()
	{
		?>/*<script>*/

P$test = function($test, $ifData, $elseData)
{
	return num(str($test), 1) ? $ifData : $elseData;
}

<?php	}
}
