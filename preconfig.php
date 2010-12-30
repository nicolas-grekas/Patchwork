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


/**** Pre-configuration stage 1 ****/


/**/if (extension_loaded('mbstring'))
/**/{
/**/	ini_get_bool('mbstring.encoding_translation')
/**/		&& !in_array(strtolower(ini_get('mbstring.http_input')), array('pass', 'utf-8'))
/**/		&& die('Patchwork error: Please disable "mbstring.encoding_translation" or set "mbstring.http_input" to "pass" or "utf-8"');
/**/}

/**/if (!defined('E_DEPRECATED'))
		define('E_DEPRECATED', -1);
/**/if (!defined('E_USER_DEPRECATED'))
		define('E_USER_DEPRECATED', -1);
/**/if (!defined('E_RECOVERABLE_ERROR'))
		define('E_RECOVERABLE_ERROR', -1);

/**/$a = file_get_contents(patchwork_bootstrapper::$pwd . 'data/utf8/quickChecks.txt');
/**/$a = explode("\n", $a);
define('UTF8_NFC_RX',            /*<*/'/' . $a[1] . '/u'/*>*/);
define('PATCHWORK_PROJECT_PATH', /*<*/patchwork_bootstrapper::$cwd   /*>*/);
define('PATCHWORK_ZCACHE',       /*<*/patchwork_bootstrapper::$zcache/*>*/);
define('PATCHWORK_PATH_LEVEL',   /*<*/patchwork_bootstrapper::$last  /*>*/);
define('PATCHWORK_PATH_OFFSET',  /*<*/count(patchwork_bootstrapper::$paths) - patchwork_bootstrapper::$last/*>*/);

$patchwork_path = /*<*/patchwork_bootstrapper::$paths/*>*/;
$_patchwork_abstract = array();
$_patchwork_destruct = array();
$CONFIG = array();


// Utility functions

function patchwork_include($file)     {global $CONFIG; return include $file;}

/**/if (version_compare(PHP_VERSION, '5.3.0') < 0)
/**/{
/**/	/*<*/patchwork_bootstrapper::alias('is_a', 'patchwork_is_a', array('$obj', '$class'))/*>*/;
		function patchwork_is_a($obj, $class) {return $obj instanceof $class;}
/**/}

function patchwork_bad_request($message, $url)
{
	if (in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD')))
	{
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $url);
	}
	else
	{
		header('HTTP/1.1 400 Bad Request');
		header('Content-Type: text/html; charset=utf-8');

		$message = htmlspecialchars($message);
		$url = htmlspecialchars($url);

		echo <<<EOHTML
<html>
<head><title>400 Bad Request</title></head>
<body>
<h1>400 Bad Request</h1>
<p>{$message}<br> Maybe are you trying to reach <a href="{$url}">this URL</a>?</p>
</body>
</html>
EOHTML;
	}

	exit;
}

function patchwork_shutdown_start()
{
/**/if (function_exists('fastcgi_finish_request'))
		fastcgi_finish_request();

	register_shutdown_function('patchwork_shutdown_end');
}

function patchwork_shutdown_end()
{
	if ($GLOBALS['_patchwork_destruct'])
	{
		$class = array_shift($GLOBALS['_patchwork_destruct']);
		register_shutdown_function('patchwork_shutdown_end');
		call_user_func(array($class, '__destructStatic'));
	}
}

register_shutdown_function('patchwork_shutdown_start');


function patchwork_class2file($class)
{
	if (false !== $a = strrpos($class, '\\'))
	{
		$a += $b = strspn($class, '\\');
		$class =  strtr(substr($class, $b, $a), '\\', '/')
			.'/'. strtr(substr($class, $a+1  ), '_' , '/');
	}
	else
	{
		$class = strtr($class, '_', '/');
	}

	if (false !== strpos($class, '//x'))
	{
/**/	$a = "_/ /!/#/$/%/&/'/(/)/+/,/-/./;/=/@/[/]/^/`/{/}/~";
/**/	$a = array(array(), explode('/', $a));
/**/	foreach ($a[1] as $b) $a[0][] = '//x' . strtoupper(dechex(ord($b)));

		static $map = /*<*/$a/*>*/;

		$class = str_replace($map[0], $map[1], $class);
	}

	return $class;
}

