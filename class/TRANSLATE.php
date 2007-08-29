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


class
{
	protected static

	$adapter,
	$cache;


	public static function get($string, $lang, $usecache)
	{
		if ('' === $string || '__' == $lang || !PATCHWORK_I18N) return $string;

		$hash = md5($string);
		$cache = '';

		if ($usecache && $id = p::$agentClass)
		{
			$id = p::getContextualCachePath('lang/' . substr($id, 6), 'php');
			if (!isset(self::$cache[$id]))
			{
				if (file_exists($id)) $cache = include $id;

				self::$cache[$id] = $cache ? array(false, false, &$cache) : array(false, true, array());
			}

			$cache =& self::$cache[$id][$hash];

			if ('' !== (string) $cache) return $cache;
			else self::$cache[$id][0] = true;
		}

		$cache = self::$adapter->search($string, $lang);

		if ('' === (string) $cache) $cache = $string;

		return $cache;
	}


	static function __static_construct()
	{
		self::$cache = array();

		$adapter = isset($CONFIG['translate.adapter']) && $CONFIG['translate.adapter'] ? 'adapter_translate_' . $CONFIG['translate.adapter'] : __CLASS__;
		self::$adapter = new $adapter(isset($CONFIG['translate.options']) ? $CONFIG['translate.options'] : array());
		self::$adapter->open();
	}

	static function __static_destruct()
	{
		self::$adapter->close();

		foreach (self::$cache as $file => &$cache) if ($cache[0])
		{
			$data = '<?php return ' . var_export($cache[2], true) . ';';

			p::writeFile($file, $data);
			if ($cache[1]) p::writeWatchTable('translate', $file, false);
		}
	}


	/* Adapter interface */

	function open() {}
	function search($string, $lang) {return $string; /*return "<span class='i18n {$lang}'>{$string}</span>";*/}
	function close() {}
}
