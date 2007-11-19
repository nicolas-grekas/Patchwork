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
	static $pool = array();

	static function php($name)
	{
		$name = p::string($name);
		$args = func_get_args();
		$key =& self::$pool[$name];

		if (is_int($key))
		{
			if (++$key >= count($args)) $key = 1;
		}
		else $key = 1;

		return p::string($args[$key]);
	}

	static function js()
	{
		?>/*<script>*/

P$cycle = function($name)
{
	$name = str($name);
	var $args = P$cycle.arguments,
		$pool = cyclePool;

	if (t($pool[$name]))
	{
		if (++$pool[$name] >= $args.length) $pool[$name] = 1;
	}
	else $pool[$name] = 1;

	return str($args[$pool[$name]]);
}

cyclePool = [];

<?php	}
}
