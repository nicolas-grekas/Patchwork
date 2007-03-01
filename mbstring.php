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


define('MB_CASE_UPPER', 0);
define('MB_CASE_LOWER', 1);
define('MB_CASE_TITLE', 2);

function mb_convert_encoding($str, $to_encoding, $from_encoding = 'UTF-8')
{
	return mbstring_500::convert_encoding($str, $to_encoding, $from_encoding);
}

function mb_decode_mimeheader($str)
{
	return mbstring_500::decode_mimeheader($str);
}

function mb_encode_mimeheader($str, $charset = 'UTF-8', $transfer_encoding = null, $linefeed = null, $indent = null)
{
	return mbstring_500::encode_mimeheader($str, $charset, $transfer_encoding, $linefeed, $indent);
}

function mb_convert_case($str, $mode, $encoding = null)
{
	return mbstring_500::convert_case($str, $mode, $encoding);
}

function mb_list_encodings()
{
	return mbstring_500::list_encodings();
}

function mb_parse_str($encoded_string, &$result = null, $encoding = null)
{
	return mbstring_500::parse_str($encoded_string, $result, $encoding);
}

function mb_strlen($str, $encoding = null)
{
	return mbstring_500::strlen($str, $encoding);
}

function mb_strpos($haystack, $needle, $offset = 0, $encoding = null)
{
	return mbstring_500::strpos($haystack, $needle, $offset, $encoding);
}

function mb_strrpos($haystack, $needle, $offset = 0, $encoding = null)
{
	return mbstring_500::strrpos($haystack, $needle, $offset, $encoding);
}

function mb_strtolower($str, $encoding = null)
{
	return mbstring_500::strtolower($str, $encoding);
}

function mb_strtoupper($str, $encoding = null)
{
	return mbstring_500::strtoupper($str, $encoding);
}

function mb_substr_count($haystack, $needle, $encoding = null)
{
	return mbstring_500::substr_count($haystack, $needle, $encoding);
}

function mb_substr($str, $start, $length = null, $encoding = null)
{
	return mbstring_500::substr($str, $start, $length, $encoding);
}
