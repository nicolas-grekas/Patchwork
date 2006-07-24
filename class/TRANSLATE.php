<?php

abstract class
{
	protected static $started;

	protected static $driver;
	protected static $cache;

	public static function get($string, $lang, $usecache)
	{
		if ('' === $string || '__' == $lang) return $string;
		isset(self::$started) || self::start();

		$hash = md5($string);
		$cache = '';

		if ($usecache && $id = CIA::$agentClass)
		{
			$id = CIA::getContextualCachePath('lang/' . substr($id, 6), 'php');
			if (!isset(self::$cache[$id]))
			{
				$cache = @include $id;

				self::$cache[$id] = $cache ? array(false, false, &$cache) : array(false, true, array());
			}

			$cache =& self::$cache[$id][$hash];

			if ('' !== (string) $cache) return $cache;
			else self::$cache[$id][0] = true;
		}

		$cache = self::$driver->search($string, $lang);

		if ('' === (string) $cache) $cache = $string;

		return $cache;
	}

	public static function syncCache()
	{
		self::$driver->close();

		foreach (self::$cache as $file => &$cache) if ($cache[0])
		{
			$data = '<?php return ' . var_export($cache[2], true) . ';';

			CIA::writeFile($file, $data);
			if ($cache[1]) CIA::writeWatchTable(array('translate'), $file);
		}
	}


	/* Private methods */

	private static function start()
	{
		if (isset(self::$started)) return;

		self::$started = true;
		self::$cache = array();

 		global $CONFIG;
		$driver = 'driver_translate_' . $CONFIG['translate_driver'];
		self::$driver = new $driver($CONFIG['translate_params']);
		self::$driver->open();

		register_shutdown_function(array('TRANSLATE', 'syncCache'));
	}


	/* Driver interface */

	function open() {}
	function search($string, $lang) {return $string; /*return "<span class='i18n {$lang}'>{$string}</span>";*/}
	function close() {}
}

class driver_translate_default_ extends self {}
