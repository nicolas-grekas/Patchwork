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

#>>>
if (isset($_SERVER['PHP_AUTH_USER']))
{
	$_SERVER['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_PW'] = "Don't use me, it would be a security hole (cross site javascript).";
}
#<<<

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

// {{{ hunter : a user function is triggered when a hunter object is destroyed
class hunter
{
	protected $function;
	protected $param_arr;

	function __construct($function, $param_arr)
	{
		$this->function =& $function;
		$this->param_arr =& $param_arr;
	}

	function __destruct()
	{
		call_user_func_array($this->function, $this->param_arr);
	}
}
// }}}

// {{{ PHP session mechanism overloading
class sessionHandler implements ArrayAccess
{
	function offsetGet($k)     {$_SESSION = SESSION::getAll(); return $_SESSION[$k];}
	function offsetSet($k, $v) {$_SESSION = SESSION::getAll(); $_SESSION[$k] =& $v;}
	function offsetExists($k)  {$_SESSION = SESSION::getAll(); return isset($_SESSION[$k]);}
	function offsetUnset($k)   {$_SESSION = SESSION::getAll(); unset($_SESSION[$k]);}

	static $id;

	static function close()   {return true;}
	static function gc($life) {return true;}

	static function open($path, $name)
	{
		session_cache_limiter('');
		ini_set('session.use_cookies', false);
		ini_set('session.use_trans_sid', false);
		return true;
	}

	static function read($id)
	{
		$_SESSION = SESSION::getAll();
		self::$id = $id;
		return '';
	}

	static function write($id, $data)
	{
		if (self::$id != $id) SESSION::regenerateId();
		return true;
	}

	static function destroy($id)
	{
		SESSION::regenerateId(true);
		return true;
	}
}

session_set_save_handler(
	array($k = 'sessionHandler', 'open'),
	array($k, 'close'),
	array($k, 'read'),
	array($k, 'write'),
	array($k, 'destroy'),
	array($k, 'gc')
);

$_SESSION = new sessionHandler;

// }}}

// {{{ Default database support with MDB2
function DB($close = false)
{
	static $hunter;
	static $db = false;

	if ($db || $close)
	{
		if ($close && $db)
		{
			$db->commit();
			$db = false;
		}

		return $db;
	}

	$hunter = new hunter('DB', array(true));

	require_once 'MDB2.php';

	$db = @MDB2::factory($GLOBALS['CONFIG']['DSN']);
	$db->loadModule('Extended');
	$db->setErrorHandling(PEAR_ERROR_CALLBACK, 'E');
	$db->setFetchMode(MDB2_FETCHMODE_OBJECT);
	$db->setOption('default_table_type', 'InnoDB');
	$db->setOption('seqname_format', 'zeq_%s');
	$db->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL ^ MDB2_PORTABILITY_FIX_CASE);

	$db->connect();

	if (@PEAR::isError($db))
	{
		trigger_error($db->getMessage(), E_USER_ERROR);
		CIA::disable(true);
	}

	$db->beginTransaction();

	$db->query('SET NAMES utf8');
	$db->query("SET collation_connection='utf8_general_ci'");

	return $db;
}
// }}}

function jsquote($a, $addDelim = true, $delim = "'")
{
	if ((string) $a === (string) ($a-0)) return $a-0;

	false !== strpos($a, "\r") && ($a = str_replace(array("\r\n", "\r"), array("\n", "\n"), $a));
	false !== strpos($a, '\\') && ($a = str_replace('\\', '\\\\', $a));
	false !== strpos($a, "\n") && ($a = str_replace("\n", '\n', $a));
	false !== strpos($a, '</') && ($a = str_replace('</', '<\\/', $a));
	false !== strpos($a, $delim) && ($a = str_replace($delim, '\\' . $delim, $a));

	if ($addDelim) $a = $delim . $a . $delim;

	return $a;
}

function jsquoteRef(&$a) {$a = jsquote($a);}

class
{
	static $cachePath = 'zcache/';
	static $agentClass;
	static $catchMeta = false;
	static $ETag = '';

	protected static $host;
	protected static $lang = '__';
	protected static $home;
	protected static $uri;

	protected static $versionId;
	protected static $fullVersionId;
	protected static $has_error = false;
	protected static $handlesOb;
	protected static $metaInfo;
	protected static $metaPool = array();
	protected static $isGroupStage = true;
	protected static $isServersideHtml;

	protected static $maxage = false;
	protected static $private = false;
	protected static $expires = 'auto';
	protected static $watchTable = array();
	protected static $headers;

	protected static $redirecting = false;
	protected static $is_enabled = false;
	protected static $ob_starting_level;
	protected static $ob_level;
	protected static $varyEncoding = false;
	protected static $contentEncoding = false;

	protected static $agentClasses = '';
	protected static $privateDetectionMode = false;
	protected static $detectXSJ = false;
	protected static $total_time = 0;

	protected static $noGzip = array(
		'application/pdf',
		'image/png',
		'image/gif',
		'image/jpeg',
	);

