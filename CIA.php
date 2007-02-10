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
isset($_SERVER['REQUEST_TIME']) || $_SERVER['REQUEST_TIME'] = time();

// {{{ Global context setup
// $_REQUEST is an open door to security problems.
unset($_REQUEST);
unset($_REQUEST); // Double unset against a PHP security hole

// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with our own output compression.
function_exists('apache_setenv') && apache_setenv('no-gzip', '1');

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

// {{{ registerAutoloadPrefix()
$__cia_autoload_prefix = array();

function registerAutoloadPrefix($prefix, $class2file_resolver)
{
	$prefix = strtolower($prefix);
	$registry = array();

	foreach ($GLOBALS['__cia_autoload_prefix'] as $v)
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

	$GLOBALS['__cia_autoload_prefix'] =& $registry;
}
// }}}

// {{{ Load configuration
chdir($CIA);

ini_set('error_log', './error.log');

$CONFIG = array();
$version_id = './.config.zcache.php';

define('__CIA__', dirname(__FILE__));
define('CIA_WINDOWS', '\\' == DIRECTORY_SEPARATOR);
define('CIA_CHECK_SOURCE', isset($_SERVER['HTTP_CACHE_CONTROL']) && 'no-cache' == $_SERVER['HTTP_CACHE_CONTROL']);

// As of PHP5.1.2, hash('md5', $str) is a lot faster than md5($str) !
function_exists('hash_algos') || require __CIA__ . '/hash.php';

require !CIA_CHECK_SOURCE && file_exists($version_id)
	? $version_id
	: (__CIA__ . '/c3mro.php');

if (!isset($CONFIG['DEBUG'])) $CONFIG['DEBUG'] = (int) @$CONFIG['DEBUG_KEYS'][ (string) $_COOKIE['DEBUG'] ];
if (isset($CONFIG['clientside']) && !$CONFIG['clientside']) $_GET['$bin'] = true;

define('CIA_PROJECT_PATH', $cia_paths[0]);

// Restore the current dir at shutdown context.
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
				$a = key($_GET);
				unset($_GET[$a]);
				unset($_GET[$a]); // Double unset against a PHP security hole
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

function_exists('date_default_timezone_set') && isset($CONFIG['timezone']) && date_default_timezone_set($CONFIG['timezone']);
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
		if (file_exists($source)) return $source;
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
	$cache = './.'. strtr(str_replace('_', '__', $file), '/', '_') . ".{$GLOBALS['cia_paths_token']}.{$cache}.zcache.php";

	if (file_exists($cache) && (!CIA_CHECK_SOURCE || filemtime($cache) >= filemtime($source))) return $cache;

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
$__autoload_static_pool = false;

