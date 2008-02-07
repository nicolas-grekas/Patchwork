<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($s)
	{
		if (preg_match('/(\.[^.\/]+)+$/', p::string($s), $s))
		{
			$s = explode('.', $s[0]);
			$s = array_reverse($s);
			$s = implode('/', $s);
		}
		else $s = '';

		return strtolower($s);
	}

	static function js()
	{
		?>/*<script>*/

P$pStudio_extension = function($s)
{
	if ($s = str($s).match(/(\.[^.\/]+)+$/))
	{
		$s = $s[0].split('.');
		var $a = [], $i = $s.length;
		while ($i-- > 0) $a.push($s[$i]);
		$s = $a.join('/');
	}
	else $s = '';

	return $s.toLowerCase();
}

<?php	}
}
