<?php

class
{
	public static

	$appWhitelist = array(''),
	$appBlacklist = array(),

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
		'~trashed$',
	),

	$editWhitelist = array(),
	$editBlacklist = array();


	static function isAuthApp($path)
	{
		static $cache = array();

		isset($cache[$path]) || $cache[$path] = self::isAuth($path, self::$appWhitelist, self::$appBlacklist);

		return $cache[$path];
	}

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

	static function resetCache($file, $depth)
	{
		if (0 === strpos($file, 'public/'))
		{
			p::touch('public');
			p::updateAppId();
		}
		else
		{
			if (0 === strpos($file, 'class/patchwork/'))
			{
				unlink(PATCHWORK_PROJECT_PATH . '.patchwork.php');
			}
			else if (0 === strpos($file, 'class/'))
			{
				$file = patchwork_file2class(substr($file, 6));
				$file = patchwork_class2cache($file, $depth);
				@unlink($file);
			}

			patchwork_debugger::purgeZcache();
		}
	}
}
