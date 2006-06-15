<?php

class CIA
{
	public static $cachePath = 'zcache/';
	public static $agentClass;
	public static $catchMeta = false;

	protected static $host;
	protected static $lang = '__';
	protected static $home;
	protected static $uri;

	protected static $handlesOb = false;
	protected static $metaInfo;
	protected static $metaPool = array();

	protected static $maxage = false;
	protected static $private = false;
	protected static $expires = 'auto';
	protected static $watchTable = array();
	protected static $headers = array();

	protected static $cia;
	protected static $redirectUrl = false;
	protected static $agentClasses = '';
	protected static $sessionStarted = false;
	protected static $cancelled = false;
	protected static $privateDetectionMode = false;
	protected static $detectXSJ = false;

	public static function start()
	{
		$cachePath = resolvePath(self::$cachePath);
		self::$cachePath = ($cachePath == self::$cachePath ? $GLOBALS['cia_paths'][count($GLOBALS['cia_paths']) - 2] . DIRECTORY_SEPARATOR : '') . $cachePath;

		if (DEBUG) self::$cia = new debug_CIA;
		else self::$cia = new CIA;

		self::setLang($_SERVER['CIA_LANG'] ? $_SERVER['CIA_LANG'] : substr($GLOBALS['CONFIG']['lang_list'], 0, 2));

		if (htmlspecialchars(self::$home) != self::$home)
		{
			E('Fatal error: illegal character found in CIA::$home');
			exit;
		}
	}

	public static function loadSession($private = true)
	{
		if ($private) self::setGroup('private');

		if (!self::$sessionStarted)
		{
			self::$sessionStarted = true;
			@session_start();
		}
	}

	public static function cancel()
	{
		self::$cancelled = true;
		ob_end_flush();
	}

	public static function setLang($new_lang)
	{
		$lang = self::$lang;
		self::$lang = $new_lang;

		self::$home = explode('__', $_SERVER['CIA_HOME'], 2);
		self::$home = implode($new_lang, self::$home);

		self::$host = substr(self::$home, 0, strpos(self::$home, '/', 8)+1);
		self::$uri = self::$host . substr($_SERVER['REQUEST_URI'], 1);

		return $lang;
	}

	public static function __HOST__() {return self::$host;}
	public static function __LANG__() {return self::$lang;}
	public static function __HOME__() {return self::$home;}
	public static function __URI__()  {return self::$uri ;}

	public static function home($url)
	{
		if (!preg_match("'^https?://'", $url))
		{
			if ('/' != substr($url, 0, 1)) $url = self::$home . $url;
			else $url = self::$host . substr($url, 1);
		}

		return $url;
	}

	/**
	 * Replacement for PHP's header() function
	 */
	public static function header($string)
	{
		if (!self::$cancelled && (
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

		if (!self::$privateDetectionMode)
		{
			if ('content-type' == $name && false !== strpos(strtolower($string), 'javascript'))
			{
				if (self::$private) self::preventXSJ();

				self::$detectXSJ = true;
			}

			self::$headers[$name] = $string;
			header($string);
		}
	}

	/**
	 * Redirect the web browser to an other GET request
	 */
	public static function redirect($url = '', $exit = true)
	{
		if (self::$privateDetectionMode)
		{
			if ($exit) throw new Exception;

			return;
		}

		$url = (string) $url;

		self::$redirectUrl = '' === $url ? '' : (preg_match("'^([^:/]+:/|\.+)?/'i", $url) ? $url : (self::$home . ('index' == $url ? '' : $url)));

		if ($exit) exit;
	}

	public static function openMeta($agentClass, $is_trace = true)
	{
		self::$agentClass = $agentClass;
		if ($is_trace) self::$agentClasses .= '*' . self::$agentClass;

		$default = array(false, array(), false, array(), array(), false, self::$agentClass);

		self::$catchMeta = true;

		self::$metaPool[] =& $default;
		self::$metaInfo =& $default;
	}

	public static function closeMeta()
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
	public static function setMaxage($maxage)
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
	public static function setGroup($group)
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

			if (count($a) == 1 && 'private' == $a[0]) return;

			if (in_array('private', $group)) $a = array('private');
			else
			{
				$a = array_unique( array_merge($a, $group) );
				sort($a);
			}
		}
	}

