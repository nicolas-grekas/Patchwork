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


class
{
	function set_event_handler($a, $b, $c = INF) // More args possible, see doc.
	{
		$a = func_get_args();

		if (is_resource($a[0])) $b =& $a[1];
		else $b =& $a[0];

		is_string($b)
			&& function_exists('__patchwork_' . $b)
			&& $b = '__patchwork_' . $b;

		if (false !== $c = array_search(INF, $a, true))
		{
			$a = array_slice($a, 0, $c);
		}

		return call_user_func_array('ibase_set_event_handler', $a);
	}
}
