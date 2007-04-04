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
	static function negociate()
	{
		$lang = self::HTTP_Best_Language(explode('|', $GLOBALS['CONFIG']['lang_list']));
		$b = $_SERVER['REQUEST_METHOD'];

		if (!CIA_DIRECT && ('GET' == $b || 'HEAD' == $b))
		{
			$lang = implode($lang, explode('__', $_SERVER['CIA_BASE'], 2));
			$lang = preg_replace("'^.*?://[^/]*'", '', $lang);
			$lang .= str_replace('%2F', '/', rawurlencode($_SERVER['CIA_REQUEST']));
			$_GET && $lang .= '?' . $_SERVER['QUERY_STRING'];

			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . $lang);
			header('Expires: ' . gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + CIA_MAXAGE) . ' GMT');
			header('Cache-Control: max-age=' . CIA_MAXAGE .',' . ($GLOBALS['cia_private'] ? 'private' : 'public'));

			exit;
		}
		else $_SERVER['CIA_LANG'] = $lang;
	}

	static function HTTP_Best_Language($supported)
	{
		static $vary = true;
		$vary && $vary = header('Vary: Accept-Language', false);

		$candidates = array();

		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $item)
		{
			$item = explode(';q=', $item);
			if ($item[0] = trim($item[0])) $candidates[ $item[0] ] = isset($item[1]) ? (double) trim($item[1]) : 1;
		}

		$lang = $supported[0];
		$qMax = 0;

		foreach ($candidates as $l => &$q) if (
			$q > $qMax
			&& (
				in_array($l, $supported)
				|| (
					($tiret = strpos($l, '-'))
					&& in_array($l = substr($l, 0, $tiret), $supported)
				)
			)
		)
		{
			$qMax = $q;
			$lang = $l;
		}

		return $lang;
	}
}
