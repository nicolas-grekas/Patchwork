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


define('CIA', microtime(true));

// IIS compatibility
isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] = $_SERVER['URL'];
isset($_SERVER['SERVER_ADDR']) || $_SERVER['SERVER_ADDR'] = '127.0.0.1';

// Convert ISO-8859-1 URLs to UTF-8 ones
if (!preg_match("''u", urldecode($a = $_SERVER['REQUEST_URI'])))
{
	$a = $a != utf8_decode($a) ? '/' : preg_replace("'(?:%[89a-f][0-9a-f])+'ei", "urlencode(utf8_encode(urldecode('$0')))", $a);
	$b = $_SERVER['REQUEST_METHOD'];

	if ('GET' == $b || 'HEAD' == $b)
	{
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $a);
		exit;
	}
	else
	{
		$_SERVER['REQUEST_URI'] = $a;
		$b = strpos($a, '?');
		$_SERVER['QUERY_STRING'] = false !== $b++ && $b < strlen($a) ? substr($a, $b) : '';
		parse_str($_SERVER['QUERY_STRING'], $_GET);
	}
}

// {{{ registerAutoloadPrefix()
$cia_autoload_prefix = array();

function registerAutoloadPrefix($prefix, $class2file_resolver, $class2file_resolver_method = false)
{
	$prefix = strtolower($prefix);
	$registry = array();

	if (is_string($class2file_resolver_method) && is_string($class2file_resolver))
		$class2file_resolver = array($class2file_resolver, $class2file_resolver_method);

	foreach ($GLOBALS['cia_autoload_prefix'] as $v)
	{
		if (false !== $prefix)
		{
			if ($prefix == $v[0]) $v[1] = $class2file_resolver;
			else if (strlen($v[0]) < strlen($prefix)) $registry[] = array($prefix, $class2file_resolver);

			$prefix = false;
		}

		$registry[] = array($v[0], $v[1]);
	}

	if (false !== $prefix) $registry[] = array($prefix, $class2file_resolver);

	$GLOBALS['cia_autoload_prefix'] =& $registry;
}
// }}}

// {{{ registerAutoloadClass()
$cia_autoload_class = array();

function registerAutoloadClass($class, $filename = '')
{
	global $cia_autoload_class;
	is_string($class) && $class = array($class => $filename);
	foreach ($class as $k => &$v) $cia_autoload_class[ strtolower($k) ] =& $v;
}
// }}}

// {{{ cia_atomic_write
function cia_atomic_write(&$data, $to, $mtime = false)
{
	$tmp = uniqid(mt_rand(), true);
	file_put_contents($tmp, $data);
	unset($data);

	$mtime && touch($tmp, $mtime);

	if (CIA_WINDOWS)
	{
		$data = new COM('Scripting.FileSystemObject');
		$data->GetFile(CIA_PROJECT_PATH .'/'. $tmp)->Attributes |= 2; // Set hidden attribute
		file_exists($to) && unlink($to);
		@rename($tmp, $to) || unlink($tmp);
	}
	else rename($tmp, $to);
}
// }}}

// {{{ Load configuration
chdir($CIA);

// $_REQUEST is an open door to security problems.
$_REQUEST = array();

$CONFIG = array();
$version_id = './.config.zcache.php';

define('__CIA__', dirname(__FILE__));
define('CIA_WINDOWS', '\\' == DIRECTORY_SEPARATOR);
define('CIA_PROJECT_PATH', getcwd());

// Major browsers send a "Cache-Control: no-cache" only if a page is reloaded
// with CTRL+F5 or location.reload(true). Usefull to trigger synchronization events.
define('CIA_CHECK_SOURCE', isset($_SERVER['HTTP_CACHE_CONTROL']) && 'no-cache' == $_SERVER['HTTP_CACHE_CONTROL']);

// Load the configuration
require file_exists($version_id) ? $version_id : (__CIA__ . '/c3mro.php');

if (isset($CONFIG['clientside']) && !$CONFIG['clientside']) $_GET['$bin'] = true;

// Restore the current dir in shutdown context.
function cia_restoreProjectPath() {CIA_PROJECT_PATH != getcwd() && chdir(CIA_PROJECT_PATH);}
register_shutdown_function('cia_restoreProjectPath', CIA_PROJECT_PATH);
// }}}

