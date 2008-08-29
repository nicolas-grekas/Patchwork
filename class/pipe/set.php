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
	static function php($pool, $key, $value)
	{
		$pool && $pool->$key = $value;
		return '';
	}

	static function js()
	{
		?>/*<script>*/

P$set = function($pool, $key, $value)
{
	if ($pool) $pool[$key] = $value;
	return '';
}

<?php	}
}
