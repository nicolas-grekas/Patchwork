<?php

class VALIDATE
{
	public static $IMAGETYPE = array(
		1 => 'gif', 'jpg', 'png',
		5 => 'psd', 'bmp', 'tif', 'tif', 'jpc', 'jp2', 'jpx', 'jb2', 'swc', 'iff'
	);

	public static function get(&$value, $type, $args = array())
	{
		$type = "get_$type";
		return $value !== null ? self::$type($value, $args) : '';
	}

	public static function getFile(&$value, $type, $args = array())
	{
		if (!is_array($value)) return '';

		if ($value['error']==4) return '';
		if ($value['error']) return false;

		$type = "getFile_$type";
		return self::$type($value, $args);
	}


	# no args
	private static function get_bool(&$value, &$args)
	{
		return $value ? 1 : 0;
	}

	# min, max
	private static function get_int(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = trim($value);
		if (!preg_match("'^[+-]?[0-9]+$'u", $result)) return false;
		if (isset($args[0]) && $result < $args[0]) return false;
		if (isset($args[1]) && $result > $args[1]) return false;

		return (int) $result;
	}

	# min, max
	private static function get_float(&$value, &$args)
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
	private static function get_in_array(&$value, &$args)
	{
		return in_array($value, $args[0]) ? $value : false;
	}

	# regexp, trim
	private static function get_string(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = (string) $value;
		if (!isset($args[1]) || $args[1]) $result = trim($result);
		if (isset($args[0]) && $args[0] && !preg_match($args[0].'u', $result, $args[2])) return false;

		return $result;
	}

	# no args
	private static function get_email(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = trim($value);

		$rx = '[-+_a-zA-Z0-9]+';
		$rx = "$rx(?:\\.$rx)*";

		if ( !preg_match("'^$rx@($rx)$'u", $result, $domain) ) return false;
		if ( function_exists('checkdnsrr') && !checkdnsrr($domain[1]) ) return false;
		return $result;
	}

	# no args
	private static function get_date(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$r = trim($value);

		$Y = date('Y');
		$r = preg_replace("'^[^0-9]+'u", '', $r);
		$r = preg_replace("'[^0-9]+$'u", '', $r);
		$r = preg_split("'[^0-9]+'u", $r);
		if (count($r) == 2) $r[2] = $Y;
		if (count($r) != 3) return false;
		if ($r[2]<100)
		{
			$r[2] += 1900;
			if ($Y - $r[2] > 50) $r[2] += 100;
		}

		return date("d-M-Y", mktime (0, 0, 0, $r[1], $r[0], $r[2]));
	}

	# size (octet), regexp
	private static function get_file(&$value, &$args)
	{	
		if (isset($args[1]) && $args[1])
		{
			$result = array($args[1], false);
			$result = self::get_string($value, $result);
			if ($result === false) return false;
		}

		if (isset($args[0]) && $args[0])
		{
			$s = @filesize($result);
			if ($s===false || ($args[0] && $s > $args[0])) return false;
		}

		return $result;
	}

	# size (octet), regexp, type, max_width, max_height, min_width, min_height
	private static function get_image(&$value, &$args)
	{
		$type = @$args[2];
		$max_width = @$args[3];
		$max_height = @$args[4];
		$min_width = @$args[5];
		$min_height = @$args[6];

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
	private static function getFile_file(&$value, &$args)
	{
		if (isset($args[0]) && $args[0])
		{
			$a = array($args[0]);
			if ( false === self::get_file($value['tmp_name'], $a) ) return false;
		}

		if (isset($args[1]) && $args[1])
		{
			$a = array(0, $args[1]);
			if ( false === self::get_file($value['name'], $a) ) return false;
		}

		return $value;
	}

	# size (octet), regexp, type, max_width, max_height, min_width, min_height
	private static function getFile_image(&$value, &$args)
	{
		$a = array(0, @$args[1]);
		$args[1] = false;

		if ( false === self::get_image($value['tmp_name'], $args) ) return false;
		if ( false === self::get_file($value['name'], $a) ) return false;

		return $value;
	}
}
