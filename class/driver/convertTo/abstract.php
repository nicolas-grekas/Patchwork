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


abstract class
{
	abstract function file($file);

	function data($data)
	{
		$file = tempnam('./tmp', 'convert');

		CIA::writeFile($file, $data);

		$data = $this->file($file);

		unlink($file);

		return $data;
	}
}
