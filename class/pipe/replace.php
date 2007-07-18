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
	static function php($string, $search, $replace, $caseInsensitive = false)
	{
		$search = preg_replace("/(?<!\\\\)((?:\\\\\\\\)*)@/", '$1\\@', patchwork::string($search));
		$caseInsensitive = patchwork::string($caseInsensitive) ? 'i' : '';
		return preg_replace("@{$search}@su{$caseInsensitive}", patchwork::string($replace), patchwork::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$replace = function($string, $search, $replace, $caseInsensitive)
{
	$search = new RegExp(str($search), 'g' + (str($caseInsensitive) ? 'i' : ''));
	return str($string).replace($search, str($replace));
}

<?php	}
}
