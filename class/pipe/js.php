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


class
{
	static function php($string, $forceString = false)
	{
		$string = p::string($string);

		return jsquote(str_replace(
			array('&#039;', '&quot;', '&gt;', '&lt;', '&amp;'),
			array("'"     , '"'     , '>'   , '<'   , '&'    ),
			$string
		));
	}

	static function js()
	{
		?>/*<script>*/

P$js = function($string, $forceString)
{
	$string = str($string);

	return $forceString || (''+$string/1 != $string)
		? ("'" + $string.replace(
				/&#039;/g, "'").replace(
				/&quot;/g, '"').replace(
				/&gt;/g  , '>').replace(
				/&lt;/g  , '<').replace(
				/&amp;/g , '&').replace(
				/\\/g , '\\\\').replace(
				/'/g  , "\\'").replace(
				/\r/g , '\\r').replace(
				/\n/g , '\\n').replace(
				/<\//g, '<\\\/'
			) + "'"
		)
		: $string/1;
}

<?php	}
}
