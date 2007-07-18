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


class extends self
{
	# config of HTMLPurifier
	protected static function get_html(&$value, &$args)
	{
		$a = array();

		if ($result = self::get_raw($value, $a))
		{
			static $parser;

			if (!isset($parser))
			{
				$parser = new HTMLPurifier;
			}

			$result = $parser->purify($result, isset($args[0]) ? $args[0] : null);
		}

		return $result;
	}
}
