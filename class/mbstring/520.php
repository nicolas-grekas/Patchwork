<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL, see LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


/*
 * Partial mbstring implementation in pure PHP
 * Functions introduced with PHP 5.2.0
 */

class
{
	static function stripos($haystack, $needle, $offset = 0, $encoding = null)
	{
		return mb_strpos(mb_strtolower($haystack, $encoding), mb_strtolower($needle, $encoding), $offset, $encoding);
	}

	static function stristr($haystack, $needle, $part = false, $encoding = null)
	{
		$pos = mb_stripos($haystack, $needle, $encoding);
		return false === $pos ? false : ($part ? mb_substr($haystack, 0, $pos, $encoding) : mb_substr($haystack, $pos, null, $encoding));
	}

	static function strrchr($haystack, $needle, $part = false, $encoding = null)
	{
		$pos = mb_strrpos($haystack, $needle, $encoding);
		return false === $pos ? false : ($part ? mb_substr($haystack, 0, $pos, $encoding) : mb_substr($haystack, $pos, null, $encoding));
	}

	static function strrichr($haystack, $needle, $part = false, $encoding = null)
	{
		$pos = mb_strripos($haystack, $needle, $encoding);
		return false === $pos ? false : ($part ? mb_substr($haystack, 0, $pos, $encoding) : mb_substr($haystack, $pos, null, $encoding));
	}

	static function strripos($haystack, $needle, $offset = 0, $encoding = null)
	{
		return mb_strrpos(mb_strtolower($haystack, $encoding), mb_strtolower($needle, $encoding), $offset, $encoding);
	}

	static function strstr($haystack, $needle, $part = false, $encoding = null)
	{
		$pos = strpos($haystack, $needle);
		return false === $pos ? false : ($part ? substr($haystack, 0, $pos) : substr($haystack, $pos));
	}
}
