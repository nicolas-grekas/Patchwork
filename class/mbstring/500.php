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


/*
 * Partial mbstring implementation in pure PHP
 *
 * Working only if iconv is loaded :

mb_convert_encoding           - Convert character encoding
mb_decode_mimeheader          - Decode string in MIME header field
mb_encode_mimeheader          - Encode string for MIME header


 * Not implemented :

mb_check_encoding             - Check if the string is valid for the specified encoding
Note: considering UTF-8, preg_match("''u", $var) is roughly equivalent but 10 times faster than mb_check_encoding($var)

mb_convert_kana               - Convert "kana" one from another ("zen-kaku", "han-kaku" and more)
mb_convert_variables          - Convert character code in variable(s)
mb_decode_numericentity       - Decode HTML numeric string reference to character
mb_detect_encoding            - Detect character encoding
mb_detect_order               - Set/Get character encoding detection order
mb_encode_numericentity       - Encode character to HTML numeric string reference
mb_ereg_match                 - Regular expression match for multibyte string
mb_ereg_replace               - Replace regular expression with multibyte support
mb_ereg_search_getpos         - Returns start point for next regular expression match
mb_ereg_search_getregs        - Retrieve the result from the last multibyte regular expression match
mb_ereg_search_init           - Setup string and regular expression for multibyte regular expression match
mb_ereg_search_pos            - Return position and length of matched part of multibyte regular expression for predefined multibyte string
mb_ereg_search_regs           - Returns the matched part of multibyte regular expression
mb_ereg_search_setpos         - Set start point of next regular expression match
mb_ereg_search                - Multibyte regular expression match for predefined multibyte string
mb_ereg                       - Regular expression match with multibyte support
mb_eregi_replace              - Replace regular expression with multibyte support ignoring case
mb_eregi                      - Regular expression match ignoring case with multibyte support
mb_get_info                   - Get internal settings of mbstring
mb_http_input                 - Detect HTTP input character encoding
mb_http_output                - Set/Get HTTP output character encoding
mb_internal_encoding          - Set/Get internal character encoding
mb_language                   - Set/Get current language
mb_list_encodings_alias_names - Returns an array of all supported alias encodings
mb_list_mime_names            - Returns an array or string of all supported mime names
mb_output_handler             - Callback function converts character encoding in output buffer
mb_preferred_mime_name        - Get MIME charset string
mb_regex_encoding             - Returns current encoding for multibyte regex as string
mb_regex_set_options          - Set/Get the default options for mbregex functions
mb_send_mail                  - Send encoded mail
mb_split                      - Split multibyte string using regular expression
mb_strcut                     - Get part of string
mb_strimwidth                 - Get truncated string with specified width
mb_strwidth                   - Return width of string
mb_substitute_character       - Set/Get substitution character

 */

class
{
	static function convert_encoding($str, $to_encoding, $from_encoding = null)
	{
		if (function_exists('iconv')) return iconv($from_encoding ? $from_encoding : 'UTF-8', $to_encoding, $str);
		W(__FUNCTION__ . '() not supported on this configuration');
		return $str;
	}

	static function decode_mimeheader($str)
	{
		if (function_exists('iconv_mime_decode')) return iconv_mime_decode($str);
		W(__FUNCTION__ . '() not supported on this configuration');
		return $str;
	}

	static function encode_mimeheader($str, $charset = null, $transfer_encoding = null, $linefeed = null, $indent = null)
	{
		if (function_exists('iconv_mime_encode')) return iconv_mime_encode('', $str, array(
			'scheme' => null === $transfer_encoding ? 'B' : $transfer_encoding,
			'input-charset' => $charset ? $charset : 'UTF-8',
			'output-charset' => $charset ? $charset : 'UTF-8',
			'line-length' => 76,
			'line-break-chars' => null === $linefeed ? "\r\n" : $linefeed,
		));
		W(__FUNCTION__ . '() not supported on this configuration');
		return $str;
	}


	static function convert_case($str, $mode, $encoding = null)
	{
		switch ($mode)
		{
		case MB_CASE_UPPER: return mb_strtoupper($str);
		case MB_CASE_LOWER: return mb_strtolower($str);
		case MB_CASE_TITLE: return preg_replace("/\b./eu", 'mb_strtoupper("$0",$encoding)', $str);
		}

		return $str;
	}

	static function list_encodings()
	{
		return array('UTF-8');
	}

	static function strlen($str, $encoding = null)
	{
		return function_exists('iconv_strlen') ? iconv_strlen($str) : strlen(utf8_decode($str));
	}

	static function strpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		if (function_exists('iconv_strpos')) return iconv_strpos($haystack, $needle, $offset);
		if ($offset = (int) $offset) $haystack = mb_substr($haystack, $offset);
		$pos = strpos($haystack, $needle);
		return false === $pos ? false : ($offset + ($pos ? mb_strlen(substr($haystack, 0, $pos)) : 0));
	}

	static function strrpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		if ($offset = (int) $offset) $haystack = mb_substr($haystack, $offset);
		$needle = mb_substr($needle, 0, 1);
		$pos = strpos(strrev($haystack), strrev($needle));
		return false === $pos ? false : ($offset + mb_strlen($pos ? substr($haystack, 0, -$pos) : $haystack));
	}

	static function strtolower($str, $encoding = null)
	{
		static $table;
		isset($table) || $table = unserialize(file_get_contents(resolvePath('data/toLowerCase.ser'));
		return strtr($str, $table);
	}

	static function strtoupper($str, $encoding = null)
	{
		static $table;
		isset($table) || $table = unserialize(file_get_contents(resolvePath('data/toUpperCase.ser'));
		return strtr($str, $table);
	}

	static function substr($str, $start, $length = null, $encoding = null)
	{
		if (function_exists('iconv_substr')) return iconv_substr($str, $start, $length);

		$strlen = mb_strlen($str);
		$start = (int) $start;

		if (0 > $start) $start += $strlen;
		if (0 > $start) $start = 0;
		if ($start >= $strlen) return '';

		$rx = $strlen - $start;

		if (null === $length) $length  = $rx;
		else if (0 > $length) $length += $rx;
		if (0 >= $length) return '';

		if ($length > $strlen - $start) $length = $rx;

		$rx = '/^' . (0 > $start ? '' : self::preg_offset($start)) . '(' . self::preg_offset($length) . ')/u';

		return preg_match($rx, $str, $str) ? $str[1] : '';
	}

	static function preg_offset($offset)
	{
		$rx = array();
		$offset = (int) $offset;

		while ($offset > 65535)
		{
			$rx[] = '(?:.{65535})';
			$offset -= 65535;
		}

		return implode('', $rx) . '(?:.{' . $offset . '})';
	}
}