	static function start()
	{
		// Stupid Zend Engine with PHP 5.0.x ...
		// Static vars assigned in the class declaration are not accessible from an instance of a derived class.
		// The workaround is to assign them at run time...
		self::$fullVersionId = $GLOBALS['version_id'];
		self::$versionId = abs(self::$fullVersionId % 10000);
		self::$handlesOb = false;
		self::$headers = array();
		self::$isServersideHtml = false;

		$cachePath = resolvePath(self::$cachePath);
		self::$cachePath = ($cachePath == self::$cachePath ? $GLOBALS['cia_paths'][count($GLOBALS['cia_paths']) - 2] . '/' : '') . $cachePath;

		self::header('Content-Type: text/html; charset=UTF-8');
		set_error_handler(array(__CLASS__, 'error_handler'));

		self::$is_enabled = true;
		self::$ob_starting_level = ob_get_level();
		ob_start(array(__CLASS__, 'ob_sendHeaders'));
		ob_start(array(__CLASS__, 'ob_filterOutput'), 8192);
		self::$ob_level = 2;


		self::setLang($_SERVER['CIA_LANG'] ? $_SERVER['CIA_LANG'] : substr($GLOBALS['CONFIG']['lang_list'], 0, 2));

		if (htmlspecialchars(self::$home) != self::$home)
		{
			E('Fatal error: illegal character found in self::$home');
			self::disable(true);
		}

		if (isset($_GET['T$'])) self::$private = true;

		$agent = $_SERVER['CIA_REQUEST'];
		if (preg_match("'\.[a-z0-9]{1,4}$'i", $agent, $mime) && strcasecmp('.tpl', $mime[0])) require processPath('controler.php');

		extension_loaded('mbstring') || require processPath('mbstring.php');

#>>>
		self::log(
			'<a href="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '" target="_blank">'
			. htmlspecialchars(preg_replace("'&v\\\$=[^&]*'", '', $_SERVER['REQUEST_URI']))
			. '</a>'
		);
		register_shutdown_function(array('CIA', 'log'), '', true);
#<<<

		CIA_DIRECT ? self::clientside() : self::serverside();

		while (self::$ob_level)
		{
			ob_end_flush();
			--self::$ob_level;
		}
	}