	/**
	 * Controls the Cache's expiration mechanism.
	 */
	public static function setExpires($expires)
	{
		if (!self::$privateDetectionMode) if ('auto' == self::$expires || 'ontouch' == self::$expires) self::$expires = $expires;

		if (self::$catchMeta) self::$metaInfo[2] = $expires;
	}

	public static function watch($watch)
	{
		if (self::$catchMeta) self::$metaInfo[3] = array_merge(self::$metaInfo[3], (array) $watch);
	}

	public static function canPost()
	{
		if (self::$catchMeta) self::$metaInfo[5] = true;
	}

	public static function string($a)
	{
		return is_object($a) ? $a->__toString() : (string) $a;
	}

	public static function uniqid() {return md5( uniqid(mt_rand(), true) );}

	/**
	 *  Returns the hash of $pwd if this hash match $crypted_pwd or if $crypted_pwd is not supplied. Else returns false.
	 */
	public static function pwd($pwd, $crypted_pwd = false)
	{
		static $saltLen = 4;

		if ($crypted_pwd !== false)
		{
			$salt = substr($crypted_pwd, 0, $saltLen);
			if ($salt . md5($pwd . $salt) != $crypted_pwd) return false;
		}

		$a = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
		$b = strlen($a) - 1;

		$salt = '';
		do $salt .= $a{ mt_rand(0, $b) }; while (--$saltLen);

		return $salt . md5($pwd . $salt);
	}

	/**
	 * Revokes every agent watching $message
	 */
	public static function touch($message)
	{
		if (is_array($message)) foreach ($message as $message) self::touch($message);
		else
		{
			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			@include self::getCachePath('watch/' . $message, 'php');
		}
	}

	/**
	 * Like mkdir(), but works with multiple level of inexistant directory
	 */
	public static function makeDir($dir)
	{
		$dir = preg_split("'[/\\\\]+'u", dirname("$dir "));

		if (!$dir) return;

		if ($dir[0]==='')
		{
			array_shift($dir);
			if (!$dir) return;
			$dir[0] = '/' . $dir[0];
		}
		else if (!(substr(PHP_OS, 0, 3) == 'WIN' && substr($dir[0], -1) == ':')) $dir[0] = './' . $dir[0];

		$a = '';
		$b = array_shift($dir) . '/';
		while (is_dir($b))
		{
			$a = array_shift($dir);
			if ($a===null) break;
			$b .= $a . '/';
		}

		if ($a!==null) while (mkdir($b))
		{
			$a = array_shift($dir);
			if ($a===null) break;
			$b .= $a . '/';
		}
	}

	/**
	 * Sort of recursive version of rmdir()
	 */
	public static function delDir($dir, $rmdir)
	{
		$d = @opendir($dir);
		if (!$d) return;

		while (false !== ($file = readdir($d)))
		{
			if ($file!='.' && $file!='..')
			{
				$file = "$dir/$file";
				if(is_dir($file)) self::delDir($file, $rmdir);
				else @unlink($file);
			}
		}

		closedir($d);
		if ($rmdir) @rmdir($dir); // Time consuming
	}

	/**
	 * Creates the full directory path to $filename, then writes $data into this file
	 */
	public static function writeFile($filename, &$data, $Dmtime = 0)
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

			if ('WIN' == substr(PHP_OS, 0, 3)) @unlink($filename);
			rename($tmpname, $filename);

			if ($Dmtime) touch($filename, CIA_TIME + $Dmtime);

