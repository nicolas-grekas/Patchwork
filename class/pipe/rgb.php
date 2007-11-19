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
	static function php($r, $g, $b)
	{
		$r = p::string($r) - 0;
		$g = p::string($g) - 0;
		$b = p::string($b) - 0;

		return sprintf('#%02x%02x%02x', $r, $g, $b);
	}

	static function js()
	{
		?>/*<script>*/

P$rgb = function($r, $g, $b)
{
	$r = ($r/1 || 0).toString(16);
	$g = ($g/1 || 0).toString(16);
	$b = ($b/1 || 0).toString(16);

	if ($r.length < 2) $r = '0' + $r;
	if ($g.length < 2) $g = '0' + $g;
	if ($b.length < 2) $b = '0' + $b;

	return '#' + $r + $g + $b;
}

<?php	}
}
