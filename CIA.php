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

function registerAutoloadPrefix($class_prefix, $class_to_file_callback)
{
	if ($len = strlen($class_prefix))
	{
		$registry =& $GLOBALS['cia_autoload_prefix'];
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

// {{{ hunter: a user callback is called when a hunter object is destroyed
class hunter
{
	protected $callback;
	protected $param_arr;

	function __construct($callback, $param_arr = array())
	{
		$this->callback =& $callback;
		$this->param_arr =& $param_arr;
	}

	function __destruct()
	{
		call_user_func_array($this->callback, $this->param_arr);
	}
}
// }}}

{ // <-- Hack to enable the next function only when execution reaches this point
	
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

}

// {{{ Load configuration
chdir($CIA);

// $_REQUEST is an open door to security problems.
$_REQUEST = array();

$CONFIG = array();
$version_id = './.config.zcache.php';

define('__CIA__', dirname(__FILE__));
define('CIA_WINDOWS', '\\' == DIRECTORY_SEPARATOR);
define('CIA_PROJECT_PATH', getcwd());

// Load the configuration
require file_exists($version_id) ? $version_id : (__CIA__ . '/c3mro.php');

if (isset($CONFIG['clientside']) && !$CONFIG['clientside']) $_GET['$bin'] = true;

// Restore the current dir in shutdown context.
function cia_restoreProjectPath() {CIA_PROJECT_PATH != getcwd() && chdir(CIA_PROJECT_PATH);}
register_shutdown_function('cia_restoreProjectPath', CIA_PROJECT_PATH);
// }}}

// {{{ Global Initialisation
define('DEBUG',       $CONFIG['DEBUG_ALLOWED'] && (!$CONFIG['DEBUG_PASSWORD'] || (isset($_COOKIE['DEBUG']) && $CONFIG['DEBUG_PASSWORD'] == $_COOKIE['DEBUG'])) ? 1 : 0);
define('CIA_MAXAGE',  isset($CONFIG['maxage']) ? $CONFIG['maxage'] : 2678400);
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


{ // <-- Hack to enable the next functions only when execution reaches this point

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

// {{{ Validator
$a = isset($_SERVER['HTTP_IF_NONE_MATCH'])
	? $_SERVER['HTTP_IF_NONE_MATCH']
	: isset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

if ($a)
{
	if (true === $a) // Special behaviour thanks to IE
	{
		$a = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
		$a = '"-' . dechex(strtotime($a[0])) . '"';
	}

	if (11 == strlen($a) && '"#--------"' == strtr($a, '-0123456789abcdef', '#----------------'))
	{
		$a = $cia_zcache . $a[2] .'/'. $a[3] .'/'. substr($a, 4) .'.validator.'. DEBUG .'.';
		$a .= md5($_SERVER['CIA_BASE'] .'-'. $_SERVER['CIA_LANG'] .'-'. CIA_PROJECT_PATH .'-'. $_SERVER['REQUEST_URI']) . '.txt';

		$b = false;
		if (file_exists($a)) $b = file_get_contents($a);
		if (false !== $b)
		{
			header('HTTP/1.1 304 Not Modified');
			if ($b)
			{
				$b = explode("\n", $b, 3);

				$a = $b[0];

				$b[0] = 'Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $_SERVER['REQUEST_TIME'] + $a);
				$b[1] = 'Cache-Control: max-age=' . $a . ((int) $b[1] ? ',private,must' : ',public,proxy') . '-revalidate';

				array_map('header', $b);
			}

			exit;
		}
	}
}
// }}}

/// {{{ Anti Cross-Site-Request-Forgery / Javascript-Hijacking token
$_POST_BACKUP =& $_POST;

if (
	isset($_COOKIE['T$'])
	&& (!CIA_POSTING || (isset($_POST['T$']) && substr($_COOKIE['T$'], 1) == substr($_POST['T$'], 1)))
	&& '---------------------------------' == strtr($_COOKIE['T$'], '-0123456789abcdef', '#----------------')
) $cia_token = $_COOKIE['T$'];
else
{
	$a = isset($_COOKIE['T$']) && '1' == substr($_COOKIE['T$'], 0, 1) ? '1' : '2';

	if ($_COOKIE)
	{
		if (CIA_POSTING) W('Potential Cross Site Request Forgery. $_POST is not reliable. Erasing it !');

		unset($_POST);
		$_POST = array();

		unset($_COOKIE['T$']);
		unset($_COOKIE['T$']); // Double unset against a PHP security hole
	}

	$cia_token = $a . md5(uniqid(mt_rand(), true));

	header('P3P: CP="' . $CONFIG['P3P'] . '"');
	setcookie('T$', $cia_token, 0, $CONFIG['session.cookie_path'], $CONFIG['session.cookie_domain']);
	$cia_private = true;
}

isset($_GET['T$']) && $cia_private = true;
define('CIA_TOKEN_MATCH', isset($_GET['T$']) && substr($cia_token, 1) == substr($_GET['T$'], 1));
// }}}

// {{{ Version synchronism
$b = abs($version_id % 10000);

if (!isset($_COOKIE['v$']) || $_COOKIE['v$'] != $b)
{
	$a = implode($_SERVER['CIA_LANG'], explode('__', $_SERVER['CIA_BASE'], 2));
	$a = preg_replace("'\?.*$'", '', $a);
	$a = preg_replace("'^https?://[^/]*'i", '', $a);
	$a = dirname($a . ' ');
	if (1 == strlen($a)) $a = '';

	header('P3P: CP="' . $CONFIG['P3P'] . '"');
	setcookie('v$', $b, $_SERVER['REQUEST_TIME'] + CIA_MAXAGE, $a .'/');
	$cia_private = true;
}
// }}}

// {{{ Language controler
if (!$_SERVER['CIA_LANG'] && $CONFIG['lang_list']) CIA_language::negociate();
// }}}

/* Let's go */
CIA::start();