	static function clientside()
	{
		// {{{ Client side rendering controler
		self::header('Content-Type: text/javascript; charset=UTF-8');

		if (isset($_GET['v$']) && self::$versionId != $_GET['v$'] && 'x$' != key($_GET))
		{
			echo 'w.r()';
			return;
		}

		switch ( key($_GET) )
		{
			case 't$':
				$template = array_shift($_GET);
				$template = str_replace('\\', '/', $template);
				$template = str_replace('../', '/', $template);

				echo 'w(0';

				$ctemplate = self::getContextualCachePath("templates/$template", 'txt');
				$readHandle = true;
				if ($h = self::fopenX($ctemplate, $readHandle))
				{
					self::openMeta('agent__template/' . $template, false);
					$compiler = new iaCompiler_js(false);
					echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
					fwrite($h, $template, strlen($template));
					fclose($h);
					list(,,, $watch) = self::closeMeta();
					self::writeWatchTable($watch, $ctemplate);
				}
				else
				{
					fpassthru($readHandle);
					fclose($readHandle);
				}

				self::setMaxage(-1);
				break;

			case 'p$':
				$pipe = array_shift($_GET);
				preg_match_all("/[a-zA-Z_][a-zA-Z_\d]*/u", $pipe, $pipe);
				self::$agentClass = 'agent__pipe/' . implode('_', $pipe[0]);

				foreach ($pipe[0] as &$pipe)
				{
					$cpipe = self::getContextualCachePath('pipe/' . $pipe, 'js');
					$readHandle = true;
					if ($h = self::fopenX($cpipe, $readHandle))
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
						self::writeWatchTable(array('pipe'), $cpipe);
					}
					else
					{
						fpassthru($readHandle);
						fclose($readHandle);
					}
				}

				echo 'w(0,[])';

				self::setMaxage(-1);
				break;

			case 'a$':
				CIA_clientside::render(array_shift($_GET), false);
				break;

			case 'x$':
				CIA_clientside::render(array_shift($_GET), true);
				break;
		}
	}
	// }}}

	// {{{ Server side rendering controler
	static function serverside()
	{
		$agent = self::resolveAgentClass($_SERVER['CIA_REQUEST'], $_GET);

		if (isset($_GET['k$']))
		{
			self::header('Content-Type: text/javascript; charset=UTF-8');
			self::setMaxage(-1);

			echo 'w.k(',
				self::$versionId, ',',
				jsquote( $_SERVER['CIA_HOME'] ), ',',
				jsquote( 'agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6)) ), ',',
				jsquote( isset($_GET['__0__']) ? $_GET['__0__'] : '' ), ',',
				'[', implode(',', array_map('jsquote', self::agentArgv($agent))), ']',
			')';

			return;
		}

		$binaryMode = (bool) constant("$agent::binary");

		// Synch exoagents on browser request
		if (isset($_COOKIE['cache_reset_id']) && self::$versionId == $_COOKIE['cache_reset_id'] && setcookie('cache_reset_id', '', 0, '/'))
		{
			self::touch('CIApID');
			self::touch('foreignTrace');
			touch('./config.php');

			self::setMaxage(0);
			self::setGroup('private');

			echo '<html><head><script type="text/javascript">location.reload()</script></head></html>';
			return;
		}

#>>>
		/*
		 * Both Firefox and IE send a "Cache-Control: no-cache" request header
		 * only and only if the current page is reloaded with CTRL+F5 or the JS code :
		 * "location.reload(true)". We use this behaviour to trigger a cache reset in DEBUG mode.
		 */

		if (CIA_CHECK_SOURCE && !CIA_POSTING && !$binaryMode)
		{
			self::touch('');
			foreach (glob(self::$cachePath . '?/?/*', GLOB_NOSORT) as $v) if ('.session' != substr($v, -8)) unlink($v);

			self::$fullVersionId -= filemtime('./config.php');

			touch('./config.php', $_SERVER['REQUEST_TIME']);

			self::$fullVersionId += $_SERVER['REQUEST_TIME'];
			self::$versionId = abs(self::$fullVersionId % 10000);
		}
#<<<

		// load agent
		if (CIA_POSTING || $binaryMode || isset($_GET['$bin']) || !isset($_COOKIE['JS']) || !$_COOKIE['JS'])
		{
			if (!$binaryMode) self::setGroup('private');
			CIA_serverside::loadAgent($agent, false, false);
		}
		else CIA_clientside::loadAgent($agent);
	}
	// }}}

	static function disable($exit = false)
	{
		if (self::$is_enabled && ob_get_level() == self::$ob_starting_level + self::$ob_level)
		{
			while (self::$ob_level-- > 2) ob_end_clean();

			ob_end_clean();
			self::$is_enabled = false;
			ob_end_clean();

			if (!$exit) return true;
		}

		if ($exit) exit;

		return false;
	}

	static function setLang($new_lang)
	{
		$lang = self::$lang;
		self::$lang = $new_lang;

		self::$home = explode('__', $_SERVER['CIA_HOME'], 2);
		self::$home = implode($new_lang, self::$home);

		self::$host = strtr(self::$home, '#?', '//');
		self::$host = substr(self::$home, 0, strpos(self::$host, '/', 8)+1);

		return $lang;
	}

	static function __HOST__() {return self::$host;}
	static function __LANG__() {return self::$lang;}
	static function __HOME__() {return self::$home;}
	static function __URI__() {return self::$uri;}

	static function home($url, $noId = false)
	{
		if (!preg_match("'^https?://'", $url))
		{
			if (strncmp('/', $url, 1)) $url = self::$home . $url;
			else $url = self::$host . substr($url, 1);

			if (!$noId) $url .= (false === strpos($url, '?') ? '?' : '&amp;') . self::$versionId;
		}

		return $url;
	}

	/**
	 * Replacement for PHP's header() function
	 */
	static function header($string)
	{
		if (self::$is_enabled && (
			   0===stripos($string, 'http/')
			|| 0===stripos($string, 'etag')
			|| 0===stripos($string, 'last-modified')
			|| 0===stripos($string, 'expires')
			|| 0===stripos($string, 'cache-control')
			|| 0===stripos($string, 'content-length')
		)) return;

		$string = preg_replace("'[\r\n].*'s", '', $string);

		$name = strtolower(substr($string, 0, strpos($string, ':')));

		if (self::$catchMeta) self::$metaInfo[4][$name] = $string;

		if ('content-type' == $name) self::$isServersideHtml = false !== stripos($string, 'html');

		if (!self::$privateDetectionMode)
		{
			if ('content-type' == $name && false !== stripos($string, 'script'))
			{
				if (self::$private) self::preventXSJ();

				self::$detectXSJ = true;
			}

			self::$headers[$name] = $string;
			header($string);
		}
	}

	static function readfile($file, $mime = 'application/octet-stream')
	{
		self::header('Content-Type: ' . $mime);

		self::$isServersideHtml = false;

		self::$ETag = filesize($file) .'-'. filemtime($file) .'-'. fileinode($file);
		self::disable();

		ignore_user_abort(false);

		if (in_array(substr(self::$headers['content-type'], 14), self::$noGzip))
		{
			header('Content-Length: ' . filesize($file));
		}
		else
		{
			ob_start(array(__CLASS__, 'ob_filterOutput'), 8192);
		}

		readfile($file);
	}

	/**
	 * Redirect the web browser to an other GET request
	 */
	static function redirect($url = '')
	{
		if (self::$privateDetectionMode) throw new Exception;

		$url = (string) $url;
		$url = '' === $url ? '' : (preg_match("'^([^:/]+:/|\.+)?/'i", $url) ? $url : (self::$home . ('index' == $url ? '' : $url)));

		self::$redirecting = true;
		self::disable();

		if (CIA_DIRECT)
		{
			$url = 'location.replace(' . ('' !== $url
				? "'" . addslashes($url) . "'"
				: 'location')
			. ')';

			if (true === self::$contentEncoding) header('Content-Encoding: identity');
			header('Content-Length: ' . strlen($url));

			echo $url;
		}
		else
		{
			header('HTTP/1.1 302 Found');
			header('Location: ' . ('' !== $url ? $url : $_SERVER['REQUEST_URI']));
		}

		exit;
	}

	protected static function openMeta($agentClass, $is_trace = true)
	{
		self::$isGroupStage = true;

		self::$agentClass = $agentClass;
		if ($is_trace) self::$agentClasses .= '*' . self::$agentClass;

		$default = array(false, array(), false, array(), array(), false, self::$agentClass);

		self::$catchMeta = true;

		self::$metaPool[] =& $default;
		self::$metaInfo =& $default;
	}

	protected static function closeGroupStage()
	{
		self::$isGroupStage = false;

		return self::$metaInfo[1];
	}

	protected static function closeMeta()
	{
		self::$catchMeta = false;

		$poped = array_pop(self::$metaPool);

		$len = count(self::$metaPool);

		if ($len)
		{
			self::$metaInfo =& self::$metaPool[$len-1];
			self::$agentClass = self::$metaInfo[6];
		}
		else self::$agentClass = self::$metaInfo = null;

		return $poped;
	}

	/**
	 * Controls the Cache's max age.
	 */
	static function setMaxage($maxage)
	{
		if ($maxage < 0) $maxage = CIA_MAXAGE;
		else $maxage = min(CIA_MAXAGE, $maxage);

		if (!self::$privateDetectionMode)
		{
			if (false === self::$maxage) self::$maxage = $maxage;
			else self::$maxage = min(self::$maxage, $maxage);
		}

		if (self::$catchMeta)
		{
			if (false === self::$metaInfo[0]) self::$metaInfo[0] = $maxage;
			else self::$metaInfo[0] = min(self::$metaInfo[0], $maxage);
		}
	}

	/**
	 * Controls the Cache's groups.
	 */
	static function setGroup($group)
	{
		if ('public' == $group) return;

		$group = array_diff((array) $group, array('public'));

		if (!$group) return;

		if (self::$privateDetectionMode) throw new PrivateDetection;
		else if (self::$detectXSJ) self::preventXSJ();

		self::$private = true;

		if (self::$catchMeta)
		{
			$a =& self::$metaInfo[1];

			if (1 == count($a) && 'private' == $a[0]) return;

			if (in_array('private', $group)) $a = array('private');
			else
			{
				$b = $a;

				$a = array_unique( array_merge($a, $group) );
				sort($a);

				if ($b != $a && !self::$isGroupStage)
				{
#>					E('Miss-conception: self::setGroup() is called in ' . self::$agentClass . '->compose( ) rather than in ' . self::$agentClass . '->control(). Cache is now disabled for this agent.');

					$a = array('private');
				}
			}
		}
	}

	/**
	 * Controls the Cache's expiration mechanism.
	 */
	static function setExpires($expires)
	{
		if (!self::$privateDetectionMode) if ('auto' == self::$expires || 'ontouch' == self::$expires) self::$expires = $expires;

		if (self::$catchMeta) self::$metaInfo[2] = $expires;
	}

	static function watch($watch)
	{
		if (self::$catchMeta) self::$metaInfo[3] = array_merge(self::$metaInfo[3], (array) $watch);
	}

	static function canPost()
	{
		if (self::$catchMeta) self::$metaInfo[5] = true;
	}

	static function string($a)
	{
		return is_object($a) ? $a->__toString() : (string) $a;
	}

	static function uniqid() {return hash('md5', uniqid(mt_rand(), true));}

	/**
	 * Revokes every agent watching $message
	 */
	static function touch($message)
	{
		if (is_array($message)) foreach ($message as &$message) self::touch($message);
		else
		{
			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			$i = 0;

			@include self::getCachePath('watch/' . $message, 'php');

#>			E("CIA::touch('$message'): $i file(s) deleted.");
		}
	}

	/**
	 * Like mkdir(), but works with multiple level of inexistant directory
	 */
	static function makeDir($dir)
	{
		$dir = dirname($dir . ' ');

		if (is_dir($dir)) return;

		$dir = preg_split("'[/\\\\]+'u", $dir);

		if (!$dir) return;

		if ($dir[0]==='')
		{
			array_shift($dir);
			if (!$dir) return;
			$dir[0] = '/' . $dir[0];
		}
		else if (!(CIA_WINDOWS && ':' == substr($dir[0], -1))) $dir[0] = './' . $dir[0];

		$new = array();

		while ($dir && !is_dir( implode('/', $dir)))
		{
			$new[] = array_pop($dir);
		}

		if ($new)
		{
			$dir = implode('/', $dir);

			while ($new)
			{
				$dir .= '/' . array_pop($new);
				mkdir($dir);
			}
		}
	}

	static function fopenX($file, &$readHandle = false)
	{
		self::makeDir($file);

		if ($h = @fopen($file, 'x+b'))
		{
			flock($h, LOCK_EX+LOCK_NB, $w);

			if ($w) fclose($h);
			else return $h;
		}

		if ($readHandle)
		{
			$readHandle = fopen($file, 'rb');
			flock($readHandle, LOCK_SH);
		}

		return false;
	}

	/**
	 * Creates the full directory path to $filename, then writes $data into this file
	 */
	static function writeFile($filename, &$data, $Dmtime = 0)
	{
		$tmpname = dirname($filename) . '/' . self::uniqid();

		$h = @fopen($tmpname, 'wb');

		if (!$h)
		{
			self::makeDir($tmpname);
			$h = @fopen($tmpname, 'wb');
		}

		if ($h)
		{
			fwrite($h, $data, strlen($data));
			fclose($h);

			if (CIA_WINDOWS)
			{
				file_exists($filename) && unlink($filename);
				@rename($tmpname, $filename) || E('Failed rename');
			}
			else rename($tmpname, $filename);

			if ($Dmtime) touch($filename, $_SERVER['REQUEST_TIME'] + $Dmtime);

			return true;
		}
		else return false;
	}


	protected static function getCachePath($filename, $extension, $key = '')
	{
		if (''!==(string)$extension) $extension = '.' . $extension;

		$hash = hash('md5', $filename . $extension . '.'. $key);
		$hash = $hash{0} . '/' . $hash{1} . '/' . substr($hash, 2);

		$filename = rawurlencode(str_replace('/', '.', $filename));
		$filename = substr($filename, 0, 224 - strlen($extension));

		return self::$cachePath . $hash . '.' . $filename . $extension;
	}

	static function getContextualCachePath($filename, $extension, $key = '')
	{
		return self::getCachePath($filename, $extension, self::$home .'-'. self::$lang .'-'. DEBUG .'-'. CIA_PROJECT_PATH .'-'. $key);
	}

	static function log($message, $is_end = false, $raw_html = true)
	{
		static $prev_time = CIA;
		self::$total_time += $a = 1000*(microtime(true) - $prev_time);

		if ('__getDeltaMicrotime' !== $message)
		{
			if ($is_end) $a = sprintf('Total: %.02f ms</pre><pre>', self::$total_time);
			else
			{
				$b = self::$handlesOb ? serialize($message) : print_r($message, true);

				if (!$raw_html) $b = htmlspecialchars($b);

				$a = '<acronym title="' . date("d-m-Y H:i:s", $_SERVER['REQUEST_TIME']) . '">' . sprintf('%.02f', $a) . ' ms</acronym>: ' . $b . "\n";
			}

			$b = ini_get('error_log');
			$b = fopen($b ? $b : './error.log', 'ab');
			flock($b, LOCK_EX);
			fwrite($b, $a, strlen($a));
			fclose($b);
		}

		$prev_time = microtime(true);

		return $a;
	}

	protected static function resolveAgentClass($agent, &$args)
	{
		static $resolvedCache = array();


		if (isset($resolvedCache[$agent])) return 'agent_' . str_replace('/', '_', $agent);


		$agent = preg_replace("'/(\.?/)+'", '/', '/' . $agent . '/');

		do $agent = preg_replace("'[^/]+/\.\./'", '/', $a = $agent);
		while ($a != $agent);

		$agent = substr($agent, 1, -1);
		$agent = preg_replace("'^(\.\.?/)+'", '', $agent);

		preg_match("'^((?:[a-z0-9]+(?:[-_][a-z0-9]+)*(?:/|$))*)(.*?)$'iu", $agent, $agent);

		$param = '' !== $agent[2] ? explode('/', $agent[2]) : array();
		$agent = $agent[1];

		if ('/' == substr($agent, -1)) $agent = substr($agent, 0, -1);

		if ('' !== $agent)
		{
			$potentialAgent = preg_replace("'[-_](.)'e", "strtoupper('$1')", $agent);
		}
		else $potentialAgent = $agent = 'index';

		$lang = self::$lang;
		$createTemplate = true;

		while (1)
		{
			if (isset($resolvedCache[$potentialAgent]))
			{
				$createTemplate = false;
				break;
			}

			$path = "class/agent/{$potentialAgent}.php";
			$p_th = processPath($path);
			if ($path != $p_th)
			{
				$createTemplate = false;
				break;
			}


			$path = "public/{$lang}/{$potentialAgent}.tpl";
			if ($path != resolvePath($path)) break;

			$path = "public/__/{$potentialAgent}.tpl";
			if ($path != resolvePath($path)) break;


			if ('index' == $potentialAgent) break;


			$a = strrpos($agent, '/');

			if ($a)
			{
				array_unshift($param, substr($agent, $a + 1));
				$agent = substr($agent, 0, $a);
				$potentialAgent = substr($potentialAgent, 0, strrpos($potentialAgent, '/'));
			}
			else
			{
				array_unshift($param, $agent);
				$potentialAgent = $agent = 'index';
			}
		}

		if ($param)
		{
			$args['__0__'] = implode('/', $param);

			$i = 0;
			foreach ($param as &$param) $args['__' . ++$i . '__'] = $param;
		}

		$resolvedCache[$potentialAgent] = true;

		$agent = 'agent_' . str_replace('/', '_', $potentialAgent);

		if ($createTemplate) eval('class ' . $agent . ' extends agent{protected $maxage=-1;protected $watch=array(\'public/templates\');function control(){}}');

		return $agent;
	}

	protected static function agentArgv($agent)
	{
		// get declared arguments in $agent::$argv public property
		$args = get_class_vars($agent);
		$args =& $args['argv'];

		if (is_array($args)) array_walk($args, array('self', 'stripArgv'));
		else $args = array();

		// autodetect private data for antiXSJ
		$cache = self::getContextualCachePath('antiXSJ.' . $agent, 'txt');
		$readHandle = true;
		if ($h = self::fopenX($cache, $readHandle))
		{
			$private = '';

			self::$privateDetectionMode = true;

			try
			{
				new $agent;
			}
			catch (PrivateDetection $d)
			{
				$private = '1';
			}
			catch (Exception $d)
			{
			}

			fwrite($h, $private, strlen($private));
			fclose($h);

			self::$privateDetectionMode = false;

			if ($private) $args[] = 'T$';
		}
		else
		{
			$cache = fstat($readHandle);
			fclose($readHandle);
			if ($cache['size']) $args[] = 'T$';
		}

		return $args;
	}

	static function resolveAgentTrace($agent)
	{
		static $cache = array();

		if (isset($cache[$agent])) return $cache[$agent];
		else $cache[$agent] =& $trace;

		$args = array();
		$HOME = $home = self::__HOME__();
		$agent = self::home($agent, true);
		$keys = false;
		$s = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';
		$s = "/w\.k\((-?[0-9]+),($s),($s),($s),\[((?:$s(?:,$s)*)?)\]\)/su";

		if (
			   0 === strpos($agent, $HOME)
			&& !ini_get('safe_mode')
			&& is_callable('shell_exec')
			&& (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? !extension_loaded('openssl') : false)
			&& $keys = $GLOBALS['CONFIG']['php']
		)
		{
			$keys = $keys . ' -q ' . implode(' ', array_map('escapeshellarg', array(
				processPath('getTrace.php'),
				resolvePath('config.php'),
				$_SERVER['CIA_HOME'],
				self::__LANG__(),
				substr($agent, strlen($HOME)),
				isset($_SERVER['HTTPS']) ? (bool) $_SERVER['HTTPS'] : false
			)));

			$keys = shell_exec($keys);

			if (!preg_match($s, $keys, $keys)) $keys = false;
		}

		if (!$keys)
		{
			$agent = implode(self::__LANG__(), explode('__', $agent, 2));

			if (ini_get('allow_url_fopen'))
			{
				$keys = file_get_contents($agent . '?k$', false, stream_context_create(array('http' => array('method' => 'GET'))));
			}
			else
			{
				require_once 'HTTP/Request.php';

				$keys = new HTTP_Request($agent . '?k$');
				$keys->sendRequest();
				$keys = $keys->getResponseBody();
			}

			if (!preg_match($s, $keys, $keys))
			{
				E('Error while getting meta info data for ' . htmlspecialchars($agent));
				self::disable(true);
			}
		}

		$CIApID = (int) $keys[1];
		$home = stripcslashes(substr($keys[2], 1, -1));
		$home = preg_replace("'__'", self::__LANG__(), $home, 1);
		$agent = stripcslashes(substr($keys[3], 1, -1));
		$a = stripcslashes(substr($keys[4], 1, -1));
		$keys = eval('return array(' . $keys[5] . ');');

		if ('' !== $a)
		{
			$args['__0__'] = $a;

			$i = 0;
			foreach (explode('/', $a) as $a) $args['__' . ++$i . '__'] = $a;
		}

		if ($home == $HOME) $CIApID = $home = false;
		else self::watch('foreignTrace');

		return $trace = array($CIApID, $home, $agent, $keys, $args);
	}

	protected static function stripArgv(&$a, $k)
	{
		if (is_string($k)) $a = $k;

		$b = strpos($a, ':');
		if (false !== $b) $a = substr($a, 0, $b);
	}

	protected static function agentCache($agentClass, $keys, $type, $group = false)
	{
		if (false === $group) $group = self::$metaInfo[1];
		$keys = serialize(array($keys, $group));

		return self::getContextualCachePath($agentClass, $type . '.php', $keys);
	}

	static function writeWatchTable($message, $file, $exclusive = true)
	{
		$file =  "++\$i;unlink('" . str_replace(array('\\',"'"), array('\\\\',"\\'"), $file) . "');\n";

		foreach (array_unique((array) $message) as $message)
		{
			if (self::$catchMeta) self::$metaInfo[3][] = $message;

			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			$path = self::getCachePath('watch/' . $message, 'php');
			if ($exclusive) self::$watchTable[] = $path;

			self::makeDir($path);

			$h = fopen($path, 'a+b');
			flock($h, LOCK_EX);
			fseek($h, 0, SEEK_END);
			if ($file_isnew = !ftell($h)) $file = "<?php ++\$i;unlink(__FILE__);\n" . $file;
			fwrite($h, $file, strlen($file));
			fclose($h);

			if ($file_isnew)
			{
				$message = explode('/', $message);
				while (array_pop($message) !== null)
				{
					$file = "include '" . str_replace(array('\\', "'"), array('\\\\', "\\'"), $path) . "';\n";

					$path = self::getCachePath('watch/' . implode('/', $message), 'php');

					self::makeDir($path);

					$h = fopen($path, 'a+b');
					flock($h, LOCK_EX);
					fseek($h, 0, SEEK_END);
					if ($file_isnew = !ftell($h)) $file = "<?php ++\$i;unlink(__FILE__);\n" . $file;
					fwrite($h, $file, strlen($file));
					fclose($h);

					if (!$file_isnew) break;
				}
			}
		}
	}

	protected static function preventXSJ()
	{
		if (!CIA_TOKEN_MATCH)
		{
			self::setMaxage(0);
			if (self::$catchMeta) self::$metaInfo[1] = array('private');

			if (CIA_DIRECT)
			{
				$a = '';

				$cache = self::getContextualCachePath('antiXSJ.' . self::$agentClass, 'txt');

				self::makeDir($cache);

				$h = fopen($cache, 'a+b');
				flock($h, LOCK_EX);
				fseek($h, 0, SEEK_END);
				if (!ftell($h))
				{
					self::touch('CIApID');
					self::touch('public/templates/js');

					fwrite($h, $a = '1', 1);
					touch('config.php');
				}
				fclose($h);

				throw new PrivateDetection($a);
			}

			E('Potential Cross Site JavaScript. Stopping !');

			self::disable(true);
		}
	}


	static function &ob_filterOutput(&$buffer, $mode)
	{
		self::$handlesOb = true;

		$one_chunk = $mode == (PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_END);

		if ('' === $buffer && $one_chunk)
		{
			self::$handlesOb = false;
			return $buffer;
		}

#>		if (self::$isServersideHtml || CIA_DIRECT) $buffer = self::error_end() . $buffer;


		// Anti-XSRF token

		if (stripos(self::$headers['content-type'], 'html'))
		{
			static $lead;

			if (PHP_OUTPUT_HANDLER_START & $mode) $lead = '';

			$tail = '';

			if (!(PHP_OUTPUT_HANDLER_END & $mode))
			{
				$meta = strrpos($buffer, '<');
				if (false !== $meta)
				{
					$tail = strrpos($buffer, '>');
					if (false !== $tail && $tail > $meta) $meta = $tail;

					$tail = substr($buffer, $meta);
					$buffer = substr($buffer, 0, $meta);
				}
			}

			$buffer = $lead . $buffer;
			$lead = $tail;


			$meta = stripos($buffer, '<form');
			if (false !== $meta)
			{
				$meta = preg_replace_callback(
					'#<form\s(?:[^>]+?\s)?method\s*=\s*(["\']?)post\1.*?>#iu',
					array(__CLASS__, 'appendAntiXSJ'),
					$buffer
				);

				if ($meta != $buffer)
				{
					self::$private = true;
					if (!(isset($_COOKIE['JS']) && $_COOKIE['JS'])) self::$maxage = 0;
					$buffer = $meta;
				}

				unset($meta);
			}
		}


		// GZip compression

		if (!in_array(substr(self::$headers['content-type'], 14), self::$noGzip))
		{
			if ($one_chunk)
			{
				if (strlen($buffer) > 100)
				{
					self::$varyEncoding = true;
					self::$is_enabled || header('Vary: Accept-Encoding');

					$mode = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';

					if ($mode)
					{
						$algo = array(
							'deflate'  => 'gzdeflate',
							'gzip'     => 'gzencode',
							'compress' => 'gzcompress',
						);

						foreach ($algo as $encoding => $algo) if (false !== stripos($mode, $encoding))
						{
							self::$contentEncoding = $encoding;
							self::$is_enabled || header('Content-Encoding: ' . $encoding);
							$buffer = $algo($buffer);
							break;
						}
					}
				}
			}
			else
			{
				self::$contentEncoding = true;
				self::$varyEncoding = true;
				if (!self::$is_enabled && (PHP_OUTPUT_HANDLER_START & $mode)) header('Vary: Accept-Encoding');
				$buffer = ob_gzhandler($buffer, $mode);
			}
		}

		if ($one_chunk && !self::$is_enabled) header('Content-Length: ' . strlen($buffer));

		self::$handlesOb = false;
		return $buffer;
	}

	static function &ob_sendHeaders(&$buffer)
	{
		self::$handlesOb = true;


		if (self::$redirecting)
		{
			$buffer = '';
			self::$handlesOb = false;
			return $buffer;
		}


		$is304 = false;

		if (!CIA_POSTING && ('' !== $buffer || self::$ETag))
		{
			if (!self::$maxage) self::$maxage = 0;


			/* ETag / Last-Modified validation */

			$meta = self::$maxage . "\n"
				. self::$private . "\n"
				. implode("\n", self::$headers);

			$ETag = substr(hash('md5', self::$ETag .'-'. $buffer .'-'. self::$expires .'-'. $meta), 0, 8);
			$ETag = hexdec($ETag);
			if ($ETag > 2147483647) $ETag -= 2147483648;

			$LastModified = gmdate('D, d M Y H:i:s \G\M\T', $ETag);
			$ETag = dechex($ETag);


			$is304 = (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $ETag)
				|| (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 0===strpos($_SERVER['HTTP_IF_MODIFIED_SINCE'], $LastModified));


			if ('ontouch' == self::$expires || ('auto' == self::$expires && self::$watchTable))
			{
				self::$expires = 'auto';
				$ETag = '-' . $ETag;
			}


			/* Write watch table */

			if ('auto' == self::$expires && self::$watchTable)
			{
				$validator = self::$cachePath . $ETag[1] .'/'. $ETag[2] .'/'. substr($ETag, 3) .'.validator.'. DEBUG .'.';
				$validator .= hash('md5', $_SERVER['CIA_HOME'] .'-'. $_SERVER['CIA_LANG'] .'-'. CIA_PROJECT_PATH .'-'. $_SERVER['REQUEST_URI']) . '.txt';

				chdir(CIA_PROJECT_PATH);

				if ($h = self::fopenX($validator))
				{
					fwrite($h, $meta, strlen($meta));
					fclose($h);

					$a = "++\$i;unlink('$validator');\n";

					foreach (array_unique(self::$watchTable) as $path)
					{
						$h = fopen($path, 'ab');
						flock($h, LOCK_EX);
						fwrite($h, $a, strlen($a));
						fclose($h);
					}

					self::writeWatchTable('CIApID', $validator);
				}
			}

			header('ETag: ' . $ETag);
			header('Last-Modified: ' . $LastModified);
			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (self::$private || !self::$maxage ? 0 : self::$maxage)));
			header('Cache-Control: max-age=' . self::$maxage . (self::$private ? ',private,must' : ',public,proxy') . '-revalidate');
			self::$varyEncoding && header('Vary: Accept-Encoding');

			if ($is304)
			{
				$buffer = '';
				header('HTTP/1.1 304 Not Modified');
			}
		}

		if (!$is304)
		{
			is_string(self::$contentEncoding) && header('Content-Encoding: ' . self::$contentEncoding);
			self::$is_enabled                 && header('Content-Length: ' . strlen($buffer));
		}

		self::$handlesOb = false;
		if ('HEAD' == $_SERVER['REQUEST_METHOD']) exit;

		return $buffer;
	}

	static function error_handler($code, $message, $file, $line, $context)
	{
		if (!error_reporting()
			|| ((E_NOTICE == $code || E_STRICT == $code) && 0!==strpos($file, end($GLOBALS['cia_paths'])))
			|| (E_WARNING == $code && false !== stripos($message, 'safe mode'))
		) return;
		self::$has_error = true;
		require processPath('error_handler.php');
	}

	static function appendAntiXSJ($f)
	{
		$f = $f[0];

		// AntiXSJ token is appended only to local application's form

		// Extract the action attribute
		if (1 < preg_match_all('#\saction\s*=\s*(["\']?)(.*?)\1([^>]*)>#iu', $f, $a, PREG_SET_ORDER)) return $f;

		if ($a)
		{
			$a = $a[0];
			$a = trim($a[1] ? $a[2] : ($a[2] . $a[3]));

			if (0 !== strpos($a, self::$home))
			{
				// Decode html encoded chars
				if (false !== strpos($a, '&'))
				{
					static $entitiesRx = "'&(nbsp|iexcl|cent|pound|curren|yen|brvbar|sect|uml|copy|ordf|laquo|not|shy|reg|macr|deg|plusmn|sup2|sup3|acute|micro|para|middot|cedil|sup1|ordm|raquo|frac14|frac12|frac34|iquest|Agrave|Aacute|Acirc|Atilde|Auml|Aring|AElig|Ccedil|Egrave|Eacute|Ecirc|Euml|Igrave|Iacute|Icirc|Iuml|ETH|Ntilde|Ograve|Oacute|Ocirc|Otilde|Ouml|times|Oslash|Ugrave|Uacute|Ucirc|Uuml|Yacute|THORN|szlig|agrave|aacute|acirc|atilde|auml|aring|aelig|ccedil|egrave|eacute|ecirc|euml|igrave|iacute|icirc|iuml|eth|ntilde|ograve|oacute|ocirc|otilde|ouml|divide|oslash|ugrave|uacute|ucirc|uuml|yacute|thorn|yuml|quot|lt|gt|amp|[xX][0-9a-fA-F]+|[0-9]+);'";

					$a = preg_replace_callback($entitiesRx, array(__CLASS__, 'translateHtmlEntity'), $a);
				}

				// Build absolute URI
				if (preg_match("'^[^:/]*://[^/]*'", $a, $host))
				{
					$host = $host[0];
					$a = substr($a, strlen($host));
				}
				else
				{
					$host = substr(self::$host, 0, -1);

					if ('/' != substr($a, 0, 1))
					{
						static $uri = false;

						if (!$uri)
						{
							$uri = $_SERVER['REQUEST_URI'];

							if (false !== ($b = strpos($uri, '?'))) $uri = substr($uri, 0, $b);

							$uri = dirname($uri . ' ');

							if (
								   ''  === $uri
								|| '/'  == $uri
								|| '\\' == $uri
							)    $uri  = '/';
							else $uri .= '/';
						}

						$a = $uri . $a;
					}
				}

				if (false !== ($b = strpos($a, '?'))) $a = substr($a, 0, $b);
				if (false !== ($b = strpos($a, '#'))) $a = substr($a, 0, $b);

				$a .= '/';

				// Resolve relative paths
				if (false !== strpos($a, './') || false !== strpos($a, '//'))
				{
					$b = $a;

					do
					{
						$a = $b;
						$b = str_replace('/./', '/', $b);
						$b = str_replace('//', '/', $b);
						$b = preg_replace("'/[^/]*[^/\.][^/]*/\.\./'", '/', $b);
					}
					while ($b != $a);
				}

				// Compare action to application's home
				if (0 !== strpos($host . $a, self::$home)) return $f;
			}
		}

		static $appendedHtml = false;

		if (!$appendedHtml)
		{
			$appendedHtml = self::$isServersideHtml ? 'syncXSJ()' : '(function(){var d=document,f=d.forms;f=f[f.length-1].T$.value=d.cookie.match(/(^|; )T\\$=([0-9a-zA-Z]+)/)[2]})()';
			$appendedHtml = '<input type="hidden" name="T$" value="' . (isset($_COOKIE['JS']) && $_COOKIE['JS'] ? '' : $GLOBALS['cia_token']) . '" /><script type="text/javascript">' . "<!--\n{$appendedHtml}//--></script>";
		}

		return $f . $appendedHtml;
	}

	static function translateHtmlEntity($c)
	{
		static $table = false;

		if (!$table) $table = array_flip(get_html_translation_table(HTML_ENTITIES));

		if (isset($table[$c[0]])) return utf8_encode($table[$c[0]]);

		$c = strtolower($c[1]);

		if ('x' == $c[0]) $c = hexdec(substr($c, 1));

		$c = sprinf('%08x', (int) $c);

		if (strlen($c) > 8) return '';

		$r = '';

		do
		{
			$a = substr($c, 0, 2);
			$c = substr($c, 2);

			if ('00' != $a) $r .= chr(hexdec($a));
		}
		while ($c);

		return $r;
	}

