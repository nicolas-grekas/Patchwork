<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($string)
	{
		$string = p::string($string);

		return (string) $string === (string) ($string-0)
			? $string
			: str_replace(
				array('&#039;', '&quot;', '&gt;', '&lt;', '&amp;', '{/}'                , '{~}'),
				array("'"     , '"'     , '>'   , '<'   , '&'    , p::__HOST__(), p::__BASE__()),
				$string
			);
	}

	static function js()
	{
		?>/*<script>*/

P$allowhtml = function($string)
{
	var $base = base();

	$string = str($string);
	return (''+$string/1==$string)
		? $string/1
		: unesc($string).replace(/{\/}/g, $base.substr(0, $base.indexOf('/', 8)+1)).replace(/{~}/g, $base);
}

<?php	}
}
