<?php @define('CIA', microtime(true)); isset($CONFIG) || $CONFIG = array();

$CONFIG += array(
	'debug' => true,
	'maxage' => 1036800,
	'lang_list' => 'fr',
	'secret' => '',
//	'pear_path' => 'C:/Program Files/Wamp/php/PEAR',
	'pear_path' => '/usr/share/php',
	'DSN' => '',

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

;String's functions overloading prevents binary use of a string, so use mb_* functions instead
mbstring.func_overload = 0

*/

/* END:CONFIG */


/* Config initialisation */

# This need more though ...
putenv('LC_ALL=en_US.UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');

define('DEBUG',			$CONFIG['debug']);
define('CIA_MAXAGE',	$CONFIG['maxage']);
define('CIA_LANG_LIST',	$CONFIG['lang_list']);
define('CIA_SECRET',	$CONFIG['secret']);


/* Include Path Initialisation */

$path = dirname(__FILE__);
@define('CIA_PROJECT_PATH', $path);
@$include_path .= $path . PATH_SEPARATOR;
@$version_id += filemtime(__FILE__);

chdir(CIA_PROJECT_PATH);
set_include_path($include_path . $CONFIG['pear_path']);


/* Global Initialisation */

if (!isset($_SERVER['CIA_REQUEST']))
{
	require 'language.php';
	exit;
}

define('CIA_TIME', time());
define('CIA_PROJECT_ID', $version_id % 1000);
define('CIA_LANG', isset($_SERVER['CIA_LANG']) ? $_SERVER['CIA_LANG'] : substr($CONFIG['lang_list'], 0, 2));
define('CIA_ROOT', $_SERVER['CIA_ROOT'] . CIA_LANG . '/');

define('CIA_POSTING', $_SERVER['REQUEST_METHOD']=='POST');
define('CIA_DIRECT', !CIA_POSTING && $_SERVER['CIA_REQUEST'] == '_');

if (DEBUG && CIA_DIRECT && isset($_GET['d']))
{
	require 'debug.php';
	exit;
}


/* Validator */

if (@$_SERVER['HTTP_IF_NONE_MATCH']{0} == '/' && preg_match("'^/[0-9a-f]{32}-([0-9]+)$'", $_SERVER['HTTP_IF_NONE_MATCH'], $match))
{
	$_SERVER['HTTP_IF_NONE_MATCH'] = $match[1];

	$file = $match[0];
	$file{6} = $file{3} = '/';
	$file = './tmp/cache/validator/' . $file . '.txt';

	$cache = @file_get_contents($file);
	if ($cache !== false)
	{
		header('HTTP/1.x 304 Not Modified');
		header('Content-Length: 0');
		header('Content-Type:');
		if ($cache)
		{
			$cache = explode("\n", $cache, 3);
			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', CIA_TIME + $cache[0]));
			header('Cache-Control: max-age=' . $cache[0] . ((int) $cache[1] ? ',private,must' : ',public,proxy') . '-revalidate');
			if (@$cache[2]) header($cache[2]);
		}

		exit;
	}
}


/* Small Usefull Functions */

if (DEBUG) {function E($msg) {CIA::ciaLog($msg, false, false);}}
else {function E($msg) {trigger_error(serialize($msg));}}

function G($name, $type) {$a = func_get_args(); return VALIDATE::get(    $_GET[$name]   , $type, array_slice($a, 2));}
function P($name, $type) {$a = func_get_args(); return VALIDATE::get(    $_POST[$name]  , $type, array_slice($a, 2));}
function C($name, $type) {$a = func_get_args(); return VALIDATE::get(    $_COOKIE[$name], $type, array_slice($a, 2));}
function F($name, $type) {$a = func_get_args(); return VALIDATE::getFile($_FILES[$name] , $type, array_slice($a, 2));}

function V($var , $type) {$a = func_get_args(); return VALIDATE::get(     $var          , $type, array_slice($a, 2));}

function T($string, $usecache = true)
{
//	return TRANSLATE::get($string, $usecache);
	return TRANSLATE::get($string, false);
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

function __autoload($classname)
{
	$file = 'class/' . str_replace('_', '/', $classname) . '.php';

	if ($fp = @fopen($file, 'r', true))
	{
		fclose($fp);
		include $file;
	}
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

	require 'controler.php';
}


if (!extension_loaded('mbstring')) require 'mbstring.php';


if (CIA_DIRECT)
{
	CIA::header('Content-Type: text/javascript; charset=UTF-8');

	switch(key($_GET))
	{
		case 't':
			$template = array_shift($_GET);
			$template = str_replace('\\', '/', $template);
			$template = str_replace('../', '/', $template);
			CIA::$agentClass = 'agent__template/' . $template;

			echo 'w(0';

			$ctemplate = CIA::makeCacheDir("templates/$template", 'txt');
			if (file_exists($ctemplate)) readfile($ctemplate);
			else
			{
				$compiler = new iaCompiler_js(false);
				echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
				CIA::writeFile($ctemplate, $template);
				CIA::writeWatchTable(array('public/templates'), $ctemplate);
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
			class IA extends IA_js {};
			IA::render(array_shift($_GET)); break;
	}
}
else
{
	$agent = '/' . $_SERVER['CIA_REQUEST'];

	if ($agent == '/') $agent = '';
	else
	{
		preg_match("'^((?:/[a-zA-Z\d]+)*)((?:/[^/]+)*)'u", $agent, $agent);

		$param = $agent[2];
		$agent = $agent[1];
		$agentClass = 'agent' . str_replace('/', '_', $agent);

		while ($agent !== '' && !class_exists($agentClass))
		{
			$pos = (int) strrpos($agent, '/');

			$param = substr($agent, $pos) . $param;
			$agent = substr($agent, 0, $pos);
			$agentClass = 'agent' . str_replace('/', '_', $agent);
		}

		$param = explode('/', $_GET['__0__'] = substr($param, 1));
		
		$i = 0;
		foreach ($param as $param) $_GET['__' . ++$i . '__'] = $param;

		$agent = $agent === '' ? '' : substr($agent, 1);
	}

	$a = CIA::agentClass($agent);
	$a = get_class_vars($a);

	$binaryMode = (bool) $a['binary'];
	CIA::setBinaryMode($binaryMode);

	/*
	 * Both Firefox and IE send a "Cache-Control: no-cache" request header
	 * only and only if the current page is reloaded with the JavaScript code :
	 * "location.reload(true)". We use this behaviour to trigger a cache reset
	 * when the cache is detected stale by the browser.
	 */
	if (
		DEBUG && !$binaryMode
		&& 'no-cache' == @$_SERVER['HTTP_CACHE_CONTROL']
		&& 0 === strpos(@$_SERVER['HTTP_USER_AGENT'], 'Mozilla') )
	{
		/* Equivalent to a touch but works with include_path */
		$h = fopen('index.php', 'r+b', true);
		fwrite($h, '<');
		fclose($h);

		CIA::delCache();
		echo "<script>location.reload()</script>";
		exit;
	}

	if (CIA_POSTING || $binaryMode || isset($_GET['$bin']) || !@$_COOKIE['JS'])
	{
		if (!$binaryMode) CIA::setPrivate(true);
		IA_php::loadAgent($agent, false);
	}
	else
	{
		IA_js::loadAgent($agent, false);
	}
}

exit;
