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
ini_set('zlib.output_compression', false);

// Encoding context initialization
@putenv('LC_ALL=en_US.UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');

extension_loaded('mbstring') && mb_internal_encoding('UTF-8');

if (function_exists('iconv'))
{
	iconv_set_encoding('input_encoding',    'UTF-8');
	iconv_set_encoding('internal_encoding', 'UTF-8');
	iconv_set_encoding('output_encoding',   'UTF-8');
}

if (!preg_match("''u", urldecode($a = $_SERVER['REQUEST_URI'])))
{
	$a = $a != utf8_decode($a) ? '/' : preg_replace("'(?:%[89a-fA-F][0-9a-fA-F])+'e", "urlencode(utf8_encode(urldecode('$0')))", $a);

	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $a);
	exit;
}
// }}}

// {{{ Fix php.ini settings if needed
set_magic_quotes_runtime(false);

if (get_magic_quotes_gpc())
{
	if (ini_get('magic_quotes_sybase')) { function _q_(&$a) {static $d=999; --$d&&is_array($a) ? array_walk($a, '_q_') : $a = str_replace("''", "'", $a); ++$d;} }
	else { function _q_(&$a) {static $d=999; --$d&&is_array($a) ? array_walk($a, '_q_') : $a = stripslashes($a); ++$d;} }
	_q_($_GET); _q_($_POST); _q_($_COOKIE);
}

if (!(extension_loaded('mbstring') && ini_get('mbstring.encoding_translation') & 'UTF-8' == ini_get('mbstring.http_input')))
{
	function _u_(&$a)
	{
		# See http://www.w3.org/International/questions/qa-forms-utf-8

		static $d=999, $rx = '/(?:[\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})+/';

		if (--$d && is_array($a)) array_walk($a, '_u_');
		else if (!preg_match("''u", $a))
		{
			preg_match_all($rx, $a, $a, PREG_PATTERN_ORDER);
			$a = implode('', $a[0]);
		}

		++$d;
	}
	// $_GET is already fixed at encoding context initialization
	_u_($_POST); _u_($_COOKIE); _u_($_FILES);
}
// }}}

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

