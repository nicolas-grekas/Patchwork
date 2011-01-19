<?php /*********************************************************************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_alias
{
	static function resolve($c)
	{
		if (is_string($c) && isset($c[0]))
		{
			if ('\\' === $c[0])
			{
				if (empty($c[1]) || '\\' === $c[1]) return $c;
				$c = substr($c, 1);
			}

			if (function_exists('__patchwork_' . strtr($c, '\\', '_')))
			{
				return '__patchwork_' . strtr($c, '\\', '_');
			}

/**/		if (version_compare(PHP_VERSION, '5.3.0') < 0)
				$c = strtr($c, '\\', '_');

/**/		if (version_compare(PHP_VERSION, '5.2.3') < 0)
				strpos($c, '::') && $c = explode('::', $c, 2);
		}
		else
		{
/**/		if (version_compare(PHP_VERSION, '5.3.0') < 0)
/**/		{
				if (is_array($c) && isset($c[0]))
					$c[0] = strtr($c[0], '\\', '_');
/**/		}
		}

		return $c;
	}

	static function scopedResolve($c, &$v)
	{
		$v = self::resolve($c);
/**/	if (version_compare(PHP_VERSION, '5.2.3') < 0)
			is_array($v) && is_string($c) && $v = implode('', $v);
		return "\xF7";
	}
}
