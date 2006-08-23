<?php

define('CIA', microtime(true)); isset($_SERVER['REQUEST_TIME']) || $_SERVER['REQUEST_TIME'] = time();

// {{{ Server configuration helper
/* Comment this section if your server's config is ok */

if (get_magic_quotes_gpc())
{
	if (ini_get('magic_quotes_sybase')) { function _q_(&$a) {is_array($a) ? array_walk($a, '_q_') : $a = str_replace("''", "'", $a);} }
	else { function _q_(&$a) {is_array($a) ? array_walk($a, '_q_') : $a = stripslashes($a);} }
	_q_($_GET);_q_($_POST);_q_($_COOKIE);
}

set_magic_quotes_runtime(0);

/* To enable UTF-8 when using MySQL, add the following lines at the end of your my.cnf or my.ini file

default-character-set=utf8
init-connect="SET NAMES utf8"

*/

/* Copy/Paste the next block at the end of your php.ini

log_errors = On

; Replace this to your needs
error_log = c:/windows/temp/php.log

magic_quotes_gpc = Off
magic_quotes_runtime = Off

variables_order = "GPCES"
register_globals = Off
register_long_arrays = Off
register_argc_argv = Off
auto_globals_jit = On

session.auto_start = 0
session.use_only_cookies = 1

mbstring.language = neutral
mbstring.script_encoding = UTF-8
mbstring.internal_encoding = UTF-8

mbstring.encoding_translation = On
mbstring.detect_order = auto
mbstring.http_input = auto
mbstring.http_output = pass

mbstring.substitute_character = none

; String's functions overloading prevents binary use of a string. Use mb_* functions instead
mbstring.func_overload = 0

*/
// }}}

// {{{ Global context setup

// $_REQUEST is an open door to security problems
unset($_REQUEST);

// Globals vars initialization
$CONFIG = array();
$cia_paths = array();
$version_id = 0;

// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with native PHP output compression.
if (function_exists('apache_setenv')) apache_setenv('no-gzip', '1');

// Encoding context initialisation
@putenv('LC_ALL=en_US.UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');
if (function_exists('iconv_set_encoding'))
{
	iconv_set_encoding('input_encoding',    'UTF-8');
	iconv_set_encoding('internal_encoding', 'UTF-8');
	iconv_set_encoding('output_encoding',   'UTF-8');
}

// }}}

// {{{ function CIA(): bootstrap
function CIA($file, $parent = '../../config.php')
{
	global $CONFIG, $cia_paths, $version_id;

	$version_id += filemtime($file);

	$file = dirname($file);

	if (!defined('CIA_PROJECT_PATH'))
	{
		define('CIA_PROJECT_PATH', $file);
		chdir($file);
	}

	$cia_paths[] = $file;

	if (false !== $parent)
	{
		if (
			    '/' != $parent[0]
			&& '\\' != $parent[0]
			&&  ':' != $parent[1]
		) $parent = $file . '/' . $parent;

		require $parent;
	}
}
// }}}

// {{{ function resolvePath(): cia-specific include_path-like mechanism
function resolvePath($file, $level = false)
{
	$paths =& $GLOBALS['cia_paths'];

	$len = count($paths);
	$i = false !== $level && $level < $len ? $len - $level - 1 : 0;

	do
	{
		$path = $paths[$i] . '/';
		if (file_exists($path . $file)) return $path . $file;
	}
	while (++$i < $len);

	return $file;
}
// }}}

// {{{ function processPath(): resolvePath + macro preprocessor
function processPath($file, $level = false)
{
	$paths =& $GLOBALS['cia_paths'];

	$len = count($paths);
	$i = false !== $level && $level < $len ? $len - $level - 1 : 0;

	if (DEBUG) $depth =& $i;
	else $depth = $i;

	$c = '.'. str_replace(array('_', '/', '\\'), array('__', '_', '_'), $file) .'.'. (int)(bool)DEBUG .'a';

	do
	{
		$source = $paths[$i] . '/' . $file;
		$cache = $c . $depth .'.zcache.php';

		if (file_exists($cache));
		else if (file_exists($source)) require resolvePath('preprocessor.php');
		else $cache = false;

		if ($cache) return $cache;
	}
	while (++$i < $len);

	return $file;
}
// }}}

