<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends patchwork
{
	static function call()
	{
		p::setMaxage(0);
		if (p::$catchMeta) p::$metaInfo[1] = array('private');

		if (PATCHWORK_DIRECT)
		{
			$a = '';

			$cache = p::getContextualCachePath('antiCSRF.' . p::$agentClass, 'txt');

			p::makeDir($cache);

			$h = fopen($cache, 'a+b');
			flock($h, LOCK_EX);
			fseek($h, 0, SEEK_END);
			if (!ftell($h))
			{
				p::touch('appId');
				p::touch('public/templates/js');

				fwrite($h, $a = '1');

				p::touchAppId();
			}
			fclose($h);

			throw new PrivateDetection($a);
		}

		W('Potential JavaScript-Hijacking. Stopping !');

		p::disable(true);
	}
}