function patchwork_file2class($file)
{
/**/$a = "_/ /!/#/$/%/&/'/(/)/+/,/-/./;/=/@/[/]/^/`/{/}/~";
/**/$a = array(explode('/', $a), array());
/**/foreach ($a[0] as $b) $a[1][] = '__x' . strtoupper(dechex(ord($b)));

	static $map = /*<*/$a/*>*/;
	$file = str_replace($map[0], $map[1], $file);
	$file = strtr($file, '/\\', '__');

	return $file;
}


// registerAutoloadPrefix()

$patchwork_autoload_prefix = array();

function registerAutoloadPrefix($class_prefix, $class_to_file_callback)
{
	if ($len = strlen($class_prefix))
	{
		$registry =& $GLOBALS['patchwork_autoload_prefix'];
		$class_prefix = strtolower($class_prefix);
		$i = 0;

		do
		{
			$c = ord($class_prefix[$i]);
			isset($registry[$c]) || $registry[$c] = array();
			$registry =& $registry[$c];
		}
		while (++$i < $len);

		$registry[-1] = $class_to_file_callback;
	}
}


// patchwork-specific include_path-like mechanism

function patchworkPath($file, &$last_level = false, $level = false, $base = false)
{
	if (false === $level)
	{
/**/if (IS_WINDOWS)
		if (isset($file[0]) && ('\\' === $file[0] || false !== strpos($file, ':'))) return $file;
		if (isset($file[0]) &&  '/'  === $file[0]) return $file;

		$i = 0;
		$level = /*<*/patchwork_bootstrapper::$last/*>*/;
	}
	else
	{
		0 <= $level && $base = 0;
		$i = /*<*/patchwork_bootstrapper::$last/*>*/ - $level - $base;
		0 > $i && $i = 0;
	}

/**/if (IS_WINDOWS)
		false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

	if (0 === $i)
	{
		$source = /*<*/patchwork_bootstrapper::$cwd/*>*/ . $file;

/**/	if (IS_WINDOWS)
/**/	{
			if (function_exists('patchwork_file_exists') ? patchwork_file_exists($source) : file_exists($source))
			{
				$last_level = $level;
				return false !== strpos($source, '/') ? strtr($source, '/', '\\') : $source;
			}
/**/	}
/**/	else
/**/	{
			if (file_exists($source))
			{
				$last_level = $level;
				return $source;
			}
/**/	}

	}


	if ($slash = '/' === substr($file, -1)) $file = substr($file, 0, -1);


/**/if ($a = patchwork_bootstrapper::updatedb())
/**/{
		static $db;

		if (!isset($db))
		{
			if (!$db = @dba_popen(/*<*/patchwork_bootstrapper::$cwd . '.parentPaths.db'/*>*/, 'rd', /*<*/$a/*>*/))
			{
				require_once /*<*/patchwork_bootstrapper::$pwd . 'class/patchwork/bootstrapper.php'/*>*/;

				$db = patchwork_bootstrapper::fixParentPaths(/*<*/patchwork_bootstrapper::$pwd/*>*/);
			}
		}

		$base = dba_fetch($file, $db);
/**/}
/**/else
/**/{
		$base = md5($file);
		$base = /*<*/patchwork_bootstrapper::$zcache/*>*/ . $base[0] . '/' . $base[1] . '/' . substr($base, 2) . '.path.txt';
		$base = @file_get_contents($base);
/**/}

	if (false !== $base)
	{
		$base = explode(',', $base);
		do if (current($base) >= $i)
		{
			$base = (int) current($base);
			$last_level = $level - $base + $i;

/**/		if (IS_WINDOWS)
				false !== strpos($file, '/') && $file = strtr($file, '/', '\\');

			return $GLOBALS['patchwork_path'][$base] . (0<=$last_level ? $file : substr($file, 6)) . ($slash ? /*<*/DIRECTORY_SEPARATOR/*>*/ : '');
		}
		while (false !== next($base));
	}

	return false;
}


// Check HTTP validator

$patchwork_private = false;

/**/unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);

$a = isset($_SERVER['HTTP_IF_NONE_MATCH'])
	? $_SERVER['HTTP_IF_NONE_MATCH']
	: isset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

