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

$version_id = -$version_id;

CIA_DIRECT && isset($_GET['d$']) && require resolvePath('debugWin.php');

if (CIA_CHECK_SOURCE && !CIA_DIRECT)
{
	if ($h = @fopen('./.debugLock', 'x+b'))
	{
		flock($h, LOCK_EX);

		foreach (glob('./.*.' . $cia_paths_token . '.*.zcache.php', GLOB_NOSORT) as $cache)
		{
			$file = str_replace('%1', '%', str_replace('%2', '_', strtr(substr($cache, 3, -11), '_', '/')));
			$level = substr(strrchr($file, '.'), 2);

			$file = substr($file, 0, -(2 + strlen($cia_paths_token) + strlen($level)));
			$level = '-' == substr($level, -1) ? -$level : (int) $level;

			$file = $cia_include_paths[count($cia_paths) - $level - 1] .'/'. $file;

			if (!file_exists($file) || filemtime($file) >= filemtime('./'.$cache)) @unlink($cache);
		}

		fclose($h);
	}
	else
	{
		$h = fopen('./.debugLock', 'rb');
		flock($h, LOCK_SH);
		fclose($h);
	}

	@unlink('./.debugLock');
}
