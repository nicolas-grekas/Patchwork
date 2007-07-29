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
 *
 * Working only if iconv is loaded:

mb_convert_encoding           - Convert character encoding
mb_decode_mimeheader          - Decode string in MIME header field
mb_encode_mimeheader          - Encode string for MIME header


 * Not implemented:

mb_check_encoding             - Check if the string is valid for the specified encoding
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
		W('mb_convert_encoding() not supported without mbstring or iconv');
		return $str;
	}

	static function decode_mimeheader($str)
	{
		if (function_exists('iconv_mime_decode')) return iconv_mime_decode($str);
		W('mb_decode_mimeheader() not supported without mbstring or iconv');
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
		W('mb_encode_mimeheader() not supported without mbstring or iconv');
		return $str;
	}


	static function convert_case($str, $mode, $encoding = null)
	{
		switch ($mode)
		{
		case MB_CASE_UPPER: return self::strtoupper($str);
		case MB_CASE_LOWER: return self::strtolower($str);
		case MB_CASE_TITLE: return preg_replace("/\b./eu", 'self::strtoupper("$0",$encoding)', $str);
		}

		return $str;
	}

	static function list_encodings()
	{
		return array('UTF-8');
	}

	static function strlen($str, $encoding = null)
	{
		return strlen(utf8_decode($str));
	}

	static function strpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		if (function_exists('iconv_strpos')) return iconv_strpos($haystack, $needle, $offset);
		if ($offset = (int) $offset) $haystack = self::substr($haystack, $offset);
		$pos = strpos($haystack, $needle);
		return false === $pos ? false : ($offset + ($pos ? self::strlen(substr($haystack, 0, $pos)) : 0));
	}

	static function strtolower($str, $encoding = null)
	{
		static $table;
		isset($table) || $table = self::loadCaseTable(0);
		return strtr($str, $table);
	}

	static function strtoupper($str, $encoding = null)
	{
		static $table;
		isset($table) || $table = self::loadCaseTable(1);
		return strtr($str, $table);
	}

	static function substr($str, $start, $length = null, $encoding = null)
	{
		if (function_exists('iconv_substr')) return iconv_substr($str, $start, $length);

		$strlen = self::strlen($str);
		$start = (int) $start;

		if (0 > $start) $start += $strlen;
		if (0 > $start) $start = 0;
		if ($start >= $strlen) return '';

		$rx = $strlen - $start;

		if (null === $length) $length  = $rx;
		else if (0 > $length) $length += $rx;
		if (0 >= $length) return '';

		if ($length > $strlen - $start) $length = $rx;

		$rx = '/^' . ($start ? self::preg_offset($start) : '') . '(' . self::preg_offset($length) . ')/u';

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

	static function loadCaseTable($upper)
	{
		return unserialize(file_get_contents(
			$upper
				? resolvePath('data/utf8/upperCase.ser')
				: resolvePath('data/utf8/lowerCase.ser')
		));
	}
}
