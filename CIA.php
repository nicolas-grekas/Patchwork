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
session.use_cookies = 0
session.use_trans_sid = 0

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

// $_REQUEST is an open door to security problems.
unset($_REQUEST);
unset($_REQUEST); // Double unset against a PHP security hole

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

// {{{ Load configuration

ini_set('error_log', $CIA . '/error.log');
ini_set('log_errors', true);
ini_set('display_errors', false);

$CONFIG = array();
$version_id = $CIA . '/.config.zcache.php';

define('__CIA__', dirname(__FILE__));
define('CIA_WINDOWS', '\\' == DIRECTORY_SEPARATOR);
define('CIA_CHECK_SOURCE', isset($_SERVER['HTTP_CACHE_CONTROL']) && 'no-cache' == $_SERVER['HTTP_CACHE_CONTROL']);

require !CIA_CHECK_SOURCE && file_exists($version_id)
	? $version_id
	: (__CIA__ . '/c3mro.php');

if (!isset($CONFIG['DEBUG'])) $CONFIG['DEBUG'] = (int) @$CONFIG['DEBUG_KEYS'][ (string) $_COOKIE['DEBUG'] ];
if (isset($CONFIG['clientside']) && !$CONFIG['clientside']) $_GET['$bin'] = true;

define('CIA_PROJECT_PATH', $cia_paths[0]);
chdir(CIA_PROJECT_PATH);

ini_set('error_log', CIA_PROJECT_PATH . '/error.log');

// Restore the current dir in shutdown context.
register_shutdown_function('chdir', CIA_PROJECT_PATH);

// }}}

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
	$_SERVER['CIA_HOME'] = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
	$_SERVER['CIA_LANG'] = $_SERVER['CIA_REQUEST'] = '';

	$lang_rx = '([a-z]{2}(?:-[A-Z]{2})?)';

	if ($CONFIG['use_path_info'])
	{
		if (isset($_SERVER['ORIG_PATH_INFO'])) $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];

		$_SERVER['CIA_HOME'] .= '/__/';

		if (isset($_SERVER['PATH_INFO']) && preg_match("'^/{$lang_rx}/?(.*)$'", $_SERVER['PATH_INFO'], $a))
		{
			$_SERVER['CIA_LANG']    = $a[1];
			$_SERVER['CIA_REQUEST'] = $a[2];
		}
	}
	else
	{
		$_SERVER['CIA_HOME'] .= '?__/';

		if (isset($_SERVER['QUERY_STRING']) && preg_match("'^{$lang_rx}/?([^\?]*)(\??)'", rawurldecode($_SERVER['QUERY_STRING']), $a))
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
				unset($_GET[ key($_GET) ]); // Double unset against a PHP security hole
			}
		}
	}
}
else if (!strncmp('/', $_SERVER['CIA_HOME'], 1)) $_SERVER['CIA_HOME'] = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['CIA_HOME'];
// }}}

// {{{ Global Initialisation
define('DEBUG',			(int) $CONFIG['DEBUG']);
define('CIA_MAXAGE',	$CONFIG['maxage']);
define('CIA_POSTING', 'POST' == $_SERVER['REQUEST_METHOD']);
define('CIA_DIRECT', '_' == $_SERVER['CIA_REQUEST']);

if (DEBUG) $version_id = -$version_id;

function E($msg = '__getDeltaMicrotime')
{
	return CIA::log($msg, false, false);
}

if (function_exists('date_default_timezone_set') && isset($CONFIG['timezone'])) date_default_timezone_set($CONFIG['timezone']);
// }}}