if ($a)
{
	if (true === $a)
	{
		// Patch an IE<=6 bug when using ETag + compression
		$a = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = $a = strtotime($a[0]);
		$_SERVER['HTTP_IF_NONE_MATCH'] = '"' . dechex($a) . '"';
		$patchwork_private = true;
	}
	else if (27 === strlen($a) && 25 === strspn($a, '0123456789abcdef') && '""' === $a[0] . $a[26])
	{
		$b = PATCHWORK_ZCACHE . $a[1] .'/'. $a[2] .'/'. substr($a, 3, 6) .'.v.txt';
		if (file_exists($b) && substr(file_get_contents($b), 0, 8) === substr($a, 9, 8))
		{
			$private = substr($a, 17, 1);
			$maxage  = hexdec(substr($a, 18, 8));

			header('HTTP/1.1 304 Not Modified');
			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $_SERVER['REQUEST_TIME'] + ($private || !$maxage ? 0 : $maxage)));
			header('Cache-Control: max-age=' . $maxage . ($private ? ',private,must' : ',public,proxy') . '-revalidate');
			exit;
		}
	}
}


// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with our own output compression.

/**/if (function_exists('apache_setenv'))
		apache_setenv('no-gzip','1');


/**/if (ini_get_bool('zlib.output_compression'))
		@ini_set('zlib.output_compression', false);


// Convert ISO-8859-1 URLs to UTF-8 ones

function url_enc_utf8_dec_callback($m) {return urlencode(patchwork_utf8_encode(urldecode($m[0])));}

if (!preg_match('//u', urldecode($a = $_SERVER['REQUEST_URI'])))
{
	$a = $a !== patchwork_utf8_decode($a) ? '/' : preg_replace_callback('/(?:%[89a-f][0-9a-f])+/i', 'url_enc_utf8_dec_callback', $a);

	patchwork_bad_request('Requested URL is not a valid urlencoded UTF-8 string.', $a);
}


// Input normalization

/**/$h = @(extension_loaded('mbstring') && ini_get_bool('mbstring.encoding_translation') && 'UTF-8' === strtoupper(ini_get('mbstring.http_input')));
/**/if (!$h || (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()))
/**/{
		$a = array(&$_GET, &$_POST, &$_COOKIE);
		foreach ($_FILES as &$v) $a[] = array(&$v['name'], &$v['type']);

		$k = count($a);
		for ($i = 0; $i < $k; ++$i)
		{
			foreach ($a[$i] as &$v)
			{
				if (is_array($v)) $a[$k++] =& $v;
				else
				{
/**/				if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
/**/				{
/**/					if (ini_get_bool('magic_quotes_sybase'))
							$v = str_replace("''", "'", $v);
/**/					else
							$v = stripslashes($v);
/**/				}

/**/				if (!$h)
/**/				{
/**/					if (extension_loaded('iconv') && '§' === @iconv('UTF-8', 'UTF-8//IGNORE', "§\xE0"))
/**/					{
							$v = @iconv('UTF-8', 'UTF-8//IGNORE', $v);
/**/					}
/**/					else
/**/					{
							# From http://www.w3.org/International/questions/qa-forms-utf-8
							preg_match_all(/*<*/"/(?:[\\x00-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xec\xee\xef][\x80-\xbf]{2}|\xed[\x80-\x9f][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf]{2}|[\xf1-\xf3][\x80-\xbf]{3}|\xf4[\x80-\x8f][\x80-\xbf]{2}){1,50}/"/*>*/, $v, $b);
							$v = implode('', $b[0]);
/**/					}
/**/				}
				}
			}

			reset($a[$i]);
			unset($a[$i]);
		}

		unset($a, $v);
/**/}


/**/$a = md5(mt_rand());
/**/$b = @ini_set('display_errors', $a);
/**/
/**/if (@ini_get('display_errors') !== $a)
/**/{
/**/	/*<*/patchwork_bootstrapper::alias('ini_set',        'patchwork_ini_set', array('$k', '$v'))/*>*/;
/**/	/*<*/patchwork_bootstrapper::alias('ini_alter',      'patchwork_ini_set', array('$k', '$v'))/*>*/;
/**/	/*<*/patchwork_bootstrapper::alias('ini_get',        'patchwork_ini_get', array('$k'))/*>*/;
/**/	/*<*/patchwork_bootstrapper::alias('set_time_limit', 'patchwork_set_time_limit', array('$s'))/*>*/;

		function patchwork_ini_set($k, $v)    {return @ini_set($k, $v);}
		function patchwork_ini_get($k)        {return @ini_get($k);}
		function patchwork_set_time_limit($s) {return @set_time_limit($s);}
/**/}
/**/else if (ini_get_bool('safe_mode'))
/**/{
/**/	/*<*/patchwork_bootstrapper::alias('set_time_limit', 'patchwork_set_time_limit', array('$s'))/*>*/;
		function patchwork_set_time_limit($a) {return @set_time_limit($s);}
/**/}
/**/
/**/@ini_set('display_errors', $b);
