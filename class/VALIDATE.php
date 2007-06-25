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

	// Generated with utf8_generate::quickCheck('NFC') . utf8_generate::combiningCheck()
	const utf8_NFC_quickCheck = '/[\x{0374}\x{037E}\x{0387}\x{0958}-\x{095F}\x{09DC}\x{09DD}\x{09DF}\x{0A33}\x{0A36}\x{0A59}-\x{0A5B}\x{0A5E}\x{0B5C}\x{0B5D}\x{0F43}\x{0F4D}\x{0F52}\x{0F57}\x{0F5C}\x{0F69}\x{0F73}\x{0F75}\x{0F76}\x{0F78}\x{0F81}\x{0F93}\x{0F9D}\x{0FA2}\x{0FA7}\x{0FAC}\x{0FB9}\x{1F71}\x{1F73}\x{1F75}\x{1F77}\x{1F79}\x{1F7B}\x{1F7D}\x{1FBB}\x{1FBE}\x{1FC9}\x{1FCB}\x{1FD3}\x{1FDB}\x{1FE3}\x{1FEB}\x{1FEE}\x{1FEF}\x{1FF9}\x{1FFB}\x{1FFD}\x{2000}\x{2001}\x{2126}\x{212A}\x{212B}\x{2329}\x{232A}\x{2ADC}\x{F900}-\x{FA0D}\x{FA10}\x{FA12}\x{FA15}-\x{FA1E}\x{FA20}\x{FA22}\x{FA25}\x{FA26}\x{FA2A}-\x{FA2D}\x{FA30}-\x{FA6A}\x{FA70}-\x{FAD9}\x{FB1D}\x{FB1F}\x{FB2A}-\x{FB36}\x{FB38}-\x{FB3C}\x{FB3E}\x{FB40}\x{FB41}\x{FB43}\x{FB44}\x{FB46}-\x{FB4E}\x{1D15E}-\x{1D164}\x{1D1BB}-\x{1D1C0}\x{2F800}-\x{2FA1D}\x{09D7}\x{0B3E}\x{0B56}\x{0B57}\x{0BBE}\x{0BD7}\x{0C56}\x{0CC2}\x{0CD5}\x{0CD6}\x{0D3E}\x{0D57}\x{0DCA}\x{0DCF}\x{0DDF}\x{102E}\x{1161}-\x{1175}\x{11A8}-\x{11C2}\x{1B35}\x{0300}-\x{034E}\x{0350}-\x{036F}\x{0483}-\x{0486}\x{0591}-\x{05BD}\x{05BF}\x{05C1}\x{05C2}\x{05C4}\x{05C5}\x{05C7}\x{0610}-\x{0615}\x{064B}-\x{065E}\x{0670}\x{06D6}-\x{06DC}\x{06DF}-\x{06E4}\x{06E7}\x{06E8}\x{06EA}-\x{06ED}\x{0711}\x{0730}-\x{074A}\x{07EB}-\x{07F3}\x{093C}\x{094D}\x{0951}-\x{0954}\x{09BC}-\x{09CE}\x{0A3C}\x{0A4D}\x{0ABC}\x{0ACD}\x{0B3C}\x{0B4D}\x{0BCD}\x{0C4D}\x{0C55}\x{0C56}\x{0CBC}\x{0CCD}\x{0D4D}\x{0DCA}\x{0E38}-\x{0E3A}\x{0E48}-\x{0E4B}\x{0EB8}\x{0EB9}\x{0EC8}-\x{0ECB}\x{0F18}\x{0F19}\x{0F35}\x{0F37}\x{0F39}\x{0F71}\x{0F72}\x{0F74}\x{0F7A}-\x{0F7D}\x{0F80}\x{0F82}-\x{0F84}\x{0F86}\x{0F87}\x{0FC6}\x{1037}\x{1039}\x{135F}\x{1714}\x{1734}\x{17D2}\x{17DD}\x{18A9}\x{1939}-\x{193B}\x{1A17}\x{1A18}\x{1B34}\x{1B44}\x{1B6B}-\x{1B73}\x{1DC0}-\x{1DCA}\x{1DFE}\x{1DFF}\x{20D0}-\x{20DC}\x{20E1}\x{20E5}-\x{20EF}\x{302A}-\x{302F}\x{3099}\x{309A}\x{A806}\x{FB1E}\x{FE20}-\x{FE23}\x{10A0D}\x{10A0F}\x{10A38}-\x{10A3A}\x{10A3F}\x{1D165}-\x{1D169}\x{1D16D}-\x{1D172}\x{1D17B}-\x{1D182}\x{1D185}-\x{1D18B}\x{1D1AA}-\x{1D1AD}\x{1D242}]/u';
	
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
