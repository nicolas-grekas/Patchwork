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
	static function php($g, $lang)
	{
		$url = $g->__LANG__ ? $g->__LANG__ : '__';
		$url = explode("/{$url}/", $g->__URI__, 2);
		$url = implode("/{$lang}/", $url);

		return $url;
	}

	static function js()
	{
		?>/*<script>*/

P$switchLang = function($g, $lang)
{
	return $g.__URI__.replace(new RegExp('/' + ($g.__LANG__ || '__') + '/'), '/' + $lang + '/');
}

<?php	}
}
