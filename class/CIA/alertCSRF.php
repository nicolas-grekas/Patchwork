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


class extends CIA
{
	static function call()
	{
		CIA::setMaxage(0);
		if (CIA::$catchMeta) CIA::$metaInfo[1] = array('private');

		if (CIA_DIRECT)
		{
			$a = '';

			$cache = CIA::getContextualCachePath('antiCSRF.' . CIA::$agentClass, 'txt');

			CIA::makeDir($cache);

			$h = fopen($cache, 'a+b');
			flock($h, LOCK_EX);
			fseek($h, 0, SEEK_END);
			if (!ftell($h))
			{
				CIA::touch('CIApID');
				CIA::touch('public/templates/js');

				fwrite($h, $a = '1', 1);
				touch('config.php');
			}
			fclose($h);

			throw new PrivateDetection($a);
		}

		W('Potential Cross Site JavaScript Request. Stopping !');

		CIA::disable(true);
	}
}
