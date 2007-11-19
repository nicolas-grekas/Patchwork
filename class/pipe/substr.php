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
	static function php($string, $start, $length = null)
	{
		return null === $length
			? mb_substr(p::string($string), (int) p::string($start))
			: mb_substr(p::string($string), (int) p::string($start), (int) p::string($length));
	}

	static function js()
	{
		?>/*<script>*/

P$substr = function($string, $start, $length)
{
	$string = str($string);
	return t($length)
		? $string.substr($start/1, $length/1)
		: $string.substr($start/1);
}

<?php	}
}