#>>>
	static protected function error_end()
	{
		$bgcolor = self::$has_error ? 'red' : 'blue';
		$debugWin = self::$home . '_?d$&stop&' . mt_rand();
		$QDebug = self::$home . 'js/QDebug.js';
		$lang = CIA::__LANG__();

		if (self::$isServersideHtml) return <<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript">/*<![CDATA[*/
_____ = new Date/1;
onload = function() {
window.debugWin = open('$debugWin','debugWin','toolbar=no,status=yes,resizable=yes,scrollbars,width=320,height=240,left=' + parseInt(screen.availWidth - 340) + ',top=' + parseInt(screen.availHeight - 290));
if (!debugWin) alert('Disable anti-popup to use the Debug Window');
else E('Rendering time: ' + (new Date/1 - _____) + ' ms');
};
//]]></script>
<div style="position:fixed;_position:absolute;float:right;font-family:arial;font-size:9px;top:0px;right:0px;z-index:255"><a href="javascript:;" onclick="window.debugWin&&debugWin.focus()" style="background-color:$bgcolor;color:white;text-decoration:none;border:0px;" id="debugLink">Debug</a>&nbsp<a href="javascript:;" onclick="location.reload(1)" style="background-color:$bgcolor;color:white;text-decoration:none;border:0px;">Reload</a><script type="text/javascript" src="$QDebug"></script></div>

EOHTML;

		else if (self::$has_error) return "L=document.getElementById('debugLink'); L && (L.style.backgroundColor='$bgcolor');";
	}
