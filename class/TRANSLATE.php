<?php

abstract class TRANSLATE
{
	protected static $started = false;

	protected static $driver;
	protected static $cache = array();

	public static function get($string, $usecache = true)
	{
		if ('' === $string) return '';

		if (!self::$started) self::start();

		$lang = CIA::__LANG__();
		if ('__' == $lang) return $string;

		$hash = (int) sprintf('%u', crc32($string));
		$cache = '';

		if ($usecache && $id = CIA::$agentClass)
		{
			$id = CIA::makeCacheDir('lang/' . substr($id, 6), 'php');
			if (!isset(self::$cache[$id]))
			{
				$cache = @include $id;

				self::$cache[$id] = $cache ? array(false, false, &$cache) : array(false, true, array());
			}

			$cache =& self::$cache[$id][$hash];

			if ('' !== (string) $cache) return $cache;
			else self::$cache[$id][0] = true;
		}

		$cache = self::$driver->translate($string, $lang);

		if ('' === (string) $cache) $cache = $string;

		return $cache;
	}

	public static function syncCache()
	{
		self::$driver->close();

		foreach (self::$cache as $file => $cache) if ($cache[0])
		{
			$data = '<?php return ' . var_export($cache[2], true) . ';';

			CIA::writeFile($file, $data);
			if ($cache[1]) CIA::watch(array('translate'), $file);
		}
	}


	/* Private methods */

	private static function start()
	{
		if (self::$started) return;

		self::$started = true;

		global $CONFIG;
		$driver = 'driver_translate_' . $CONFIG['translate_driver'];
		self::$driver = new $driver($CONFIG['translate_params']);
		self::$driver->open();

		register_shutdown_function(array('TRANSLATE', 'syncCache'));
	}


	/* Driver interface */

	public function open() {}
	public function translate($string, $lang) {return $string; /*return "<span class='i18n {$lang}'>{$string}</span>";*/}
	public function close() {}
}

class driver_translate_default_ extends TRANSLATE {}
