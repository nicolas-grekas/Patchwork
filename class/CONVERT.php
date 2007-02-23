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
		$adapter = self::getAdapter($from, $to);
		if (!$adapter) return false;

		return $adapter->file($file);
	}

	static function data($data, $from, $to)
	{
		$adapter = self::getAdapter($from, $to);
		if (!$adapter) return false;

		return $adapter->data($data);
	}

	protected static function getAdapter($from, $to)
	{
		$class = 'adapter_convertTo_' . $to . '_' . $from;
		if (preg_match("'[^a-zA-Z0-9_]'u", $class))
		{
			W('Wrong class name: ' . $class);
			return false;
		}

		if (class_exists($class)) return new $class;

		W('No defined adapter for this convertion: ' . $class);

		return false;
	}
}
