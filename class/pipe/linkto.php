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
	static function php($text, $url = '', $attributes = '')
	{
		$text = p::string($text);
		$url = p::string($url);

		$a = strpos($url, '#');
		if (false !== $a)
		{
			$hash = substr($url, $a);
			$url = substr($url, 0, $a);
		}
		else $hash = '';

		return $url == htmlspecialchars(substr(p::__HOST__() . substr($_SERVER['REQUEST_URI'], 1), strlen(p::__BASE__())))
			? ('<b class="linkloop">' . $text . '</b>')
			: ('<a href="' . p::base($url, true) . $hash . '" ' . p::string($attributes) . '>' . $text . '</a>');
	}

	static function js()
	{
		?>/*<script>*/

P$linkto = function($text, $url, $attributes)
{
	$text = str($text);
	$url = str($url);

	var $a = $url.indexOf('#'), $hash;
	if ($a >= 0)
	{
		$hash = $url.substr($a);
		$url = $url.substr(0, $a);
	}
	else $hash = '';

	return $url == esc(''+location).substr( base('', 1, 1).length )
			? ('<b class="linkloop">' + $text + '</b>')
			: ('<a href="' + base($url, 1) + $hash + '" ' + str($attributes) + '>' + $text + '</a>');
}

<?php	}
}
