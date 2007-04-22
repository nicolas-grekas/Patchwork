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


class extends self
{
	function ob_handler($buffer)
	{
		parent::ob_handler($buffer);

		if ('' !== $buffer)
		{
			iaMail_mime::send(
				array('To' => $GLOBALS['CONFIG']['debug_email']),
				$buffer
			);
		}

		return '';
	}
}
