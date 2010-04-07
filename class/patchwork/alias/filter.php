<?php /*********************************************************************
 *
 *   Copyright : (C) 2010 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_alias_filter
{
	static function filter_var($var, $filter = FILTER_DEFAULT, $opt = INF)
	{
		if (INF === $opt) return filter_var($var, $filter);

		isset($opt['options'])
			&& FILTER_CALLBACK == $filter
			&& is_string($opt['options'])
			&& function_exists('__patchwork_' . $opt['options'])
			&& $opt['options'] = '__patchwork_' . $opt['options'];

		return filter_var($var, $filter, $opt);
	}

	static function var_array($data, $def = INF)
	{
		if (INF === $def) return filter_var_array($data);

		if (is_array($def)) foreach ($def as &$opt)
		{
			isset($opt['filter'], $opt['options'])
				&& FILTER_CALLBACK == $opt['filter']
				&& is_string($opt['options'])
				&& function_exists('__patchwork_' . $opt['options'])
				&& $opt['options'] = '__patchwork_' . $opt['options'];
		}

		return filter_var_array($data, $def);
	}
}