// {{{ function __autoload()
function __autoload($searched_class)
{
	if (preg_match("'^(.+)__(0|[1-9][0-9]*)$'", $searched_class, $class_level)) // Namespace renammed class
	{
		$class = $class_level[1];
		$class_level = (int) $class_level[2];
	}
	else
	{
		$class = $searched_class;
		$class_level = -1;
	}

	$level = $class_level>=0 ? $class_level + 1 : count($GLOBALS['cia_paths']);

	if ('_' == substr($class, -1) || '_' == substr($class, 0, 1) || false !== strpos($class, '__')) // Out of the path class: search for an existing parent
	{
		if ($class_level >= 0) --$level;

		do $parent_class = $class . '__' . --$level;
		while ($level && !class_exists($parent_class, false));
	}
	else // Conventional class: search its definition on disk
	{
		$file = 'class/' . str_replace('_', '/', $class) . '.php';
		$i = $class_level>=0 ? count($GLOBALS['cia_paths']) - $class_level - 2 : -1;
		$paths =& $GLOBALS['cia_paths'];
		$parent_class = false;

		$c = '.'. $class .'.'. (int)(bool)DEBUG .'b';

		do
		{
			$parent_class = $class . '__' . --$level;

			if (class_exists($parent_class, false)) break;

			$source = $paths[++$i] . '/' . $file;
			$cache = $c . $i .'.zcache.php';

			if (file_exists($cache));
			else if (file_exists($source)) require processPath('classRewriter.php');
			else $cache = false;

			if ($cache)
			{
				require $cache;

				if (class_exists($searched_class, false)) return;

				break;
			}
		}
		while ($level);
	}

	if (class_exists($parent_class, true))
	{
		$class = new ReflectionClass($parent_class);

		eval(($class->isAbstract() ? 'abstract ' : '') . 'class ' . $searched_class . ' extends ' . $parent_class . '{}');
	}
}
// }}}

// {{{ Shortcut functions for applications developpers
function G($name, $type) {$a = func_get_args(); return VALIDATE::get(    $_GET[$name]   , $type, array_slice($a, 2));}
function P($name, $type) {$a = func_get_args(); return VALIDATE::get(    $_POST[$name]  , $type, array_slice($a, 2));}
function C($name, $type) {$a = func_get_args(); return VALIDATE::get(    $_COOKIE[$name], $type, array_slice($a, 2));}
function F($name, $type) {$a = func_get_args(); return VALIDATE::getFile($_FILES[$name] , $type, array_slice($a, 2));}

function V($var , $type) {$a = func_get_args(); return VALIDATE::get(     $var          , $type, array_slice($a, 2));}

function T($string, $lang = false)
{
	if (!$lang) $lang = CIA::__LANG__();
	return TRANSLATE::get($string, $lang, true);
}
// }}}

