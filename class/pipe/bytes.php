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
	static function php($byte)
	{
		$byte = patchwork::string($byte);

		$suffix = ' Ko';

		if ($byte >= ($div=1073741824)) $suffix = ' Go';
		else if ($byte >= ($div=1048576)) $suffix = ' Mo';
		else $div = 1024;

		$byte /= $div;
		$div = $byte < 10 ? 100 : 1;
		$byte = intval($div*$byte)/$div;

		return $byte . $suffix;
	}

	static function js()
	{
		?>/*<script>*/

P$bytes = function($byte)
{
	$byte = str($byte);
	var $suffix = ' Ko', $div;

	if ($byte >= ($div=1073741824)) $suffix = ' Go';
	else if ($byte >= ($div=1048576)) $suffix = ' Mo';
	else $div = 1024;

	$byte /= $div;
	$div = $byte < 10 ? 100 : 1;
	$byte = parseInt($div*$byte)/$div;

	return $byte + $suffix;
}

<?php	}
}