// {{{ Global Initialisation
define('DEBUG',       $CONFIG['DEBUG_ALLOWED'] && (!$CONFIG['DEBUG_PASSWORD'] || (isset($_COOKIE['DEBUG']) && $CONFIG['DEBUG_PASSWORD'] == $_COOKIE['DEBUG'])) ? 1 : 0);
define('CIA_MAXAGE',  isset($CONFIG['maxage']) ? $CONFIG['maxage'] : 2678400); // 31D x 24H x 3600S = 2678400S ~ 1M
define('CIA_POSTING', 'POST' == $_SERVER['REQUEST_METHOD']);
define('CIA_DIRECT',  '_' == $_SERVER['CIA_REQUEST']);

function E($msg = '__getDeltaMicrotime')
{
	return class_exists('CIA', false) ? CIA::log($msg, false, false) : W($msg, E_USER_NOTICE);
}

function W($msg, $err = E_USER_WARNING)
{
	ini_set('log_errors', true);
	ini_set('error_log', './error.log');
	ini_set('display_errors', false);
	trigger_error($msg, $err);
}
// }}}



{ // <-- Hack to enable the functions below only when execution reaches this point

// {{{ function resolvePath(): cia-specific include_path-like mechanism
function resolvePath($file, $level = false, $base = false)
{
	$last_cia_paths = count($GLOBALS['cia_paths']) - 1;

	if (false === $level)
	{
		$i = 0;
		$level = $last_cia_paths;
	}
	else
	{
		if (0 <= $level) $base = 0;

		$i = $last_cia_paths - $level - $base;

		if (0 > $i) $i = 0;
		else if ($i > $last_cia_paths) $i = $last_cia_paths;
	}

	$GLOBALS['cia_lastpath_level'] =& $level;

	$file = strtr($file, '\\', '/');

	if ('class/' == substr($file, 0, 6)) $paths =& $GLOBALS['cia_include_paths'];
	else $paths =& $GLOBALS['cia_paths'];

	$nb_paths = count($paths);

	for (; $i < $nb_paths; ++$i, --$level)
	{
		$source = $paths[$i] .'/'. (0<=$level ? $file : substr($file, 6));
		if (file_exists($source) && (!CIA_WINDOWS || is_file($source) || is_dir($source) || is_link($source))) return $source;
	}

	return false;
}
// }}}

// {{{ function processPath(): resolvePath + macro preprocessor
function processPath($file, $level = false, $base = false)
{
	$source = resolvePath($file, $level, $base);

	if (false === $source) return false;

	$level = $GLOBALS['cia_lastpath_level'];

	$file = strtr($file, '\\', '/');
	$cache = ((int)(bool)DEBUG) . (0>$level ? -$level .'-' : $level);
	$cache = './.'. strtr(str_replace('_', '%2', str_replace('%', '%1', $file)), '/', '_') . ".{$cache}.{$GLOBALS['cia_paths_token']}.zcache.php";

	if (file_exists($cache)) return $cache;

	$class = 0<=$level
		&& 'class/' == substr($file, 0, 6)
		&& false === strpos($file, '_')
		&& '.php' == substr($file, -4)
		&& false === strpos($class = substr($file, 6, -4), '.')
		? strtr($class, '/', '_')
		: false;

	CIA_preprocessor::run($source, $cache, $level, $class);

	return $cache;
}
// }}}

// {{{ function cia_adaptRequire(): automatically added by the preprocessor in files in the include_path
function cia_adaptRequire($file)
{
	$file = strtr($file, '\\', '/');
	$f = '.' . $file . '/';

	if (false !== strpos($f, './') || false !== strpos($file, ':'))
	{
		$f = realpath($file);
		if (!$f) return $file;

		$file = false;
		$i = count($GLOBALS['cia_paths']);
		$p =& $GLOBALS['cia_include_paths'];
		$len = count($p);

		for (; $i < $len; ++$i)
		{
			if (substr($f, 0, strlen($p[$i])+1) == $p[$i] . DIRECTORY_SEPARATOR)
			{
				$file = substr($f, strlen($p[$i])+1);
				break;
			}
		}

		if (false === $file) return $f;
	}

	return processPath('class/' . $file);
}
// }}}

// {{{ function __autoload()
function __autoload($searched_class)
{
	$a = strtolower($searched_class);

	if ($a =& $GLOBALS['cia_autoload_cache'][$a] && !DEBUG)
	{
		if (is_int($a))
		{
			$b = $a; unset($a);
			$a = $b - $GLOBALS['cia_paths_offset'];
			$a = $searched_class . '.php.0' . (0>$a ? -$a .'-' : $a);
		}

		include "./.class_{$a}.{$GLOBALS['cia_paths_token']}.zcache.php";

		if (class_exists($searched_class, 0)) return;
	}

	function_exists('cia_autoload') || require __CIA__ . '/autoload.php';

	cia_autoload($searched_class);
}
// }}}

function cia_is_a($obj, $class)
{
	return $obj instanceof $class;
}

}

