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


abstract class
{
	static function file($file, $from, $to)
	{
		$driver = self::getDriver($from, $to);
		if (!$driver) return false;

		return $driver->file($file);
	}

	static function data($data, $from, $to)
	{
		$driver = self::getDriver($from, $to);
		if (!$driver) return false;

		return $driver->data($data);
	}

	protected static function getDriver($from, $to)
	{
		$class = 'driver_convertTo_' . $to . '_' . $from;
		if (preg_match("'[^a-zA-Z0-9_]'u", $class))
		{
			E('Disallowed classname: ' . $class);
			return false;
		}

		if (class_exists($class)) return new $class;

		E('No defined driver for this convertion: ' . $class);

		return false;
	}
}
