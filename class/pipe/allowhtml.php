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
	static function php($string)
	{
		$string = CIA::string($string);

		return (string) $string === (string) ($string-0)
			? $string
			: str_replace(
				array('&#039;', '&quot;', '&gt;', '&lt;', '&amp;'),
				array("'"     , '"'     , '>'   , '<'   , '&'    ),
				$string
			);
	}

	static function js()
	{
		?>/*<script>*/

P$allowhtml = function($string)
{
	$string = str($string);
	return (''+$string/1==$string)
		? $string/1
		: unesc($string);
}

<?php	}
}
