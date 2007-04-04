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
	static function call($agent)
	{
		static $cache = array();

		if (isset($cache[$agent])) return $cache[$agent];
		else $cache[$agent] =& $trace;

		$args = array();
		$BASE = $base = CIA::__BASE__();
		$agent = str_replace('%2F', '/', rawurlencode($agent));
		$agent = CIA::base($agent, true);
		$agent = preg_replace("'^.*?://[^/]*'", '', $agent);
		
		$h = isset($_SERVER['HTTPS']) ? 'ssl' : 'tcp';
		$h = fsockopen("{$h}://{$_SERVER['SERVER_ADDR']}", $_SERVER['SERVER_PORT'], $errno, $errstr, 30);
		if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");

		$keys  = "GET {$agent}?k$ HTTP/1.0\r\n";
		$keys .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
		$keys .= "Connection: Close\r\n\r\n";

		fwrite($h, $keys);
		$keys = array();
		while (!feof($h)) $keys[] = fgets($h);
		fclose($h);

		$h = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';
		$h = "/w\.k\((-?[0-9]+),($h),($h),($h),\[((?:$h(?:,$h)*)?)\]\)/su";

		if (!preg_match($h, implode('', $keys), $keys))
		{
			W('Error while getting meta info data for ' . htmlspecialchars($agent));
			CIA::disable(true);
		}

		$CIApID = (int) $keys[1];
		$base = stripcslashes(substr($keys[2], 1, -1));
		$base = preg_replace("'__'", CIA::__LANG__(), $base, 1);
		$agent = stripcslashes(substr($keys[3], 1, -1));
		$a = stripcslashes(substr($keys[4], 1, -1));
		$keys = eval('return array(' . $keys[5] . ');');

		if ('' !== $a)
		{
			$args['__0__'] = $a;

			$i = 0;
			foreach (explode('/', $a) as $a) $args['__' . ++$i . '__'] = $a;
		}

		if ($base == $BASE) $CIApID = $base = false;
		else CIA::watch('foreignTrace');

		return $trace = array($CIApID, $base, $agent, $keys, $args);
	}
}
