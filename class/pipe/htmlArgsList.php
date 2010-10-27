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
	static function php()
	{
		$a = func_get_args();
		count($a) % 2 && $a[] = '';
		$len = count($a);

		$result = '';
		for ($i = 0; $i < $len; $i += 2)
		{
			$v = p::string($a[$i+1]);
			'' !== $v && $result .= p::string($a[$i]) . '="' . $v . '" ';
		}

		return $result;
	}

	static function js()
	{
		?>/*<script>*/

function()
{
	var $result = '', $a = arguments, $i = 0, $v;

	for ($i = 0; $i < $a.length; $i += 2)
	{
		$v = str($a[$i+1]);
		if ('' != $v) $result += str($a[$i]) + '="' + $v + '" ';
	}

	return $result;
}

<?php	}
}