// {{{ function CIA_GO()
function CIA_GO($file, $use_path_info)
{
	CIA($file, false);
	global $CONFIG, $cia_paths, $version_id;

	// {{{ CIA's environment context
	/**
	* Setup needed environment variables if they don't exists :
	*   $_SERVER['CIA_HOME']: application's home part of the url. Lang independant (ex. /cia/myapp/__/)
	*   $_SERVER['CIA_LANG']: lang (ex. en)
	*   $_SERVER['CIA_REQUEST']: request part of the url (ex. myagent/mysubagent/...)
	*
	* You can also define these vars with mod_rewrite, to get cleaner urls
	*/
	if (!isset($_SERVER['CIA_HOME']))
	{
		$_SERVER['CIA_HOME'] = 'http' . (@$_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
		$_SERVER['CIA_LANG'] = $_SERVER['CIA_REQUEST'] = '';

		$lang_rx = '([a-z]{2}(?:-[A-Z]{2})?)';

		if ($use_path_info)
		{
			if (isset($_SERVER['ORIG_PATH_INFO'])) $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];

			$_SERVER['CIA_HOME'] .= '/__/';

			if (preg_match("'^/{$lang_rx}/?(.*)$'", @$_SERVER['PATH_INFO'], $a))
			{
				$_SERVER['CIA_LANG']    = $a[1];
				$_SERVER['CIA_REQUEST'] = $a[2];
			}
		}
		else
		{
			$_SERVER['CIA_HOME'] .= '?__/';

			if (preg_match("'^{$lang_rx}/?([^\?]*)(\??)'", rawurldecode(@$_SERVER['QUERY_STRING']), $a))
			{
				$_SERVER['CIA_LANG']    = $a[1];
				$_SERVER['CIA_REQUEST'] = $a[2];

				if ($a[3])
				{
					$_GET = array();
					$_SERVER['QUERY_STRING'] = preg_replace("'^.*?(\?|%3F)'i", '', $_SERVER['QUERY_STRING']);
					parse_str($_SERVER['QUERY_STRING'], $_GET);
				}
				else
				{
					$_SERVER['QUERY_STRING'] = null;
					unset($_GET[ key($_GET) ]);
				}
			}
		}
	}
	else if ('/' == substr($_SERVER['CIA_HOME'], 0, 1)) $_SERVER['CIA_HOME'] = 'http' . (@$_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['CIA_HOME'];
	// }}}

	// {{{ Default database support with MDB2
	if (!function_exists('DB'))
	{
		function DB($close = false)
		{
			static $db = false;

			if ($close)
			{
				if ($db) $db->commit();
			}
			else if (!$db)
			{
				require_once 'MDB2.php';

				global $CONFIG;

				$db = @MDB2::factory($CONFIG['DSN']);
				$db->loadModule('Extended');
				$db->setErrorHandling(PEAR_ERROR_CALLBACK, 'E');
				$db->setFetchMode(MDB2_FETCHMODE_OBJECT);
				$db->setOption('default_table_type', 'InnoDB');
				$db->setOption('seqname_format', 'zeq_%s');
				$db->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL ^ MDB2_PORTABILITY_FIX_CASE);

				$db->connect();

				if(@PEAR::isError($db))
				{
					trigger_error($db->getMessage(), E_USER_ERROR);
					exit;
				}

				$db->beginTransaction();

				$db->query('SET NAMES utf8');
				$db->query("SET collation_connection='utf8_general_ci'");
			}

			return $db;
		}
	}
	// }}}

	// {{{ Global Initialisation
	define('DEBUG',			(int) $CONFIG['DEBUG']);
	define('CIA_MAXAGE',	$CONFIG['maxage']);
	define('CIA_PROJECT_ID', abs($version_id % 10000));
	define('CIA_POSTING', 'POST' == $_SERVER['REQUEST_METHOD']);
	define('CIA_DIRECT', '_' == $_SERVER['CIA_REQUEST']);

	function E($msg = '__getDeltaMicrotime')
	{
		if (class_exists('CIA_debug', false)) return CIA::ciaLog($msg, false, false);

		trigger_error(serialize($msg));
	}

	if (function_exists('date_default_timezone_set') && isset($CONFIG['timezone'])) date_default_timezone_set($CONFIG['timezone']);
	// }}}

	// {{{ Language controler
	if (!$_SERVER['CIA_LANG'])
	{
		require processPath('language.php');
		exit;
	}
	// }}}

	// {{{ Validator
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && !isset($_SERVER['HTTP_IF_NONE_MATCH'])) // Special behaviour thanks to IE
	{
		$match = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
		$_SERVER['HTTP_IF_NONE_MATCH'] = '-' . dechex(strtotime($match[0]));
	}

	if ('-' == @$_SERVER['HTTP_IF_NONE_MATCH'][0] && preg_match("'^-[0-9a-f]{8}$'", $_SERVER['HTTP_IF_NONE_MATCH'], $match))
	{
		$_SERVER['HTTP_IF_NONE_MATCH'] = substr($_SERVER['HTTP_IF_NONE_MATCH'], 1);

		$match = $match[0];
		$match = resolvePath('zcache/') . $match[1] .'/'. $match[2] .'/'. substr($match, 3) .'.validator.'. DEBUG .'.';
		$match .= md5($_SERVER['CIA_HOME'] .'-'. $_SERVER['CIA_LANG'] .'-'. CIA_PROJECT_PATH .'-'. $_SERVER['REQUEST_URI']) . '.txt';

		$headers = @file_get_contents($match);
		if ($headers !== false)
		{
			header('HTTP/1.x 304 Not Modified');
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

	// {{{ Output debug window
	if (DEBUG && CIA_DIRECT && isset($_GET['d$']))
	{
		require processPath('debug.php');
		exit;
	}
	// }}}

	/// {{{ Anti Cross-Site-(Request-Forgery|Javascript) token
	if (!isset($_COOKIE['T$']) || !$_COOKIE['T$'])
	{
		unset($_COOKIE['T$']);
		define('CIA_TOKEN', md5(uniqid(mt_rand(), true)));

		$k = implode($_SERVER['CIA_LANG'], explode('__', $_SERVER['CIA_HOME'], 2));
		$k = preg_replace("'\?.*$'", '', $k);
		$k = preg_replace("'^https?://[^/]*'i", '', $k);
		$k = dirname($k . ' ');
		if (1 == strlen($k)) $k = '';

		setcookie('T$', CIA_TOKEN, 0, $k .'/');
	}
	else define('CIA_TOKEN', $_COOKIE['T$']);

	define('CIA_TOKEN_MATCH', isset($_GET['T$']) && CIA_TOKEN == $_GET['T$']);
	// }}}

	/* Let's go */
	CIA::start();
	exit;
}
// }}}
