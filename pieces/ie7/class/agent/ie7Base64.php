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


class extends agent
{
	const contentType = '';

	function control() {}

	function compose($o)
	{
		if (!isset($_SERVER['QUERY_STRING']) || false !== strpos($_SERVER['QUERY_STRING'], ';'))
		{
			$data = explode(';', $_SERVER['QUERY_STRING']);
			header('Content-Type: ' . $data[0]);

			$data = explode(',', $data[1]),
			$o->DATA = base64_decode($data[1]);
		}

		return $o;
	}
}
