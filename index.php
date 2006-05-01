<?php defined('CIA') || define('CIA', microtime(true)) && $CONFIG = array();

$CONFIG += array(
	'maxage' => 3600,
	'lang_list' => 'fr',
	'DSN' => '',

	'allow_debug' => true,

	'translate_driver' => 'default_',
	'translate_params' => array(),

	'session_driver' => 'file',
	'session_params' => array(),
);


/* CONFIG: the next section should be commented after proper configuration */

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
zlib.output_compression = Off

mbstring.language = neutral
mbstring.script_encoding = UTF-8
mbstring.internal_encoding = UTF-8

mbstring.encoding_translation = On
mbstring.detect_order = auto
mbstring.http_input = auto
mbstring.http_output = pass

mbstring.substitute_character = none

; String's functions overloading prevents binary use of a string, so use mb_* functions instead
mbstring.func_overload = 0

*/

/* END:CONFIG */


if (!isset($_SERVER['CIA']))
{
	$_SERVER['CIA_ROOT'] = 'http' . (@$_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '/__/';
	$_SERVER['CIA_LANG'] = $_SERVER['CIA_REQUEST'] = '';

	if (preg_match("'^/([a-z]{2}(?:-[A-Z]{2})?)/?(.*)$'", @$_SERVER['PATH_INFO'], $a))
	{
		$_SERVER['CIA_LANG']    = $a[1];
		$_SERVER['CIA_REQUEST'] = $a[2];
	}
}


/* Config initialisation */

putenv('LC_ALL=en_US.UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');

define('DEBUG',			$CONFIG['allow_debug'] ? (int) @$_COOKIE['DEBUG'] : 0);
define('CIA_MAXAGE',	$CONFIG['maxage']);
define('CIA_TIME', time());
define('CIA_PROJECT_ID', $version_id % 1000);
define('CIA_POSTING', $_SERVER['REQUEST_METHOD']=='POST');
define('CIA_DIRECT', !CIA_POSTING && $_SERVER['CIA_REQUEST'] == '_');

isset($_SERVER['REQUEST_TIME']) || $_SERVER['REQUEST_TIME'] = CIA_TIME;


/* Include Path Initialisation */

$p = dirname(__FILE__);
defined('CIA_PROJECT_PATH') || define('CIA_PROJECT_PATH', $p) && $cia_paths = array() || $version_id = 0;
$version_id += filemtime(__FILE__);
$cia_paths[] = $p;

chdir(CIA_PROJECT_PATH);


/* Global Initialisation */

if (!$_SERVER['CIA_LANG'])
{
	require resolvePath('language.php');
	exit;
}


/* Validator */

if (@$_SERVER['HTTP_IF_NONE_MATCH']{0} == '/' && preg_match("'^/[0-9a-f]{32}-([0-9]+)$'", $_SERVER['HTTP_IF_NONE_MATCH'], $match))
{
	$_SERVER['HTTP_IF_NONE_MATCH'] = $match[1];

	$match = $match[0];
	$match{6} = $match{3} = '/';
	$match = './tmp/cache/validator.' . DEBUG . '/' . $match . '.txt';

	$headers = @file_get_contents($match);
	if ($headers !== false)
	{
		header('HTTP/1.x 304 Not Modified');
		header('Content-Length: 0');
		if ($headers)
		{
			$headers = explode("\n", $headers, 3);

			$match = $headers[0];

			$headers[0] = 'Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', CIA_TIME + $match);
			$headers[1] = 'Cache-Control: max-age=' . $match . ((int) $headers[1] ? ',private,must' : ',public,proxy') . '-revalidate';

			array_map('header', $headers);
		}

		exit;
	}
}


/* Small Usefull Functions */

if (DEBUG) {function E($msg = '__getDeltaMicrotime') {if (class_exists('debug_CIA', false)) return CIA::ciaLog($msg, false, false);}}
else {function E($msg = '__getDeltaMicrotime') {trigger_error(serialize($msg));}}

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

function DB()
{
	static $db = false;

	if (!$db)
	{
		require_once 'DB.php';

		global $CONFIG;

		$db = DB::connect($CONFIG['DSN'], array('persistent' => !DEBUG));

		if(DB::isError( $db ))
		{
			trigger_error($db->message, E_USER_ERROR);
			exit;
		}

		$db->query('SET NAMES utf8');
		$db->query("SET collation_connection='utf8_general_ci'");

		$db->setOption('seqname_format', 'zeq_%s');
		$db->setErrorHandling(PEAR_ERROR_CALLBACK, 'E');
		$db->setFetchMode(DB_FETCHMODE_OBJECT);

		$db->autoCommit(false);
	}

	return $db;
}

function resolvePath($filename)
{
	$paths =& $GLOBALS['cia_paths'];

	$i = 0;
	$len = count($paths);
	do
	{
		$path = $paths[$i++] . DIRECTORY_SEPARATOR;

		switch (DEBUG)
		{
			case 5 : if (file_exists($path . $filename . '.5')) return $path . $filename . '.5';
			case 4 : if (file_exists($path . $filename . '.4')) return $path . $filename . '.4';
			case 3 : if (file_exists($path . $filename . '.3')) return $path . $filename . '.3';
			case 2 : if (file_exists($path . $filename . '.2')) return $path . $filename . '.2';
			case 1 : if (file_exists($path . $filename . '.1')) return $path . $filename . '.1';
			default: if (file_exists($path . $filename       )) return $path . $filename       ;
		}
	}
	while (--$len);

	return $filename;
}

