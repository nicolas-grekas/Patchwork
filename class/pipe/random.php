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
	static function php($min = '', $max = '')
	{
		if ($max === '') $max = 32767;

		$min = (int) p::string($min);
		$max = (int) p::string($max);

		return mt_rand($min, $max);
	}

	static function js()
	{
		?>/*<script>*/

P$random = function($min, $max)
{
	if (!t($max))) $max = 32767;

	$min = ($min-0) || 0;
	$max -= 0;

	if ($min > $max)
	{
		var $tmp = $min;
		$min = $max;
		$max = $tmp;
	}

	return $min + parseInt(Math.random() * ($max+1));
}

<?php	}
}
