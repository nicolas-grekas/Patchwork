<?php

class
{
	public static

	$readWhitelist = array(
		'^class/',
		'^public/',
		'^example/',
		'^data/((utf8|unicode)/.*)?[^/]*$',
		'^[^/]+$',
	),

	$readBlacklist = array(
		'(^|/)\.',
		'(^|/)zcache(/|$)',
		'(^|/)config\.patchwork\.php',
		'(^|/)error\.patchwork\.log',
	),

	$editWhitelist = array(),
	$editBlacklist = array();


	static function isAuthRead($path)
	{
		if ('' === $path) return true;

		static $cache = array();

		isset($cache[$path]) || $cache[$path] = self::isAuth($path, self::$readWhitelist, self::$readBlacklist);

		return $cache[$path];
	}

	static function isAuthEdit($path)
	{
		static $cache = array();

		isset($cache[$path]) || $cache[$path] = self::isAuth($path, self::$editWhitelist, self::$editBlacklist);

		return $cache[$path];
	}

	protected static function isAuth($path, $whitelist, $blacklist)
	{
		$auth = false;

		foreach ($whitelist as $rx)
		{
			if (preg_match("\"{$rx}\"uD", $path))
			{
				$auth = true;
				break;
			}
		}

		if (!$auth) return false;

		foreach ($blacklist as $rx)
		{
			if (preg_match("\"{$rx}\"uD", $path)) return false;
		}

		return true;
	}

	static function getAppname($depth)
	{
		global $patchwork_path;

		$depth = PATCHWORK_PATH_LEVEL - $depth;

		return isset($patchwork_path[$depth]) ? basename($patchwork_path[$depth]) : false;
	}
}
