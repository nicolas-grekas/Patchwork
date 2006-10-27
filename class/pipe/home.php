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


// This pipe is duplicated in js/w

class
{
	static function php($string = '')
	{
		return CIA::home( CIA::string($string) );
	}

	static function js()
	{
		?>/*<script>*/

P$home = function($string)
{
	return home( str($string) );
}

<?php	}
}