{ // Hack to enable the 3 functions below only when execution reaches this point

// {{{ function resolvePath(): cia-specific include_path-like mechanism
function resolvePath($file, $level = false, $base = false)
{
	$paths =& $GLOBALS['cia_paths'];
	$len = count($paths);
	$i = 0;

	if (false !== $level)
	{
		if (0 <= $level) $base = 0;

		$i = $len - $level - $base - 1;

		if (0 > $i) $i = 0;
		else if ($i >= $len) $i = $len - 1;
	}

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
function processPath($file, $level = false, $base = false)
{
	$paths =& $GLOBALS['cia_paths'];
	$len = count($paths);
	$i = 0;

	if (false !== $level)
	{
		if (0 <= $level) $base = 0;

		$i = $len - $level - $base - 1;

		if (0 > $i) $i = 0;
		else if ($i >= $len) $i = $len - 1;
	}

	if (DEBUG || CIA_CHECK_SOURCE) $depth =& $i;
	else $depth = $i;

	$c = './.'. $GLOBALS['cia_paths_token'] . str_replace(array('_', '/', '\\'), array('__', '_', '_'), $file) .'.'. (int)(bool)DEBUG .'a';

	do
	{
		$source = $paths[$i] . '/' . $file;
		$cache = $c . $depth .'.zcache.php';

		if (
			file_exists($cache) && (!CIA_CHECK_SOURCE
			|| (file_exists($source) && filemtime($cache) >= filemtime($source)))
		) ;
		else if (file_exists($source))
		{
			function_exists('runPreprocessor') || require resolvePath('preprocessor.php');

			runPreprocessor($source, $cache, $len - $i - 1);
		}
		else $cache = false;

		if ($cache) return $cache;
	}
	while (++$i < $len);

	return $file;
}
// }}}

// {{{ function __autoload()
$__autoload_static_pool = false;

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
	$cache = false;

	if ('_' == substr($class, -1) || !strncmp('_', $class, 1) || false !== strpos($class, '__')) // Out of the path class: search for an existing parent
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

		$c = './.'. $GLOBALS['cia_paths_token'] . $class .'.'. (int)(bool)DEBUG .'b';

		do
		{
			$parent_class = $class . '__' . --$level;

			if (class_exists($parent_class, false)) break;

			$source = $paths[++$i] . '/' . $file;
			$cache = $c . $i .'.zcache.php';

			if (
				file_exists($cache) && (!CIA_CHECK_SOURCE
				|| (file_exists($source) && filemtime($cache) >= filemtime($source)))
			) ;
			else if (file_exists($source))
			{
				function_exists('runPreprocessor') || require resolvePath('preprocessor.php');

				runPreprocessor($source, $cache, $level, $class);
			}
			else $cache = false;

			if ($cache)
			{
				$current_pool = array();
				$parent_pool =& $GLOBALS['__autoload_static_pool'];
				$GLOBALS['__autoload_static_pool'] =& $current_pool;

				require $cache;

				if (class_exists($searched_class, false)) $parent_class = false;

				if (false !== $parent_pool) $parent_pool[$parent_class ? $parent_class : $searched_class] = array(0, $cache);

				break;
			}
		}
		while ($level);
	}

	if ($parent_class && class_exists($parent_class, true))
	{
		$class = new ReflectionClass($parent_class);
		$class = ($class->isAbstract() ? 'abstract ' : '') . 'class ' . $searched_class . ' extends ' . $parent_class . '{}';

		eval($class);
	}
	else $class = '';

	if ($cache)
	{
		if ($parent_pool && $class) $parent_pool[$searched_class] = array(1, '<?php ' . $class . '?>');

		$GLOBALS['__autoload_static_pool'] =& $parent_pool;


		if ($current_pool) // Writes parent's source code in child's source file
		{
			$code = '<?php ?>';
			$tmp = file_get_contents($cache);

			if ('<?php ' != substr($tmp, 0, 6)) $tmp = '<?php ?>' . $tmp;

			foreach ($current_pool as $class => &$c)
			{
				if ($c[0])
				{
					$c =& $c[1];

					if ('<?php ' != substr($c, 0, 6)) $c = '<?php ?>' . $c;
					if ('?>' != substr($c, -2)) $c .= '<?php ?>';

					$code = substr($code, 0, -2) . "if(!class_exists('$class',0)){" . substr($c, 6, -2) . '}?>';
				}
				else $code = substr($code, 0, -2) . "class_exists('{$class}',0)||require '{$c[1]}';?>";
			}

			$code = substr($code, 0, -2) . ';' . substr($tmp, 6);


			$tmp = md5(uniqid(mt_rand(), true));

			file_put_contents($tmp, $code);

			if (CIA_WINDOWS)
			{
				$code = new COM('Scripting.FileSystemObject');
				$code->GetFile($GLOBALS['cia_paths'][0] . '/' . $tmp)->Attributes |= 2;
				file_exists($cache) && unlink($cache);
				@rename($tmp, $cache) || E('Failed rename');
			}
			else rename($tmp, $cache);
		}
	}
}
// }}}

}

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

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && !strncmp($_SERVER['HTTP_IF_NONE_MATCH'], '-', 1) && preg_match("'^-[0-9a-f]{8}$'", $_SERVER['HTTP_IF_NONE_MATCH'], $match))
{
	$_SERVER['HTTP_IF_NONE_MATCH'] = substr($_SERVER['HTTP_IF_NONE_MATCH'], 1);

	$match = $match[0];
	$match = resolvePath('zcache/') . $match[1] .'/'. $match[2] .'/'. substr($match, 3) .'.validator.'. DEBUG .'.';
	$match .= md5($_SERVER['CIA_HOME'] .'-'. $_SERVER['CIA_LANG'] .'-'. CIA_PROJECT_PATH .'-'. $_SERVER['REQUEST_URI']) . '.txt';

	$headers = false;
	if (file_exists($match)) $headers = file_get_contents($match);
	if (false !== $headers)
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
	unset($_COOKIE['T$']); // Double unset against a PHP security hole
	define('CIA_TOKEN', md5(uniqid(mt_rand(), true)));

	$k = implode($_SERVER['CIA_LANG'], explode('__', $_SERVER['CIA_HOME'], 2));
	$k = preg_replace("'\?.*$'", '', $k);
	$k = preg_replace("'^https?://[^/]*'i", '', $k);
	$k = dirname($k . ' ');
	if (1 == strlen($k)) $k = '';

	setcookie('T$', CIA_TOKEN, 0, $k .'/');
}
else
{
	define('CIA_TOKEN', $_COOKIE['T$']);

	if (CIA_POSTING && (!isset($_POST['T$']) || $_COOKIE['T$'] != $_POST['T$']))
	{
		E('Potential Cross Site Request Forgery. $_POST is not reliable. Erasing it !');
		E($_SERVER); E($_POST); E($_COOKIE);

		$_POST = array();
	}
}

define('CIA_TOKEN_MATCH', isset($_GET['T$']) && CIA_TOKEN == $_GET['T$']);

// }}}

/* Let's go */
CIA::start();

exit;
