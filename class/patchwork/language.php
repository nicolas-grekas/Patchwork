<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function negociate()
	{
		$lang = self::HTTP_Best_Language(array_keys($CONFIG['i18n.lang_list']));
		$b = $_SERVER['REQUEST_METHOD'];

		if (!PATCHWORK_DIRECT && ('GET' === $b || 'HEAD' === $b))
		{
			$lang = $CONFIG['i18n.lang_list'][$lang];
			$lang = implode($lang, explode('__', $_SERVER['PATCHWORK_BASE'], 2));
			$lang .= str_replace('%2F', '/', rawurlencode($_SERVER['PATCHWORK_REQUEST']));

			if (!isset($_SERVER['REDIRECT_QUERY_STRING'])) empty($_SERVER['QUERY_STRING']) || $lang .= '?' . $_SERVER['QUERY_STRING'];
			else '' === $_SERVER['REDIRECT_QUERY_STRING'] || $lang .= '?' . $_SERVER['REDIRECT_QUERY_STRING'];

			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . $lang);
			header('Expires: ' . gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $CONFIG['maxage']) . ' GMT');
			header('Cache-Control: max-age=' . $CONFIG['maxage'] .',' . ($GLOBALS['patchwork_private'] ? 'private' : 'public'));

			exit;
		}
		else $_SERVER['PATCHWORK_LANG'] = $lang;
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

		$lang = reset($supported);
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
