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


/*
 * To use eZComponents, setup them using the PEAR method,
 * then add this line in your config.php :
 * registerAutoloadPrefix('ezc', array('adapter_ezc', 'getAutoload'));
 */

class
{
	static function getAutoload($className)
	{
		return 'ezcBase' == $className ? 'ezc/Base/base.php' : ezcBase::getAutoload($className);
	}
}