#<<<
}

class agent
{
	const binary = false;

	public $argv = array();

	protected $template = '';

	protected $maxage  = 0;
	protected $expires = 'auto';
	protected $canPost = false;
	protected $watch = array();

	function control() {}
	function compose($o) {return $o;}
	function getTemplate()
	{
		return $this->template ? $this->template : str_replace('_', '/', substr(get_class($this), 6));
	}

	final public function __construct($args = array())
	{
		$a = (array) $this->argv;

		$this->argv = (object) array();
		$_GET = array();

		foreach ($a as $key => &$a)
		{
			if (is_string($key))
			{
				$default = $a;
				$a = $key;
			}
			else $default = '';

			$a = explode(':', $a);
			$key = array_shift($a);

			$b = isset($args[$key]) ? (string) $args[$key] : '';
			if (false !== strpos($b, "\0")) $b = str_replace("\0", '', $b);

			if ($a)
			{
				$b = VALIDATE::get($b, array_shift($a), $a);
				if (false === $b) $b = $default;
			}

			$_GET[$key] = $this->argv->$key = $b;
		}

		$this->control();
	}

	function metaCompose()
	{
		CIA::setMaxage($this->maxage);
		CIA::setExpires($this->expires);
		CIA::watch($this->watch);
		if ($this->canPost) CIA::canPost();
	}
}

class loop
{
	private $loopLength = false;
	private $filter = array();

	protected function prepare() {}
	protected function next() {}

	final public function &loop()
	{
		$catchMeta = CIA::$catchMeta;
		CIA::$catchMeta = true;

		if ($this->loopLength === false) $this->loopLength = (int) $this->prepare();

		if (!$this->loopLength) $data = false;
		else
		{
			$data = $this->next();
			if ($data || is_array($data))
			{
				$data = (object) $data;
				$i = 0;
				$len = count($this->filter);
				while ($i<$len) $data = (object) call_user_func($this->filter[$i++], $data, $this);
			}
			else $this->loopLength = false;
		}

		CIA::$catchMeta = $catchMeta;

		return $data;
	}

	final public function addFilter($filter) {if ($filter) $this->filter[] = $filter;}

	final public function __toString()
	{
		$catchMeta = CIA::$catchMeta;
		CIA::$catchMeta = true;

		if ($this->loopLength === false) $this->loopLength = (int) $this->prepare();

		CIA::$catchMeta = $catchMeta;

		return (string) $this->loopLength;
	}

	final public function getLength()
	{
		return (int) $this->__toString();
	}
}

class PrivateDetection extends Exception {}
