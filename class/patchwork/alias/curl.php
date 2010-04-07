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


class patchwork_alias_curl
{
	static function setopt($ch, $option, $value)
	{
		return self::setopt_array($ch, array($option => $value));
	}

	static function setopt_array($ch, $options)
	{
		foreach ($options as $k => &$v)
		{
			if (is_string($v)) switch ($k)
			{
			case CURLOPT_HEADERFUNCTION:   case CURLOPT_PASSWDFUNCTION:
			case CURLOPT_PROGRESSFUNCTION: case CURLOPT_READFUNCTION:
			case CURLOPT_WRITEFUNCTION:
				function_exists('__patchwork_' . $v) && $v = '__patchwork_' . $v;
			}
		}

		return curl_setopt_array($ch, $options);
	}
}
