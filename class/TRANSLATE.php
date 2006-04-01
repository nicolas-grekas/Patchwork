<?php

abstract class TRANSLATE
{
	public static $driverClass;
	public static $defaultLang = '__';

	private static $started = false;

	private static $driver;
	private static $cache = array();

	public static function get($string, $usecache = true)
	{
		self::start();

		if (CIA::__LANG__() == self::$defaultLang) return $string;
		
		$hash = sprintf('%u', crc32($string));
		$cache = '';
		
		if ($id = CIA::$agentClass)
		{
			$id = substr($id, 6);
			if (!isset(self::$cache[$id]))
			{
				if ($usecache) $cache = @include './tmp/cache/lang/' . $id . '.php';
				self::$cache[$id] = is_array($cache) ? $cache : array();
			}

			if ($usecache)
			{
				$cache =& self::$cache[$id][$hash];
				if ('' === (string) $cache) return $cache;
			}
		}

		$cache = self::$driver->translate($string);

		if ('' === (string) $cache) $cache = $string;
		
		return $cache;
	}

	public static function syncCache()
	{
		self::$driver->close();

		foreach (self::$cache as $id => $cacheArray)
		{
			if ($cacheArray)
			{
				$file = CIA::makeCacheDir('lang/' . $id, 'php');
				$data = '<?php return ' . var_export($cacheArray, true) . ';';
		
				CIA::writeFile($file, $data);
				CIA::watch(array('translate'), $file);
			}		
		}
	}


	/* Private methods */

	private static function start()
	{
		if (self::$started) return;

		self::$started = true;

		global $CONFIG;
		self::$driverClass = $driver = 'driver_translate_' . $CONFIG['translate_driver'];
		self::$driver = new $driver($CONFIG['translate_params']);
		self::$driver->open();

		register_shutdown_function(array('TRANSLATE', 'syncCache'));
	}


	/* Driver interface */

	public function open() {}
	public function translate($string) {return $string;"<span class='i18n'>$string</span>";}
	public function close() {}
}

class driver_translate_default_ extends TRANSLATE {}
