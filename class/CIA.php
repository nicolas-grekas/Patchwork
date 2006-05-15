<?php

class CIA
{
	public static $agentClass;
	public static $catchMeta = false;

	protected static $host;
	protected static $lang = '__';
	protected static $home;
	protected static $uri;

	protected static $handlesOb = false;
	protected static $binaryMode = false;
	protected static $metaInfo;
	protected static $metaPool = array();

	protected static $maxage = false;
	protected static $private = false;
	protected static $expires = 'ontouch';
	protected static $watchTable = array();
	protected static $headers = array();

	protected static $cia;
	protected static $redirectUrl = false;
	protected static $agentClasses = '';
	protected static $cancelled = false;

	public static function start()
	{
		if (DEBUG) self::$cia = new debug_CIA;
		else self::$cia = new CIA;

		self::setLang($_SERVER['CIA_LANG'] ? $_SERVER['CIA_LANG'] : substr($GLOBALS['CONFIG']['lang_list'], 0, 2));

		if (htmlspecialchars(self::$home) != self::$home)
		{
			E('Fatal error: illegal character found in CIA::$home');
			exit;
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
		if (0===stripos($string, 'http/'));
		else if (0===stripos($string, 'etag'));
		else if (0===stripos($string, 'last-modified'));
		else if (0===stripos($string, 'expires'));
		else if (0===stripos($string, 'cache-control'));
		else if (0===stripos($string, 'content-length'));
		else
		{
			$string = preg_replace("'[\r\n].*'", '', $string);

			$name = strtolower(substr($string, 0, strpos($string, ':')));

			self::$headers[$name] = $string;

			if (self::$catchMeta) self::$metaInfo[4][$name] = $string;

			header($string);
		}
	}

	/**
	 * Redirect the web browser to an other GET request
	 */
	public static function redirect($url = '', $exit = true)
	{
		$url = (string) $url;

		self::$redirectUrl = '' === $url ? '' : (preg_match("'^([^:/]+:/|\.+)?/'i", $url) ? $url : (self::$home . ('index' == $url ? '' : $url)));

		if ($exit) exit;
	}

	public static function openMeta($agentClass, $is_trace = true)
	{
		self::$agentClass = $agentClass = str_replace('_', '/', $agentClass);
		if ($is_trace) self::$agentClasses .= '*' . self::$agentClass;

		$default = array(false, false, false, array(), array(), false, self::$agentClass);

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

	public static function setBinaryMode($binaryMode)
	{
		self::$binaryMode = $binaryMode;
	}

	/**
	 * Controls the Cache Control headers.
	 */
	public static function setMaxage($maxage)
	{
		if ($maxage < 0) $maxage = CIA_MAXAGE;
		else $maxage = min(CIA_MAXAGE, $maxage);

		if (false === self::$maxage) self::$maxage = $maxage;
		else self::$maxage = min(self::$maxage, $maxage);

		if (self::$catchMeta)
		{
			if (false === self::$metaInfo[0]) self::$metaInfo[0] = $maxage;
			else self::$metaInfo[0] = min(self::$metaInfo[0], $maxage);
		}
	}

	/**
	 * Controls the Cache Control headers.
	 */
	public static function setPrivate($private = true)
	{
		if ($private)
		{
			self::$private = true;
			if (self::$catchMeta) self::$metaInfo[1] = true;
		}
	}

	/**
	 * Controls the Cache Control headers.
	 */
	public static function setExpires($expires)
	{
		self::$expires = $expires;

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

			self::recursiveUnwatch('./tmp/cache/watch/' . $message . '/');
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
	public static function delDir($dir, $rmdir = false)
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
		$a = @file_put_contents($filename, $data);

		if (!$a)
		{
			self::makeDir($filename);
			$a = file_put_contents($filename, $data);
		}

		if ($a)
		{
			if ($Dmtime) touch($filename, CIA_TIME + $Dmtime);
			return true;
		}
		else return false;
	}


	/*
	 * The following methods are used internally, mainly by the IA_* class
	 */

	public static function makeCacheDir($prefix, $extension = '', $key = '')
	{
		static $prefixKey = false;

		if (!$prefixKey) $prefixKey = substr(md5(self::$home .'-'. self::$lang), -8) . '.' . DEBUG;

		if ($key!=='')
		{
			$key = md5($key);
			$key{5} = $key{2} = '/';
		}

		return './tmp/cache/' . $prefixKey . '/' . $prefix . $key . ($extension!=='' ? '.' . $extension : '');
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
		if ($createTemplate) eval('class ' . $agent . ' extends agent {protected $maxage =-1;protected $watch=array(\'public/templates\');}');

		return $agent;
	}

	public static function agentArgv($agent)
	{
		$agent = get_class_vars($agent);
		$agent =& $agent['argv'];

		if (is_array($agent)) array_walk($agent, array('self', 'stripArgv'));
		else $agent = array();

		return $agent;
	}

	public static function resolveAgentTrace($agent)
	{
		$args = array();
		$HOME = $home = CIA::__HOME__();
		$agent = self::home($agent);

		if (0 === strpos($agent, $HOME)) $agent = substr($agent, strlen($HOME));
		else
		{
			require_once 'HTTP/Request.php';
			$agent = preg_replace("'__'", CIA::__LANG__(), $agent, 1);
			$keys = new HTTP_Request($agent);
			$keys->addQueryString('$k', '');
			$keys->sendRequest();
			$keys = $keys->getResponseBody();

			$s = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';

			if (!preg_match("/w\.k\((-?[0-9]+),($s),($s),($s),\[((?:$s(?:,$s)*)?)\]\)/su", $keys, $keys))
			{
				E('Error while getting meta info data for ' . htmlspecialchars($agent));
				exit;
			}

			self::watch('foreignTrace');

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
		}

		if ($home == $HOME)
		{
			return array(false, false, $agent,  self::agentArgv(self::resolveAgentClass($agent, $args)), $args);
		}
		else
		{
			return array((int) $CIApID, $home, $agent, $keys, $args);
		}
	}

	protected static function stripArgv(&$a, $k)
	{
		if (is_string($k)) $a = $k;

		$b = strpos($a, ':');
		if (false !== $b) $a = substr($a, 0, $b);
	}

	public static function agentCache($agentClass, $keys, $type)
	{
		$cagent = '_';
		foreach ($keys as $key => $value) $cagent .= '&' . rawurlencode($key) . '=' . rawurlencode($value);

		return self::makeCacheDir(str_replace('_', '/', $agentClass) . '/_/', $type . '.php', $cagent);
	}

	public static function delCache()
	{
		self::touch('');
		self::delDir(CIA_PROJECT_PATH . '/tmp/cache/', true);
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

			self::$watchTable[] = $path = './tmp/cache/watch/' . $message . '/table.php';

			if (file_exists($path))
			{
				$h = fopen($path, 'ab');
				fwrite($h, $file, strlen($file));
				fclose($h);
			}
			else
			{
				$h = "<?php\n" . $file;
				self::writeFile($path, $h);

				$message = explode('/', $message);
				while (array_pop($message) !== null)
				{
					$path = './tmp/cache/watch/' . implode('/', $message) . '/table.php';
					if (file_exists($path)) break;

					$h = "<?php\n";
					CIA::writeFile($path, $h);
				}
			}
		}
	}

	private static function recursiveUnwatch($dirname)
	{
		if (file_exists($dirname . 'table.php'))
		{
			@include $dirname . 'table.php';

			unlink($dirname . 'table.php');

			$dir = opendir($dirname);

			while (($file = readdir($dir)) !== false)
			{
				if ($file != '.' && $file != '..' && is_dir($dirname . $file)) self::recursiveUnwatch($dirname . $file . '/');
			}

			closedir($dir);
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
		if (class_exists('USER', false)) USER::end();
		if (class_exists('AUTH', false)) AUTH::end();
		if (class_exists('SESSION', false)) SESSION::end();
		if (class_exists('DB', false)) DB()->commit();
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

			$is304 = @$_SERVER['HTTP_IF_NONE_MATCH'] == $ETag || @$_SERVER['HTTP_IF_MODIFIED_SINCE'] == $LastModified;

			if ('ontouch' == self::$expires && self::$watchTable) $ETag = '/' . md5(self::$agentClasses .'_'. $buffer) . "-$ETag";

			if (!$is304)
			{
				header('ETag: ' . $ETag);
				header('Last-Modified: ' . $LastModified);
			}

			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', CIA_TIME + self::$maxage));
			header('Cache-Control: max-age=' . self::$maxage . (self::$private ? ',private,must' : ',public,proxy') . '-revalidate');


			/* Write watch table */

			if ('ontouch' == self::$expires && self::$watchTable)
			{
				$ETag{6} = $ETag{3} = '/';
				$ETag = './tmp/cache/validator.' . DEBUG . '/' . $ETag . '.txt';

				if (!file_exists($ETag))
				{
					$h = self::$maxage . "\n"
						. self::$private . "\n"
						. implode("\n", self::$headers);

					self::writeFile($ETag, $h);

					foreach (array_unique(self::$watchTable) as $path)
					{
						$h = fopen($path, 'ab');
						fwrite($h, "unlink('$ETag');\n");
						fclose($h);
					}
				}
			}

			if ($is304)
			{
				header('HTTP/1.x 304 Not Modified');

				$buffer = '';
			}
		}

		header('Content-Length: ' . strlen($buffer));

		if ('HEAD' == $_SERVER['REQUEST_METHOD']) $buffer = '';

		self::$handlesOb = false;
		return $buffer;
	}

	public function error_handler($code, $message, $file, $line, $context)
	{
		if (!error_reporting() || (E_STRICT == $code && 0!==strpos($file, end($GLOBALS['cia_paths'])))) return;
		$this->has_error = true;
		require resolvePath('error_handler.php');
	}
}

class agent_
{
	public $argv = array();
	public $binary = false;

	protected $template;

	protected $maxage  = 0;
	protected $private = false;
	protected $expires = 'ontouch';
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
		CIA::setPrivate($this->private);
		CIA::setExpires($this->expires);
		CIA::watch($this->watch);
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