// {{{ Debug context
DEBUG && CIA_debug::checkCache();
// }}}

// {{{ Language controler
if (!$_SERVER['CIA_LANG'] && $CONFIG['lang_list']) CIA_language::negociate();
// }}}

// {{{ Validator
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && !isset($_SERVER['HTTP_IF_NONE_MATCH'])) // Special behaviour thanks to IE
{
	$match = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
	$_SERVER['HTTP_IF_NONE_MATCH'] = '"-' . dechex(strtotime($match[0])) . '"';
}

if (
	isset($_SERVER['HTTP_IF_NONE_MATCH'])
	&& 11 == strlen($_SERVER['HTTP_IF_NONE_MATCH'])
	&& '"#--------"' == strtr($_SERVER['HTTP_IF_NONE_MATCH'], '-0123456789abcdef', '#----------------'))
{
	$match = $_SERVER['HTTP_IF_NONE_MATCH'];
	$match = resolvePath('zcache/') . $match[2] .'/'. $match[3] .'/'. substr($match, 4) .'.validator.'. DEBUG .'.';
	$match .= md5($_SERVER['CIA_HOME'] .'-'. $_SERVER['CIA_LANG'] .'-'. CIA_PROJECT_PATH .'-'. $_SERVER['REQUEST_URI']) . '.txt';

	$headers = false;
	if (file_exists($match)) $headers = file_get_contents($match);
	if (false !== $headers)
	{
		header('HTTP/1.1 304 Not Modified');
		if ($headers)
		{
			$headers = explode("\n", $headers, 3);

			$match = $headers[0];

			$headers[0] = 'Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $_SERVER['REQUEST_TIME'] + $match);
			$headers[1] = 'Cache-Control: max-age=' . $match . ((int) $headers[1] ? ',private,must' : ',public,proxy') . '-revalidate';

			array_map('header', $headers);
		}

		exit;
	}
}
// }}}

/// {{{ Anti Cross-Site-(Request-Forgery|Javascript-Request) token
$_POST_BACKUP =& $_POST;

if (
	isset($_COOKIE['T$'])
	&& (!CIA_POSTING || (isset($_POST['T$']) && $_COOKIE['T$'] === $_POST['T$']))
	&& '---------------------------------' == strtr($_COOKIE['T$'], '-0123456789abcdef', '#----------------')
) $cia_token = $_COOKIE['T$'];
else
{
	$a = isset($_COOKIE['T$']) && '1' == substr($_COOKIE['T$'], 0, 1) ? '1' : '0';

	if ($_COOKIE)
	{
		if (CIA_POSTING) W('Potential Cross Site Request Forgery. $_POST is not reliable. Erasing it !');

		unset($_POST);
		$_POST = array();

		unset($_COOKIE['T$']);
		unset($_COOKIE['T$']); // Double unset against a PHP security hole
	}

	$cia_token = $a . md5(uniqid(mt_rand(), true));

	$a = implode($_SERVER['CIA_LANG'], explode('__', $_SERVER['CIA_HOME'], 2));
	$a = preg_replace("'\?.*$'", '', $a);
	$a = preg_replace("'^https?://[^/]*'i", '', $a);
	$a = dirname($a . ' ');
	if (1 == strlen($a)) $a = '';

	setcookie('T$', $cia_token, 0, $a .'/');
	header('Vary: *');
}

define('CIA_TOKEN_MATCH', isset($_GET['T$']) && $cia_token === $_GET['T$']);
// }}}

/* Let's go */
CIA::start();
