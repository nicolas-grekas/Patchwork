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


class extends loop_array
{
	function __construct($loop)
	{
		$array = $this->getArray($loop, true);

		parent::__construct($array, 'filter_rawArray');
	}

	function getArray($loop, $unshift = false)
	{
		$array = array();

		while ($a = $loop->loop())
		{
			foreach ($a as &$v) if ($v instanceof loop) $v = new loop_array($this->getArray($v), 'filter_rawArray');

			$unshift ? array_unshift($array, $a) : ($array[] = $a);
		}

		return $array;
	}
}
