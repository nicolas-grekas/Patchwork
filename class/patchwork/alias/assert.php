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


class patchwork_alias_assert
{
	static function options($what, $value = INF)
	{
		if (INF === $value) return assert_options($what);

		return ASSERT_CALLBACK == $value && is_string($value) && function_exists('__patchwork_' . $value)
			? assert_options($what, '__patchwork_' . $value)
			: assert_options($what, $value);
	}
}
