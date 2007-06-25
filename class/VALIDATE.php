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
	// This RegExp must work in most Javascript implementation too
	const email_rx = '[-+=_a-zA-Z0-9%]+(\\.[-+=_a-zA-Z0-9%]+)*@([-+=_a-zA-Z0-9%]+(\\.[-+=_a-zA-Z0-9%]+)*)';

	// Generated with utf8_generate::quickCheck('NFC')
	const utf8_NFC_quickCheck = '/[\x{300}-\x{34e}\x{350}-\x{36f}\x{374}\x{37e}\x{387}\x{483}-\x{486}\x{591}-\x{5bd}\x{5bf}\x{5c1}\x{5c2}\x{5c4}\x{5c5}\x{5c7}\x{610}-\x{615}\x{64b}-\x{65e}\x{670}\x{6d6}-\x{6dc}\x{6df}-\x{6e4}\x{6e7}\x{6e8}\x{6ea}-\x{6ed}\x{711}\x{730}-\x{74a}\x{7eb}-\x{7f3}\x{93c}\x{94d}\x{951}-\x{954}\x{958}-\x{95f}\x{9bc}\x{9be}\x{9cd}\x{9d7}\x{9dc}\x{9dd}\x{9df}\x{a33}\x{a36}\x{a3c}\x{a4d}\x{a59}-\x{a5b}\x{a5e}\x{abc}\x{acd}\x{b3c}\x{b3e}\x{b4d}\x{b56}\x{b57}\x{b5c}\x{b5d}\x{bbe}\x{bcd}\x{bd7}\x{c4d}\x{c55}\x{c56}\x{cbc}\x{cc2}\x{ccd}\x{cd5}\x{cd6}\x{d3e}\x{d4d}\x{d57}\x{dca}\x{dcf}\x{ddf}\x{e38}-\x{e3a}\x{e48}-\x{e4b}\x{eb8}\x{eb9}\x{ec8}-\x{ecb}\x{f18}\x{f19}\x{f35}\x{f37}\x{f39}\x{f43}\x{f4d}\x{f52}\x{f57}\x{f5c}\x{f69}\x{f71}-\x{f76}\x{f78}\x{f7a}-\x{f7d}\x{f80}-\x{f84}\x{f86}\x{f87}\x{f93}\x{f9d}\x{fa2}\x{fa7}\x{fac}\x{fb9}\x{fc6}\x{102e}\x{1037}\x{1039}\x{1161}-\x{1175}\x{11a8}-\x{11c2}\x{135f}\x{1714}\x{1734}\x{17d2}\x{17dd}\x{18a9}\x{1939}-\x{193b}\x{1a17}\x{1a18}\x{1b34}\x{1b35}\x{1b44}\x{1b6b}-\x{1b73}\x{1dc0}-\x{1dca}\x{1dfe}\x{1dff}\x{1f71}\x{1f73}\x{1f75}\x{1f77}\x{1f79}\x{1f7b}\x{1f7d}\x{1fbb}\x{1fbe}\x{1fc9}\x{1fcb}\x{1fd3}\x{1fdb}\x{1fe3}\x{1feb}\x{1fee}\x{1fef}\x{1ff9}\x{1ffb}\x{1ffd}\x{2000}\x{2001}\x{20d0}-\x{20dc}\x{20e1}\x{20e5}-\x{20ef}\x{2126}\x{212a}\x{212b}\x{2329}\x{232a}\x{2adc}\x{302a}-\x{302f}\x{3099}\x{309a}\x{a806}\x{f900}-\x{fa0d}\x{fa10}\x{fa12}\x{fa15}-\x{fa1e}\x{fa20}\x{fa22}\x{fa25}\x{fa26}\x{fa2a}-\x{fa2d}\x{fa30}-\x{fa6a}\x{fa70}-\x{fad9}\x{fb1d}-\x{fb1f}\x{fb2a}-\x{fb36}\x{fb38}-\x{fb3c}\x{fb3e}\x{fb40}\x{fb41}\x{fb43}\x{fb44}\x{fb46}-\x{fb4e}\x{fe20}-\x{fe23}\x{10a0d}\x{10a0f}\x{10a38}-\x{10a3a}\x{10a3f}\x{1d15e}-\x{1d169}\x{1d16d}-\x{1d172}\x{1d17b}-\x{1d182}\x{1d185}-\x{1d18b}\x{1d1aa}-\x{1d1ad}\x{1d1bb}-\x{1d1c0}\x{1d242}\x{2f800}]/u';
	
	static $IMAGETYPE = array(
		1 => 'gif', 'jpg', 'png',
		5 => 'psd', 'bmp', 'tif', 'tif', 'jpc', 'jp2', 'jpx', 'jb2', 'swc', 'iff'
	);

	static function get(&$value, $type, $args = array())
	{
		$type = "get_$type";
		return self::$type($value, $args);
	}

	static function getFile(&$value, $type, $args = array())
	{
		if (!is_array($value)) return '';

		if ($value['error']==4) return '';
		if ($value['error']) return false;

		if ('image/pjpeg' == $value['type']) $value['type'] = 'image/jpeg';

		$type = "getFile_$type";
		return self::$type($value, $args);
	}


	# no args
	protected static function get_bool(&$value, &$args)
	{
		return (string) (bool) $value;
	}

	# min, max
	protected static function get_int(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = trim($value);
		if (!preg_match("'^[+-]?[0-9]+$'u", $result)) return false;
		if (isset($args[0]) && $result < $args[0]) return false;
		if (isset($args[1]) && $result > $args[1]) return false;

		return (int) $result;
	}

	# min, max
	protected static function get_float(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$rx = '(?:(?:\d*\.\d+)|(?:\d+\.\d*))';
		$rx = "(?:[+-]\s*)?(?:(?:\d+|$rx)[eE][+-]?\d+|$rx|[1-9]\d*|0[xX][\da-fA-F]+|0[0-7]*)(?!\d)";

		$result = str_replace(',', '.', trim($value));
		if (!preg_match("'^$rx$'u", $result)) return false;
		if (isset($args[0]) && $result < $args[0]) return false;
		if (isset($args[1]) && $result > $args[1]) return false;

		return (float) $result;
	}

	# array
	protected static function get_in_array(&$value, &$args)
	{
		return in_array($value, $args[0]) ? $value : false;
	}

	# regexp
	protected static function get_string(&$value, &$args)
	{
		if (!is_scalar($value)) return false;
		$result = trim((string) $value);
		return self::get_raw($result, $args);
	}

	# regexp
	protected static function get_raw(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', $value);
		false !== strpos($result, "\r") && $result = strtr(str_replace("\r\n", "\n", $result), "\r", "\n");

		preg_match(self::utf8_NFC_quickCheck, $result) && $result = utf8_normalize::toNFC($result);

		if (isset($args[0]))
		{
			$rx = implode(':', $args);
			$rx = preg_replace("/(?<!\\\\)((?:\\\\\\\\)*)@/", '$1\\@', $rx);
			if (!preg_match("@^{$rx}$@Dsu", $result)) return false;
		}

		return $result;
	}

	# no args
	protected static function get_email(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = trim($value);

		if ( !preg_match("'^" . self::email_rx . "$'u", $result, $domain) ) return false;
		if ( function_exists('checkdnsrr') && !checkdnsrr($domain[2]) ) return false;
		return $result;
	}

	# (bool) international
	protected static function get_phone(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$r = preg_replace("'[^+0-9]+'u", '', $value);
		$r = preg_replace("'^00'u", '+', $r);

		if (!preg_match("'^\+?[0-9]{4,}$'u", $r)) return false;
		if (isset($args[0]) && $args[0] && strpos($r, '+')!==0) return false;

		return $r;
	}

	# no args
	protected static function get_date(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$r = trim($value);

		if ('0000-00-00' == $r) return $value = '';

		$r = preg_replace("'^(\d{4})-(\d{2})-(\d{2})$'u", '$3-$2-$1', $r);

		$Y = date('Y');
		$r = preg_replace("'^[^0-9]+'u", '', $r);
		$r = preg_replace("'[^0-9]+$'u", '', $r);
		$r = preg_split("'[^0-9]+'u", $r);

		if (2 == count($r)) $r[2] = $Y;
		else if (1 == count($r))
		{
			$r = $r[0];
			switch (strlen($r))
			{
				case 4:
				case 6:
				case 8:
					$r = array(
						substr($r, 0, 2),
						substr($r, 2, 2),
						substr($r, 4)
					);

					if (!$r[2]) $r[2] = $Y;

					break;

				default: return false;
			}
		}

		if (3 != count($r)) return false;
		if ($r[2]<100)
		{
			$r[2] += 1900;
			if ($Y - $r[2] > 80) $r[2] += 100;
		}

		if (31 < $r[0] || 12 < $r[1]) return false;

		return sprintf('%02d-%02d-%04d', $r[0], $r[1], $r[2]);
	}

	# size (octet), regexp
	protected static function get_file(&$value, &$args)
	{
		$result = $value;

		if (isset($args[1]) && $args[1])
		{
			$result = array($args[1]);
			$result = self::get_raw($value, $result);
			if (false === $result) return false;
		}

		if (isset($args[0]) && $args[0])
		{
			$s = @filesize($result);
			if (false === $s || ($args[0] && $s > $args[0])) return false;
		}

		return $result;
	}

	# size (octet), regexp, type, max_width, max_height, min_width, min_height
	protected static function get_image(&$value, &$args)
	{
		$type =       isset($args[2]) ? $args[2] : 0;
		$max_width =  isset($args[3]) ? $args[3] : 0;
		$max_height = isset($args[4]) ? $args[4] : 0;
		$min_width =  isset($args[5]) ? $args[5] : 0;
		$min_height = isset($args[6]) ? $args[6] : 0;

		$result = self::get_file($value, $args);

		if ($result === false) return false;

		$size = @getimagesize($result);
		if (is_array($size))
		{
			if ($max_width && $size[0]>$max_width) return false;
			if ($min_width && $size[0]<$min_width) return false;
			if ($max_height && $size[1]>$max_height) return false;
			if ($min_height && $size[1]<$min_height) return false;

			if ($type && !in_array(self::$IMAGETYPE[$size[2]], (array) $type)) return false;

			$args[7] =& $size;
			return $result;
		}
		else return false;
	}


	# size (octet), regexp
	protected static function getFile_file(&$value, &$args)
	{
		if (isset($args[0]) && $args[0])
		{
			$a = array($args[0]);
			if ( false === self::get_file($value['tmp_name'], $a) ) return false;
		}

		if (isset($args[1]) && $args[1])
		{
			$value['name'] = basename(strtr($value['name'], "\\\0", '/_'));

			$a = array(0, $args[1]);
			if ( false === self::get_file($value['name'], $a) ) return false;
		}

		return $value;
	}

	# size (octet), regexp, type, max_width, max_height, min_width, min_height
	protected static function getFile_image(&$value, &$args)
	{
		$a = array(0, isset($args[1]) ? $args[1] : '');
		$args[1] = false;

		if ( false === self::get_image($value['tmp_name'], $args) ) return false;
		if ( false === self::get_file($value['name'], $a) ) return false;

		$type =& $args[7][2];
		$type = self::$IMAGETYPE[$type];

		$value['info'] = $args[7];

		return $value;
	}
}