			return true;
		}
		else return false;
	}


	/*
	 * The following methods are used internally, mainly by the IA_* class
	 */

	public static function getCachePath($filename, $extension, $key = '')
	{
		if (''!==(string)$extension) $extension = '.' . $extension;

		$hash = md5($filename . $extension . '.'. $key);
		$hash = $hash{0} . '/' . $hash{1} . '/' . substr($hash, 2);

		$filename = rawurlencode(str_replace('/', '.', $filename));
		$filename = substr($filename, 0, 224 - strlen($extension));

		return self::$cachePath . $hash . '.' . $filename . $extension;
	}

	public static function makeCacheDir($filename, $extension, $key = '')
	{
		return self::getCachePath($filename, $extension, self::$home .'-'. self::$lang .'-'. DEBUG .'-'. CIA_PROJECT_PATH .'-'. $key);
	}

	public static function ciaLog($message, $is_end = false, $html = true)
	{
		return self::$cia->log($message, $is_end, $html);
	}

	public static function resolveAgentClass($agent, &$args)
	{
		static $resolvedCache = array();


		if (isset($resolvedCache[$agent])) return 'agent_' . str_replace('/', '_', $agent);


		$agent = preg_replace("'/(\.?/)+'", '/', '/' . $agent . '/');

		do $agent = preg_replace("'[^/]+/\.\./'", '/', $a = $agent);
		while ($a != $agent);

		$agent = substr($agent, 1, -1);
		$agent = preg_replace("'^(\.\.?/)+'", '', $agent);

		preg_match("'^((?:[\w\d]+(?:/|$))*)(.*?)$'u", $agent, $agent);

		$param = '' !== $agent[2] ? explode('/', $agent[2]) : array();
		$agent = $agent[1];

		if ('/' == substr($agent, -1)) $agent = substr($agent, 0, -1);

		$agent = '' !== $agent ? $agent : 'index';

		$lang = self::$lang;
		$createTemplate = true;

		while (1)
		{
			if (isset($resolvedCache[$agent]))
			{
				$createTemplate = false;
				break;
			}

			$path = "class/agent/{$agent}.php";
			$p_th = resolvePath($path);
			if ($path != $p_th)
			{
				require $p_th;
				$createTemplate = false;
				break;
			}


			$path = "public/{$lang}/{$agent}.tpl";
			if ($path != resolvePath($path)) break;

			$path = "public/__/{$agent}.tpl";
			if ($path != resolvePath($path)) break;


			if ('index' == $agent) break;


			$a = strrpos($agent, '/');

			if ($a)
			{
				array_unshift($param, substr($agent, $a + 1));
				$agent = substr($agent, 0, $a);
			}
			else
			{
				array_unshift($param, $agent);
				$agent = 'index';
			}
		}

		if ($param)
		{
			$args['__0__'] = implode('/', $param);

			$i = 0;
			foreach ($param as $param) $args['__' . ++$i . '__'] = $param;
		}

		$resolvedCache[$agent] = true;

		$agent = 'agent_' . str_replace('/', '_', $agent);

		/*
		* eval() is known to be slow.
		* Instead, we could write once this PHP code in a file, and include it on subsequent calls.
		* But is it faster ? Maybe with both an opcode cache and a memory filesystem. Else, I doubt ...
		*/
		if ($createTemplate) eval('class ' . $agent . ' extends agent {protected $maxage=-1;protected $watch=array(\'public/templates\');}');

		return $agent;
	}

	public static function agentArgv($agent)
	{
		// get declared arguments in $agent::$argv public property
		$args = get_class_vars($agent);
		$args =& $args['argv'];

		if (is_array($args)) array_walk($args, array('self', 'stripArgv'));
		else $args = array();

		// autodetect private data for antiXSJ
		$cache = self::makeCacheDir('antiXSJ.' . $agent, 'txt');
		if (file_exists($cache))
		{
			if (filesize($cache)) $args[] = 'T$';
		}
		else
		{
			$private = '';

			self::$privateDetectionMode = true;

			try
			{
				$agent = new $agent;
				$d = (object) $agent->compose();
				$agent->getTemplate();

				self::executeLoops($d);

				$agent->metaCompose();
			}
			catch (PrivateDetection $d)
			{
				$private = 1;
			}
			catch (Exception $d)
			{
			}

			self::$privateDetectionMode = false;

			self::writeFile($cache, $private);

			if ($private) $args[] = 'T$';
		}

		return $args;
	}

	protected static function executeLoops($d)
	{
		foreach ($d as $k => $v) if ($v instanceof loop) while ($k = $v->compose()) self::executeLoops($k);
	}

	public static function resolveAgentTrace($agent)
	{
		static $cache = array();

		if (isset($cache[$agent])) return $cache[$agent];
		else $cache[$agent] =& $trace;

		$args = array();
		$HOME = $home = CIA::__HOME__();
		$agent = self::home($agent);

		require_once 'HTTP/Request.php';
		$agent = preg_replace("'__'", CIA::__LANG__(), $agent, 1);
		$keys = new HTTP_Request($agent);
		$keys->addQueryString('k$', '');
		$keys->sendRequest();
		$keys = $keys->getResponseBody();

		$s = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';

		if (!preg_match("/w\.k\((-?[0-9]+),($s),($s),($s),\[((?:$s(?:,$s)*)?)\]\)/su", $keys, $keys))
		{
			E('Error while getting meta info data for ' . htmlspecialchars($agent));
			exit;
		}

		$CIApID = (int) $keys[1];
		$home = stripcslashes(substr($keys[2], 1, -1));
		$home = preg_replace("'__'", CIA::__LANG__(), $home, 1);
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

	public static function agentCache($agentClass, $keys, $type, $group = false)
	{
		if (false === $group) $group = self::$metaInfo[1];
		$keys = serialize(array($keys, $group));

		return self::makeCacheDir($agentClass, $type . '.php', $keys);
	}

	public static function delCache()
	{
		self::touch('');
		self::delDir(self::$cachePath, false);
	}

	public static function writeWatchTable($message, $file)
	{
		$file =  "unlink('" . str_replace(array('\\',"'"), array('\\\\',"\\'"), $file) . "');\n";

		foreach (array_unique((array) $message) as $message)
		{
			if (self::$catchMeta) self::$metaInfo[3][] = $message;

			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			self::$watchTable[] = $path = self::getCachePath('watch/' . $message, 'php');

			if (file_exists($path))
			{
				$h = fopen($path, 'ab');
				flock($h, LOCK_EX);
				fseek($h, 0, SEEK_END);
				fwrite($h, $file, strlen($file));
				fclose($h);
			}
			else
			{
				$h = "<?php unlink(__FILE__);\n" . $file;
				self::writeFile($path, $h);

				$message = explode('/', $message);
				while (array_pop($message) !== null)
				{
					$file = "include '" . str_replace(array('\\',"'"), array('\\\\',"\\'"), $path) . "';\n";

					$path = self::getCachePath('watch/' . implode('/', $message), 'php');
					if (file_exists($path))
					{
						$h = fopen($path, 'ab');
						flock($h, LOCK_EX);
						fseek($h, 0, SEEK_END);
						fwrite($h, $file, strlen($file));
						fclose($h);

						break;
					}

					$h = "<?php unlink(__FILE__);\n" . $file;
					CIA::writeFile($path, $h);
				}
			}
		}
	}

	protected static function preventXSJ()
	{
		if (!CIA_TOKEN_MATCH)
		{
			if (CIA_DIRECT)
			{
				$cache = self::makeCacheDir('antiXSJ.' . self::$agentClass, 'txt');
				if (!file_exists($cache) || !filesize($cache))
				{
					touch('index.php');
					CIA::touch('CIApID');
					CIA::touch('public/templates');

					$a = 1;
					self::writeFile($cache, $a);

					echo 'location.reload(' . (DEBUG ? '' : 'true') . ')';
					exit;
				}
			}

			E('Potential Cross Site JavaScript. Stopping !');
			E($_SERVER); E($_POST); E($_COOKIE);

			exit;
		}
	}


	/*
	* CIA object
	*/

	protected $has_error = false;

	public function __construct()
	{
		self::header('Content-Type: text/html; charset=UTF-8');
		set_error_handler(array($this, 'error_handler'));
		register_shutdown_function(array($this, 'shutdown'));
		ob_start(array($this, 'ob_handler'));
	}

	public function shutdown()
	{
		if (self::$sessionStarted) session_write_close();
		DB(true);
	}

	public function &ob_handler(&$buffer)
	{
		self::$handlesOb = true;
		chdir(CIA_PROJECT_PATH);

		if (self::$redirectUrl !== false)
		{
			if (CIA_DIRECT)
			{
				$buffer = 'location.replace(' . (self::$redirectUrl !== ''
					? "'" . addslashes(self::$redirectUrl) . "'"
					: 'location') . ')';
			}
			else
			{
				header('HTTP/1.x 302 Found');
				header('Location: ' . (self::$redirectUrl !== '' ? self::$redirectUrl : $_SERVER['REQUEST_URI']));

				$buffer = '';
			}

			self::$handlesOb = false;

			return $buffer;
		}


		if (self::$cancelled)
		{
			self::$handlesOb = false;
			return $buffer;
		}


		if (!CIA_POSTING && $buffer !== '')
		{
			if (!self::$maxage) self::$maxage = 0;

			/* ETag / Last-Modified validation */

			$ETag = sprintf('%u', crc32($buffer .'_'. self::$maxage .'_'. self::$private .'_'. self::$expires));
			$LastModified = $ETag - 2147483647 * (int) ($ETag / 2147483647);
			$LastModified = gmdate('D, d M Y H:i:s \G\M\T', $LastModified);

			$is304 = @$_SERVER['HTTP_IF_NONE_MATCH'] == $ETag || 0===strpos(@$_SERVER['HTTP_IF_MODIFIED_SINCE'], $LastModified);

			if ('ontouch' == self::$expires || ('auto' == self::$expires && self::$watchTable))
			{
				self::$expires = 'auto';
				$ETag = '/' . md5(self::$agentClasses .'_'. $buffer) . '-' . $ETag;
			}

			if (!$is304)
			{
				header('ETag: ' . $ETag);
				header('Last-Modified: ' . $LastModified);
			}

			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', CIA_TIME + self::$maxage));
			header('Cache-Control: max-age=' . self::$maxage . (self::$private ? ',private,must' : ',public,proxy') . '-revalidate');


			/* Write watch table */

			if ('auto' == self::$expires && self::$watchTable)
			{
				$ETag = $ETag[1] . '/' . $ETag[2] . '/' . substr($ETag, 3) . '.validator.';
				$ETag = self::$cachePath . $ETag . DEBUG . '.txt';

				if (!file_exists($ETag))
				{
					$h = self::$maxage . "\n"
						. self::$private . "\n"
						. implode("\n", self::$headers);

					self::writeFile($ETag, $h);

					$a = "unlink('$ETag');\n";

					foreach (array_unique(self::$watchTable) as $path)
					{
						$h = fopen($path, 'ab');
						flock($h, LOCK_EX);
						fseek($h, 0, SEEK_END);
						fwrite($h, $a, strlen($a));
						fclose($h);
					}

					self::writeWatchTable('CIApID', $ETag);
				}
			}

			if ($is304)
			{
				header('HTTP/1.x 304 Not Modified');

				$buffer = '';
			}
		}


		if ('HEAD' == $_SERVER['REQUEST_METHOD']) $buffer = '';


		if (function_exists('apache_setenv')) apache_setenv('no-gzip', '1'); // This disables mod_deflate

		switch (substr(self::$headers['content-type'], 14))
		{
			case 'image/png':
			case 'image/gif':
			case 'image/jpeg':
				header('Content-Length: ' . strlen($buffer));
				@ini_set('zlib.output_compression', false);
				break;

			default:
				if (!ini_get('zlib.output_compression')) $buffer = ob_gzhandler($buffer, 5);
				break;
		}

		self::$handlesOb = false;
		return $buffer;
	}

	public function error_handler($code, $message, $file, $line, $context)
	{
		if (!error_reporting()
			|| ((E_NOTICE == $code || E_STRICT == $code) && 0!==strpos($file, end($GLOBALS['cia_paths'])))
			|| (E_WARNING == $code && false !== stripos($message, 'safe mode'))
		) return;
		$this->has_error = true;
		require resolvePath('error_handler.php');
	}
}