function __autoload($class)
{
	$class = 'class' . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
	$file = resolvePath($class);

	if ($file == $class)
	{
		if ($class = @fopen($file, 'r', true))
		{
			fclose($class);
			include $file;
		}
	}
	else include $file;
}


/* Output debug window */

if (DEBUG && CIA_DIRECT && isset($_GET['d']))
{
	require resolvePath('debug.php');
	exit;
}


/* Start the "kernel" */

CIA::start();


/* Controler */

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

	require resolvePath('controler.php');
}


if (!extension_loaded('mbstring')) require resolvePath('mbstring.php');


if (CIA_DIRECT)
{
	CIA::header('Content-Type: text/javascript; charset=UTF-8');

	if (isset($_GET['$v']) && CIA_PROJECT_ID != $_GET['$v'])
	{
		echo 'w.r()';
		exit;
	}

	switch ( key($_GET) )
	{
		case 't':
			$template = array_shift($_GET);
			$template = str_replace('\\', '/', $template);
			$template = str_replace('../', '/', $template);

			echo 'w(0';

			$ctemplate = CIA::makeCacheDir("templates/$template", 'txt');
			if (file_exists($ctemplate)) readfile($ctemplate);
			else
			{
				CIA::openMeta('agent__template/' . $template, false);
				$compiler = new iaCompiler_js(false);
				echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
				CIA::writeFile($ctemplate, $template);
				list(,,, $watch) = CIA::closeMeta();
				CIA::writeWatchTable($watch, $ctemplate);
			}

			CIA::setMaxage(-1);
			break;

		case 'p':
			$pipe = array_shift($_GET);
			preg_match_all("/[a-zA-Z_][a-zA-Z_\d]*/u", $pipe, $pipe);
			CIA::$agentClass = 'agent__pipe/' . implode('_', $pipe[0]);

			foreach ($pipe[0] as $pipe)
			{
				$cpipe = CIA::makeCacheDir('pipe/' . $pipe, 'js');
				if (file_exists($cpipe)) readfile($cpipe);
				else
				{
					ob_start();
					call_user_func(array('pipe_' . $pipe, 'js'));
					$pipe = ob_get_clean();

					$jsquiz = new jsquiz;
					$jsquiz->addJs($pipe);
					echo $pipe = $jsquiz->get();
					$pipe .= "\n";
					CIA::writeFile($cpipe, $pipe);
					CIA::writeWatchTable(array('pipe'), $cpipe);
				}
			}

			echo 'w(0,[])';

			CIA::setMaxage(-1);
			break;

		case '$':
			IA_js::compose(array_shift($_GET));
			break;
	}
}
else
{
	$agent = CIA::resolveAgentClass($_SERVER['CIA_REQUEST'], $_GET);

	if (isset($_GET['$k']))
	{
		CIA::header('Content-Type: text/javascript; charset=UTF-8');
		CIA::setMaxage(-1);

		function q($a)
		{
			return "'" . str_replace(
				array("\r\n", "\r", '\\',   "\n", "'"),
				array("\n"  , "\n", '\\\\', '\n', "\\'"),
				$a
			) . "'";
		}

		echo 'w.k(',
				CIA_PROJECT_ID, ',',
				q( $_SERVER['CIA_ROOT'] ), ',',
				q( 'agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6)) ), ',',
				q( @$_GET['__0__'] ), ',',
				'[', implode(',', array_map('q', CIA::agentArgv($agent))), ']',
			')';

		exit;
	}

	$a = get_class_vars($agent);
	$binaryMode = (bool) $a['binary'];
	CIA::setBinaryMode($binaryMode);

	/*
	 * Both Firefox and IE send a "Cache-Control: no-cache" request header
	 * only and only if the current page is reloaded with CTRL+F5 or the JS code :
	 * "location.reload(true)". We use this behaviour to trigger a cache reset
	 * when the cache is detected stale by the browser.
	 */
	if (
		( (DEBUG && !$binaryMode) || (isset($_COOKIE['cache_reset_id']) && setcookie('cache_reset_id')) )
		&& !CIA_POSTING
		&& 'no-cache' == @$_SERVER['HTTP_CACHE_CONTROL'] )
	{
		if (DEBUG && !$binaryMode)
		{
			touch('./index.php');
			CIA::delCache();
		}
		else if ($_COOKIE['cache_reset_id'] == CIA_PROJECT_ID)
		{
			touch('./index.php');
			CIA::touch('foreignTrace');
		}

		echo '<script type="text/javascript">/*<![CDATA[*/self.ScriptEngine ? location.replace(location) : location.reload()/*]]>*/</script>';

		exit;
	}

	if (CIA_POSTING || $binaryMode || isset($_GET['$bin']) || !@$_COOKIE['JS'])
	{
		if (!$binaryMode) CIA::setPrivate(true);
		IA_php::loadAgent($agent, false, false);
	}
	else
	{
		if (!isset($_COOKIE['JS'])) setcookie('JS', '0', 2147364847, '/');
		IA_js::loadAgent($agent);
	}
}

exit;
