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


class patchwork_alias_strings
{
	static function htmlspecialchars($s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true)
	{
		return $double_enc || false === strpos($s, '&') || false === strpos($s, ';')
			? htmlspecialchars($s, $style, $charset)
			: htmlspecialchars(html_entity_decode($s, $style, $charset), $style, $charset);
	}

	static function htmlentities($s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true)
	{
		return $double_enc || false === strpos($s, '&') || false === strpos($s, ';')
			? htmlentities($s, $style, $charset)
			: htmlentities(html_entity_decode($s, $style, $charset), $quote_style, $charset);
	}

	static function substr_compare($main_str, $str, $offset, $length = INF, $case_insensitivity = false)
	{
		if (INF === $length) return substr_compare($main_str, $str, $offset);
		$main_str = substr($main_str, $offset, $length);
		return $case_insensitivity ? strcasecmp($main_str, $str) : strcmp($main_str, $str);
	}
}
