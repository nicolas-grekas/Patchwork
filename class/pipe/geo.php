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
	static function php($geo, $latlong)
	{
		if ($geo = patchwork::string($geo))
		{
			$geo = round(100 * $geo);
			$geo = substr($geo, 0, -2) . '.' . substr($geo, -2);
		}
		else $geo = '0.00';

		return $geo;
	}

	static function js()
	{
		?>/*<script>*/

P$geo = function($geo, $latlong)
{
	if ($geo = str($geo))
	{
		$geo = '' + Math.round(100*$geo);
		$geo = $geo.substr(0, -2) + '.' + $geo.substr(-2);
	}
	else $geo = '0.00';

	return $geo;
}
<?php 	}
}
