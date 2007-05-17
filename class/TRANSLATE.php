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
	protected static $adapter;
	protected static $cache;

	public static function get($string, $lang, $usecache)
	{
		if ('' === $string || '__' == $lang || !$GLOBALS['patchwork_multilang']) return $string;

		$hash = md5($string);
		$cache = '';

		if ($usecache && $id = patchwork::$agentClass)
		{
			$id = patchwork::getContextualCachePath('lang/' . substr($id, 6), 'php');
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

		global $CONFIG;

		$adapter = isset($CONFIG['translate_adapter']) && $CONFIG['translate_adapter'] ? 'adapter_translate_' . $CONFIG['translate_adapter'] : __CLASS__;
		self::$adapter = new $adapter(isset($CONFIG['translate_options']) ? $CONFIG['translate_options'] : array());
		self::$adapter->open();
	}

	static function __static_destruct()
	{
		self::$adapter->close();

		foreach (self::$cache as $file => &$cache) if ($cache[0])
		{
			$data = '<?php return ' . var_export($cache[2], true) . ';';

			patchwork::writeFile($file, $data);
			if ($cache[1]) patchwork::writeWatchTable('translate', $file, false);
		}
	}


	/* Adapter interface */

	function open() {}
	function search($string, $lang) {return $string; /*return "<span class='i18n {$lang}'>{$string}</span>";*/}
	function close() {}
}