class agent_
{
	const binary = false;

	public $argv = array();

	protected $template;

	protected $maxage  = 0;
	protected $expires = 'auto';
	protected $canPost = false;
	protected $watch = array();

	public function control() {}
	public function compose() {return (object) array();}
	public function getTemplate()
	{
		return isset($this->template) ? $this->template : str_replace('_', '/', substr(get_class($this), 6));
	}

	final public function __construct($args = array())
	{
		$a = (array) $this->argv;

		$this->argv = (object) array();
		$_GET = array();

		array_walk($a, array($this, 'populateArgv'), (object) $args);

		$this->control();
	}

	public function metaCompose()
	{
		CIA::setMaxage($this->maxage);
		CIA::setExpires($this->expires);
		CIA::watch($this->watch);
		if ($this->canPost) CIA::canPost();
	}

	private function populateArgv(&$a, $key, $args)
	{
		if (is_string($key))
		{
			$default = $a;
			$a = $key;
		}
		else $default = '';

		$a = explode(':', $a);
		$key = array_shift($a);

		$args = @$args->$key;

		if ($a)
		{
			$args = VALIDATE::get($args, array_shift($a), $a);
			if (false === $args) $args = $default;
		}

		$_GET[$key] = $this->argv->$key = $args;
	}
}

class loop
{
	private $loopLength = false;
	private $filter = array();

	protected function prepare() {}
	protected function next() {}

	final public function &compose()
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