function __autoload($searched_class)
{
	$last_cia_paths = count($GLOBALS['cia_paths']) - 1;

	if (false !== strpos($searched_class, ';')) return;

	$i = strrpos($searched_class, '__');
	$level = false !== $i ? substr($searched_class, $i+2) : false;

	if (false !== $level && '' !== $level && '' === ltrim(strtr($level, ' 0123456789 ', '#          ')))
	{
		// Namespace renammed class
		$class = substr($searched_class, 0, $i);
		$level = min($last_cia_paths, '00' == $level ? -1 : (int) $level);
	}
	else
	{
		$class = $searched_class;
		$level = $last_cia_paths;
	}

	$parent_class = $class . '__' . $level;
	$cache = false;

	if ('_' == substr($class, -1) || !strncmp('_', $class, 1) || false !== strpos($class, '__'))
	{
		// Out of the path class: search for an existing parent

		if ($class == $searched_class) ++$level;

		do $parent_class = $class . '__' . (0<=--$level ? $level : '00');
		while ($level>=0 && !class_exists($parent_class, false));
	}
	else if ($class != $searched_class || !class_exists($parent_class, false))
	{
		// Conventional class: search its parent in existing classes or on disk

		$file = false;
		$lcClass = strtolower($class);

		$i = $last_cia_paths - $level;
		if (0 > $i) $i = 0;

		if ($GLOBALS['__cia_autoload_prefix'])
		{
			foreach ($GLOBALS['__cia_autoload_prefix'] as $v)
			{
				if ($v[0] == substr($lcClass, 0, strlen($v[0])))
				{
					$file = call_user_func($v[1], $class);
					break;
				}
			}
		}

		$file || $file = strtr($class, '_', '/') . '.php';
		$file = 'class/' . $file;

		$paths =& $GLOBALS['cia_include_paths'];
		$nb_paths = count($paths);

		for (; $i < $nb_paths; ++$i)
		{
			$source = $paths[$i] .'/'. (0<=$level ? $file : substr($file, 6));

			if (file_exists($source))
			{
				switch ($lcClass)
				{
				case 'cia_preprocessor':
					require $source;
					break;

				default:
					$cache = ((int)(bool)DEBUG) . (0>$level ? -$level .'-' : $level);
					$cache = "./.class_{$class}.php.{$GLOBALS['cia_paths_token']}.{$cache}.zcache.php";

					if (file_exists($cache) && (!CIA_CHECK_SOURCE || filemtime($cache) >= filemtime($source))) ;
					else CIA_preprocessor::run($source, $cache, $level, $class);

					$current_pool = array();
					$parent_pool =& $GLOBALS['__autoload_static_pool'];
					$GLOBALS['__autoload_static_pool'] =& $current_pool;

					require $cache;

					if (class_exists($searched_class, false)) $parent_class = false;
					if (false !== $parent_pool) $parent_pool[$parent_class ? $parent_class : $searched_class] = array(0, $cache);
				}

				break;
			}

			--$level;

			$parent_class = $class . '__' . (0<=$level ? $level : '00');

			if (class_exists($parent_class, false)) break;
		}
	}

	$c = $searched_class == $class;

	if ($parent_class && class_exists($parent_class, true))
	{
		$class = new ReflectionClass($parent_class);
		$class = ($class->isAbstract() ? 'abstract ' : '') . 'class ' . $searched_class . ' extends ' . $parent_class . '{}';

		eval($class);
	}
	else $class = '';

	if ($c)
	{
		if (PHP_VERSION >= '5.1')
		{
			method_exists($searched_class, '__static_construct') && call_user_func(array($searched_class, '__static_construct'));
			method_exists($searched_class, '__static_destruct' ) && register_shutdown_function(array($searched_class, '__static_destruct'));
		}
		else
		{
			$c = get_class_methods($searched_class);
			in_array('__static_construct', $c) && call_user_func(array($searched_class, '__static_construct'));
			in_array('__static_destruct' , $c) && register_shutdown_function(array($searched_class, '__static_destruct'));
		}
	}

	if ($cache)
	{
		if ($parent_pool && $class) $parent_pool[$searched_class] = array(1, '<?php ' . $class . '?>');

		$GLOBALS['__autoload_static_pool'] =& $parent_pool;


		if ($current_pool) // Pre-include parent's code in this derivated class
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


			$tmp = uniqid(mt_rand(), true);

			file_put_contents($tmp, $code);

			if (CIA_WINDOWS)
			{
				$code = new COM('Scripting.FileSystemObject');
				$code->GetFile(CIA_PROJECT_PATH .'/'. $tmp)->Attributes |= 2; // Set hidden attribute
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
$_SERVER['CIA_LANG'] || require processPath('language.php');
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
	&& '"-        "' == strtr($_SERVER['HTTP_IF_NONE_MATCH'], ' 0123456789abcdef', '#                '))
{
	$match = $_SERVER['HTTP_IF_NONE_MATCH'];
	$match = resolvePath('zcache/') . $match[2] .'/'. $match[3] .'/'. substr($match, 4) .'.validator.'. DEBUG .'.';
	$match .= hash('md5', $_SERVER['CIA_HOME'] .'-'. $_SERVER['CIA_LANG'] .'-'. CIA_PROJECT_PATH .'-'. $_SERVER['REQUEST_URI']) . '.txt';

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

// {{{ Output debug window
DEBUG && CIA_DIRECT && isset($_GET['d$']) && require processPath('debug.php');
// }}}

/// {{{ Anti Cross-Site-(Request-Forgery|Javascript-Request) token
$_POST_BACKUP =& $_POST;

if (
	isset($_COOKIE['T$'])
	&& (!CIA_POSTING || (isset($_POST['T$']) && $_COOKIE['T$'] === $_POST['T$']))
	&& '                                ' == strtr($_COOKIE['T$'], ' 0123456789abcdef', '#                '))
) $cia_token = $_COOKIE['T$'];
else
{
	if (CIA_POSTING) E('Potential Cross Site Request Forgery. $_POST is not reliable. Erasing it !');

	unset($_POST);
	$_POST = array();

	unset($_COOKIE['T$']);
	unset($_COOKIE['T$']); // Double unset against a PHP security hole

	$cia_token = hash('md5', uniqid(mt_rand(), true));

	$k = implode($_SERVER['CIA_LANG'], explode('__', $_SERVER['CIA_HOME'], 2));
	$k = preg_replace("'\?.*$'", '', $k);
	$k = preg_replace("'^https?://[^/]*'i", '', $k);
	$k = dirname($k . ' ');
	if (1 == strlen($k)) $k = '';

	setcookie('T$', $cia_token, 0, $k .'/');
}

define('CIA_TOKEN_MATCH', isset($_GET['T$']) && $cia_token === $_GET['T$']);
// }}}

/* Let's go */
CIA::start();
