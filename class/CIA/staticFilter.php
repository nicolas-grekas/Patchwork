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
	protected $rx;
	protected $rest = '';

	function __construct($rx)
	{
		$this->rx = $rx;
	}

	function filter($buffer, $mode)
	{
		$base = CIA::__BASE__() . dirname($_SERVER['CIA_REQUEST']) . '/';

		$buffer = preg_split($this->rx, $this->rest . $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

		$len = count($buffer);
		for ($i = 1; $i < $len; $i += 2) $buffer[$i] .= $base;

		if (PHP_OUTPUT_HANDLER_END & $mode) $this->rest = '';
		else
		{
			$base = array_pop($buffer);
			$this->rest = substr($base, 4096);
			array_push($buffer, substr($base, 0, 4096));
		}

		return implode('', $buffer);
	}
}
