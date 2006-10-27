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
	static function php($string, $length = 80, $etc = '...', $break_words = false)
	{
		$string = CIA::string($string);
		$length = CIA::string($length);
		$etc = CIA::string($etc);
		$break_words = CIA::string($break_words);

		if (!$length) return '';

		if (mb_strlen($string) > $length)
		{
			$length -= mb_strlen($etc);
			if (!$break_words) $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1));

			return mb_substr($string, 0, $length) . $etc;
		}

		return $string;
	}

	static function js()
	{
		?>/*<script>*/

P$truncate = function($string, $length, $etc, $break_words)
{
	$string = str($string);
	$length = str($length, 80);
	$etc = str($etc, '...');

	if (!$length) return '';

	if ($string.length > $length)
	{
		$length -= strlen($etc);
		if (!str($break_words)) $string = $string.substr(0, $length + 1).replace(/\s+?(\S+)?$/g, '');

		return substr($string, 0, $length) . $etc;
	}

	return $string;
}

<?php	}
}
