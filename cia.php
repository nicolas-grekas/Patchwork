<?php // vim: set noet ts=4 sw=4 fdm=marker:
define('CIA', microtime(true)); isset($_SERVER['REQUEST_TIME']) || $_SERVER['REQUEST_TIME'] = time();

// {{{ Server configuration helper
/* Comment this section if your server's ocnfig is ok */

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
function resolvePath($filename)
{
	$paths =& $GLOBALS['cia_paths'];

	$i = -1;
	$level = count($paths);

	do
	{
		$path = $paths[++$i] . DIRECTORY_SEPARATOR;
		if (file_exists($path . $filename)) return $path . $filename;
	}
	while (--$level);

	return $filename;
}
// }}}

// {{{ function __autoload()
function __autoload($searched_class)
{
	if (preg_match("'^(.+)__([0-9]+)$'", $searched_class, $class_level)) // Namespace renammed class
	{
		$class = $class_level[1];
		$class_level = (int) $class_level[2];
	}
	else
	{
		$class = $searched_class;
		$class_level = -1;
	}

	$level = $class_level>=0 ? $class_level : count($GLOBALS['cia_paths']);

	if ('_' == substr($class, -1) || '_' == substr($class, 0, 1) || false !== strpos($class, '__')) // Out of the path class: search for an existing parent
	{
		do
		{
			$parent = $class . '__' . --$level;

			if (class_exists($parent, false))
			{
				$searched_class .= ' extends ' . $parent;
				$abstract = new ReflectionClass($parent);
				$abstract = $abstract->isAbstract();
				break;
			}
		}
		while ($level);
	}
	else // Conventional class: search its definition on disk
	{
		$file = 'class' . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.';

		$i = $class_level>=0 ? count($GLOBALS['cia_paths']) - $class_level - 1 : 0;

		$paths =& $GLOBALS['cia_paths'];

		do
		{
			$parent = $class . '__' . --$level;

			$path = $paths[$i++] . DIRECTORY_SEPARATOR;

			if (class_exists($parent, false))
			{
				$searched_class .= ' extends ' . $parent;
				$abstract = new ReflectionClass($parent);
				$abstract = $abstract->isAbstract();
				break;
			}

			if (file_exists($path . $file . 'php'))
			{
				$searched_class .= ' extends ' . $parent;

				$file = $path . $file;
				$abstract = false;
				$final = false;

				     if (file_exists($file_rewritten = $file . 'c.r.php')) ;
				else if (file_exists($file_rewritten = $file . 'a.r.php')) $abstract = true;
				else if (file_exists($file_rewritten = $file . 'f.r.php')) $final = true;
				else $file_rewritten = false;

				if (!$file_rewritten || (DEBUG && filemtime($file . 'php') > filemtime($file_rewritten))) require resolvePath('classRewriter.php');

				require $file_rewritten;

				if ($level+1 == $class_level || $final) return;

				break;
			}
		}
		while ($level);
	}

	eval(($abstract ? 'abstract ' : '') . 'class ' . $searched_class . '{}');
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
					parse_str( preg_replace("'^.*?(\?|%3F)'i", '', $_SERVER['QUERY_STRING']), $_GET);
				}
				else unset($_GET[ key($_GET) ]);
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
	define('DEBUG',			$CONFIG['DEBUG']);
	define('CIA_MAXAGE',	$CONFIG['maxage']);
	define('CIA_PROJECT_ID', abs($version_id % 10000));
	define('CIA_POSTING', 'POST' == $_SERVER['REQUEST_METHOD']);
	define('CIA_DIRECT', !CIA_POSTING && '_' == $_SERVER['CIA_REQUEST']);

	function E($msg = '__getDeltaMicrotime')
	{
		if (class_exists('debug_CIA', false)) return CIA::ciaLog($msg, false, false);

		trigger_error(serialize($msg));
	}

	if (function_exists('date_default_timezone_set') && isset($CONFIG['timezone'])) date_default_timezone_set($CONFIG['timezone']);
	// }}}

	// {{{ Language controler
	if (!$_SERVER['CIA_LANG'])
	{
		require resolvePath('language.php');
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
		require resolvePath('debug.php');
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

	/* Start the "kernel" */
	CIA::start();

	// {{{ Static controler
	$agent = $_SERVER['CIA_REQUEST'];
	$path = strtolower(strrchr($agent, '.'));
	switch ($path)
	{
		case '.html':
		case '.htm':
		case '.css':
		case '.js':

		case '.png':
		case '.gif':
		case '.jpg':
		case '.jpeg':

		require resolvePath('controler.php');
	}
	// }}}

	if (!extension_loaded('mbstring')) require resolvePath('mbstring.php');

	if (CIA_DIRECT)
	{
		// {{{ Client side rendering controler
		CIA::header('Content-Type: text/javascript; charset=UTF-8');

		if (isset($_GET['v$']) && CIA_PROJECT_ID != $_GET['v$'] && 'x$' != key($_GET))
		{
			echo 'w.r()';
			exit;
		}

		switch ( key($_GET) )
		{
			case 't$':
				$template = array_shift($_GET);
				$template = str_replace('\\', '/', $template);
				$template = str_replace('../', '/', $template);

				echo 'w(0';

				$ctemplate = CIA::getContextualCachePath("templates/$template", 'txt');
				if ($h = CIA::fopenX($ctemplate))
				{
					CIA::openMeta('agent__template/' . $template, false);
					$compiler = new iaCompiler_js(false);
					echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
					fwrite($h, $template, strlen($template));
					fclose($h);
					list(,,, $watch) = CIA::closeMeta();
					CIA::writeWatchTable($watch, $ctemplate);
				}
				else readfile($ctemplate);

				CIA::setMaxage(-1);
				break;

			case 'p$':
				$pipe = array_shift($_GET);
				preg_match_all("/[a-zA-Z_][a-zA-Z_\d]*/u", $pipe, $pipe);
				CIA::$agentClass = 'agent__pipe/' . implode('_', $pipe[0]);

				foreach ($pipe[0] as &$pipe)
				{
					$cpipe = CIA::getContextualCachePath('pipe/' . $pipe, 'js');
					if ($h = CIA::fopenX($cpipe))
					{
						ob_start();
						call_user_func(array('pipe_' . $pipe, 'js'));
						$pipe = ob_get_clean();

						$jsquiz = new jsquiz;
						$jsquiz->addJs($pipe);
						echo $pipe = $jsquiz->get();
						$pipe .= "\n";
						fwrite($h, $pipe, strlen($pipe));
						fclose($h);
						CIA::writeWatchTable(array('pipe'), $cpipe);
					}
					else readfile($cpipe);
				}

				echo 'w(0,[])';

				CIA::setMaxage(-1);
				break;

			case 'a$':
				IA_js::render(array_shift($_GET), false);
				break;

			case 'x$':
				IA_js::render(array_shift($_GET), true);
				break;
		}
		// }}}
	}
	else
	{
		// {{{ Server side rendering controler
		$agent = CIA::resolveAgentClass($_SERVER['CIA_REQUEST'], $_GET);

		if (isset($_GET['k$']))
		{
			CIA::header('Content-Type: text/javascript; charset=UTF-8');
			CIA::setMaxage(-1);

			echo 'w.k(',
					CIA_PROJECT_ID, ',',
					jsquote( $_SERVER['CIA_HOME'] ), ',',
					jsquote( 'agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6)) ), ',',
					jsquote( @$_GET['__0__'] ), ',',
					'[', implode(',', array_map('jsquote', CIA::agentArgv($agent))), ']',
				')';

			exit;
		}

		$binaryMode = (bool) constant("$agent::binary");

		/*
		 * Both Firefox and IE send a "Cache-Control: no-cache" request header
		 * only and only if the current page is reloaded with CTRL+F5 or the JS code :
		 * "location.reload(true)". We use this behaviour to trigger a cache reset in DEBUG mode.
		 */

		if (($a = !CIA_POSTING && DEBUG && !$binaryMode && 'no-cache' == @$_SERVER['HTTP_CACHE_CONTROL'])
			|| (isset($_COOKIE['cache_reset_id']) && setcookie('cache_reset_id', '', 0, '/')))
		{
			if ($a)
			{
				CIA::touch('');
				CIA::delDir(CIA::$cachePath, false);
				touch('config.php');
			}
			else if ($_COOKIE['cache_reset_id'] == CIA_PROJECT_ID)
			{
				CIA::touch('CIApID');
				CIA::touch('foreignTrace');
				touch('config.php');
			}

			CIA::setMaxage(0);
			CIA::setGroup('private');

			echo '<html><head><script type="text/javascript">location.reload()</script></head></html>';
			exit;
		}

		if (CIA_POSTING || $binaryMode || isset($_GET['$bin']) || !@$_COOKIE['JS'])
		{
			if (!$binaryMode) CIA::setGroup('private');
			IA_php::loadAgent($agent, false, false);
		}
		else IA_js::loadAgent($agent);
		// }}}
	}

	exit;
}
// }}}