// {{{ cia_atomic_write
function cia_atomic_write(&$data, $to)
{
	$tmp = uniqid(mt_rand(), true);
	file_put_contents($tmp, $data);
	unset($data);

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

if (!isset($CONFIG['DEBUG'])) $CONFIG['DEBUG'] = (int) @$CONFIG['DEBUG_KEYS'][ (string) $_COOKIE['DEBUG'] ];
if (isset($CONFIG['clientside']) && !$CONFIG['clientside']) $_GET['$bin'] = true;

// Restore the current dir in shutdown context.
function cia_restoreProjectPath() {CIA_PROJECT_PATH != getcwd() && chdir(CIA_PROJECT_PATH);}
register_shutdown_function('cia_restoreProjectPath', CIA_PROJECT_PATH);
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

function E($msg = '__getDeltaMicrotime')
{
	return class_exists('CIA', false) ? CIA::log($msg, false, false) : trigger_error($msg);
}

function W($msg)
{
	trigger_error($msg, E_USER_WARNING);
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
$cia_autoload_pool = false;

function __autoload($searched_class)
{
	global $cia_paths_token, $cia_autoload_cache;

	if (isset($cia_autoload_cache[$searched_class]))
	{
		include "./.class_{$cia_autoload_cache[$searched_class]}.{$cia_paths_token}.zcache.php";
		if (class_exists($searched_class, 0)) return;
	}

	$last_cia_paths = count($GLOBALS['cia_paths']) - 1;

	if (false !== strpos($searched_class, ';') || false !== strpos($searched_class, "'")) return;

	$amark = $GLOBALS['a' . $cia_paths_token];
	$GLOBALS['a' . $cia_paths_token] = false;
	$bmark = $GLOBALS['b' . $cia_paths_token];

	$i = strrpos($searched_class, '__');
	$level = false !== $i ? substr($searched_class, $i+2) : false;

	if (false !== $level && '' !== $level && '' === ltrim(strtr($level, ' 0123456789', '#          ')))
	{
		// Namespace renammed class
		$class = substr($searched_class, 0, $i);
		$level = min($last_cia_paths, '00' === $level ? -1 : (int) $level);
	}
	else
	{
		$class = $searched_class;
		$level = $last_cia_paths;
	}

	$parent_class = $class . '__' . $level;
	$cache = false;
	$c = $searched_class == $class;

	if ('_' == substr($class, -1) || !strncmp('_', $class, 1) || false !== strpos($class, '__'))
	{
		// Out of the path class: search for an existing parent

		if ($class == $searched_class) ++$level;

		do $parent_class = $class . '__' . (0<=--$level ? $level : '00');
		while ($level>=0 && !class_exists($parent_class, false));
	}
	else if (!$c || !class_exists($parent_class, false))
	{
		// Conventional class: search its parent in existing classes or on disk

		$file = false;
		$lcClass = strtolower($class);

		$i = $last_cia_paths - $level;
		if (0 > $i) $i = 0;

		if ($GLOBALS['cia_autoload_prefix'])
		{
			foreach ($GLOBALS['cia_autoload_prefix'] as $v)
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
				$preproc = 'CIA_preprocessor';
				if ('cia_preprocessor' == $lcClass)
				{
					if ($level) $preproc .= '__0';
					else
					{
						require $source;
						break;
					}
				}

				$cache = ((int)(bool)DEBUG) . (0>$level ? -$level .'-' : $level);
				$cache = "./.class_{$class}.php.{$cache}.{$cia_paths_token}.zcache.php";

				file_exists($cache) || call_user_func(array($preproc, 'run'), $source, $cache, $level, $class);

				$current_pool = array();
				$parent_pool =& $GLOBALS['cia_autoload_pool'];
				$GLOBALS['cia_autoload_pool'] =& $current_pool;

				require $cache;

				if (class_exists($searched_class, false)) $parent_class = false;
				if (false !== $parent_pool) $parent_pool[$parent_class ? $parent_class : $searched_class] = $cache;

				break;
			}

			--$level;

			$parent_class = $class . '__' . (0<=$level ? $level : '00');

			if (class_exists($parent_class, false)) break;
		}
	}

	if ($parent_class && class_exists($parent_class, true))
	{
		$class = new ReflectionClass($parent_class);
		$class = ($class->isAbstract() ? 'abstract ' : '') . 'class ' . $searched_class . ' extends ' . $parent_class . '{}';

		if ($c)
		{
			method_exists($parent_class, '__static_construct') && $class .= "{$parent_class}::__static_construct();";
			method_exists($parent_class, '__static_destruct' ) && $class .= "register_shutdown_function(array('{$parent_class}','__static_destruct'));";
		}

		eval($class);
	}
	else $class = '';

	if ($class && isset($cia_autoload_cache[$parent_class]))
	{
		// Include class declaration in its closest parent

		$code = $cia_autoload_cache[$parent_class];
		$tmp = strrpos($code, '-');
		$level = substr($code, 0, $tmp);
		$code = substr($code, $tmp);
		$code = "\$GLOBALS['cia_autoload_cache']['{$parent_class}']=__FILE__.'{$code}';";

		$tmp = file_get_contents($level);
		if (false !== strpos($tmp, $code))
		{
			if (!$c)
			{
				$c = substr($code, 0, strrpos($code, '-') + 1) . mt_rand() . "';";
				$class .= ';' . $c;
			}

			$tmp = str_replace($code, $class, $tmp);
			($cache == $level && $current_pool) || cia_atomic_write($tmp, $level);
		}
	}
	else $tmp = false;

	if ($cache)
	{
		$GLOBALS['cia_autoload_pool'] =& $parent_pool;

		if ($current_pool)
		{
			// Add an include directive of parent's code in the derivated class

			$code = '<?php ?>';
			$tmp || $tmp = file_get_contents($cache);
			if ('<?php ' != substr($tmp, 0, 6)) $tmp = '<?php ?>' . $tmp;

			foreach ($current_pool as $class => &$c) $code = substr($code, 0, -2) . "class_exists('{$class}',0)||include '{$c}';?>";

			$tmp = substr($code, 0, -2) . substr($tmp, 6);
			cia_atomic_write($tmp, $cache);
		}

		$cache = substr($cache, 9, -12-strlen($cia_paths_token));

		if ($amark)
		{
			// Marker substitution

			$code = $amark;
			$amark = $amark != $bmark;

			$tmp = strrpos($code, '-');
			$level = substr($code, 0, $tmp);
			$code = substr($code, $tmp);
			$code = "\$a{$cia_paths_token}=__FILE__.'{$code}'";

			$tmp = file_get_contents($level);
			if (false !== strpos($tmp, $code))
			{
				if ($amark) $c = "class_exists('{$searched_class}',0)||(include './.class_{$cache}.{$cia_paths_token}.zcache.php')||1";
				else
				{
					$code = "\$b{$cia_paths_token}=" . $code;
					$c = substr($code, 0, strrpos($code, '-') + 1);
					$c = "(\$cia_autoload_cache['{$searched_class}']='{$cache}')&&" . $c . mt_rand() . "'";
				}

				$tmp = str_replace($code, $c, $tmp);
				cia_atomic_write($tmp, $level);
			}
		}
		else if (!$bmark)
		{
			// Global cache completion

			$code = "\$cia_autoload_cache['{$searched_class}']='{$cache}';";

			$c = fopen('./.config.zcache.php', 'ab');
			flock($c, LOCK_EX);
			fwrite($c, $code, strlen($code));
			fclose($c);
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

// {{{ Debug mode add-on
DEBUG && require processPath('debug.php');
// }}}

/// {{{ Anti Cross-Site-(Request-Forgery|Javascript-Request) token
$_POST_BACKUP =& $_POST;

if (
	isset($_COOKIE['T$'])
	&& (!CIA_POSTING || (isset($_POST['T$']) && $_COOKIE['T$'] === $_POST['T$']))
	&& '--------------------------------' == strtr($_COOKIE['T$'], ' 0123456789abcdef', '-----------------')
) $cia_token = $_COOKIE['T$'];
else
{
	if ($_COOKIE)
	{
		if (CIA_POSTING) W('Potential Cross Site Request Forgery. $_POST is not reliable. Erasing it !');

		unset($_POST);
		$_POST = array();

		unset($_COOKIE['T$']);
		unset($_COOKIE['T$']); // Double unset against a PHP security hole
	}

	$cia_token = md5(uniqid(mt_rand(), true));

	$a = implode($_SERVER['CIA_LANG'], explode('__', $_SERVER['CIA_HOME'], 2));
	$a = preg_replace("'\?.*$'", '', $a);
	$a = preg_replace("'^https?://[^/]*'i", '', $a);
	$a = dirname($a . ' ');
	if (1 == strlen($a)) $a = '';

	setcookie('T$', $cia_token, 0, $a .'/');
}

define('CIA_TOKEN_MATCH', isset($_GET['T$']) && $cia_token === $_GET['T$']);
// }}}

/* Let's go */
CIA::start();
