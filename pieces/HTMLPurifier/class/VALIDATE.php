<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends self
{
	# config of HTMLPurifier
	protected static function get_html(&$value, &$args)
	{
		$a = array();

		if ($result = self::get_text($value, $a))
		{
			static $parser;
			isset($parser) || $parser = new HTMLPurifier;

			$result = $parser->purify($result, isset($args[0]) ? $args[0] : null);
			$result = str_replace(p::__BASE__(), '{~}', $result);
			$result = str_replace(p::__HOST__(), '{/}', $result);
		}

		return $result;
	}
}
