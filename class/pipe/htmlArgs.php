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
	static function php($pool)
	{
		if (!$pool) return '';

		$except = func_get_args();
		$except = array_slice($except, 1);

		$result = '';
		foreach ($pool as $k => &$v)
		{
			if ('_'!=substr($k, 0, 1) && 'iteratorPosition'!=$k && strpos($k, '$')===false && !in_array($k, $except))
			{
				$result .= $k . '="' . patchwork::string($v) . '" ';
			}
		}

		return $result;
	}

	static function js()
	{
		?>/*<script>*/

P$htmlArgs = function($pool)
{
	if (!$pool) return '';
	var $result = '', $args = P$htmlArgs.arguments, $i = $args.length, $except = [];

	while (--$i) $except[$i] = $args[$i];
	$except = new RegExp('^(|'+$except.join('|')+')$');

	for ($i in $pool) if ('_'!=$i.substr(0, 1) && 'iteratorPosition'!=$i && $i.indexOf('$')<0 && $i.search($except)) $result += $i + '="' + $pool[$i] + '" ';

	return $result;
}

<?php	}
}
