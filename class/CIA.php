<?php

class CIA
{
	public static $agentClass;
	public static $catchMeta = false;

	protected static $lang;
	protected static $root;
	protected static $host;

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

		self::$lang = isset($_SERVER['CIA_LANG']) ? $_SERVER['CIA_LANG'] : substr($CONFIG['lang_list'], 0, 2);
		self::$root = $_SERVER['CIA_ROOT'];
		self::$host = 'http' . (@$_SERVER['HTTPS'] ? 's' : '') . '://' . @$_SERVER['HTTP_HOST'];
	}

	public static function cancel()
	{
		self::$cancelled = true;
		ob_end_flush();
	}

	public static function __LANG__($new_lang = null)
	{
		$lang = self::$lang;
		if (null !== $new_lang) self::$lang = $new_lang;
		return $lang;
	}

	public static function __ROOT__($new_root = null)
	{
		$root = self::$root;

		if (null !== $new_root)
		{
			self::$root = $new_root;
			return $root;
		}

		return $root . self::$lang . '/';
	}

	public static function __HOST__($new_host = null)
	{
		$host = self::$host;
		if (null !== $new_host) self::$host = $new_host;
		return $host;
	}

	public static function getUri($url)
	{
		if (!preg_match("'^https?://'iu", $url)) 
		{
			if ('/' != substr($url, 0, 1)) $url = self::__ROOT__() . $url;

			$url = self::__HOST__() . $url;
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

		self::$redirectUrl = '' === $url ? '' : (preg_match("'^([^:/]+:/|\.+)?/'i", $url) ? $url : (self::__ROOT__() . ('index' == $url ? '' : $url)));

		if ($exit) exit;
	}

	public static function openMeta()
	{
		self::$agentClasses .= '*' . self::$agentClass;

		$default = array(false, false, false, array(), array(), false);

		self::$catchMeta = true;

		self::$metaPool[] =& $default;
		self::$metaInfo =& $default;
	}

	public static function closeMeta()
	{
		self::$catchMeta = false;

		$poped = array_pop(self::$metaPool);

		$len = count(self::$metaPool);

		if ($len) self::$metaInfo =& self::$metaPool[$len-1];
		else self::$metaInfo = null;

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
	public static function setExpires($expires, $watch = false)
	{
		self::$expires = $expires;

		if (self::$catchMeta)
		{
			self::$metaInfo[2] = $expires;
			if ($watch) self::$metaInfo[3] += (array) $watch;
		}
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
	public static function hash($str) {return md5( $str . $CONFIG['secret'] );}

	/**
	 *  Returns the hash of $pwd if this hash match $crypted_pwd or if $crypted_pwd is not supplied. Else returns false. 
	 */
	public static function pwd($pwd, $crypted_pwd = false)
	{
		static $saltLen = 8;

		if ($crypted_pwd !== false)
		{
			$salt = substr($crypted_pwd, 0, $saltLen);
			if ($salt . CIA::hash($pwd . $salt) != $crypted_pwd) return false;
		}

		$a = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
		$b = strlen($a) - 1;

		$salt = '';
		do $salt .= $a{ mt_rand(0, $b) }; while (--$saltLen);

		return $salt . CIA::hash($pwd . $salt);
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

		if (!$prefixKey)
		{
			$prefixKey = @md5(
				self::__ROOT__() . '-' .
				self::$host . '-' .
				DEBUG
			);

			$prefixKey = substr($prefixKey, -8);
		}

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

	public static function agentClass($agent)
	{
		return $agent == '' ? 'agent_index' : preg_replace("'[^a-zA-Z\d]+'u", '_', "agent_$agent");
	}

	public static function agentArgv($agent)
	{
		$agent = get_class_vars($agent);
		$agent = (array) $agent['argv'];

		array_walk($agent, array('self', 'stripArgv'));

		return $agent;
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

		self::$agentClass = $agentClass = str_replace('_', '/', $agentClass);

		return self::makeCacheDir($agentClass . '/_/', $type . '.php', $cagent);
	}

	public static function delCache()
	{
		self::touch('');
		self::delDir(CIA_PROJECT_PATH . '/tmp/cache/', true);
	}

	public static function writeWatchTable($message, $file)
	{
		$file =  "@unlink('" . str_replace(array('\\',"'"), array('\\\\',"\\'"), $file) . "');\n";

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
			include $dirname . 'table.php';

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
				$buffer = 'location.replace(' . (self::$redirectUrl !== '' ? "'" . addslashes(self::$redirectUrl) . "'" : 'location') . ')';
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
				$ETag = './tmp/cache/validator/' . $ETag . '.txt';

				if (!file_exists($ETag))
				{
					$h = self::$maxage . "\n"
						. self::$private . "\n"
						. implode("\n", self::$headers);

					self::writeFile($ETag, $h);

					foreach (array_unique(self::$watchTable) as $path)
					{
						$h = fopen($path, 'ab');
						fwrite($h, "@unlink('$ETag');\n");
						fclose($h);
					}
				}
			}

			if ($is304)
			{
				header('HTTP/1.x 304 Not Modified');
				header('Content-Type:'); // This prevents PHP from sending wrong Content-Type: text/html

				$buffer = '';
			}
		}

		header('Content-Length: ' . strlen($buffer));

		if ($_SERVER['REQUEST_METHOD']=='HEAD') $buffer = '';

		self::$handlesOb = false;
		return $buffer;
	}

	public function error_handler($code, $message, $file, $line, $context)
	{
		if ($code == E_STRICT || !error_reporting()) return;
		require 'error_handler.php';
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
		CIA::setExpires($this->expires, $this->watch);
	}

	private function populateArgv(&$a, $key, $args)
	{
		if (is_string($key))
		{
			$default = $a;
			$a = $key;
		}
		else $default = false;

		$a = explode(':', $a);
		$key = array_shift($a);

		$args = @$args->$key;

		if ($a)
		{
			$a = array_map('rawurldecode', $a);
			$args = VALIDATE::get($args, array_shift($a), $a);
			if (false === $args) $args = $default;
		}

		$_GET[$key] = $this->argv->$key = $args;
	}
}

class agentTemplate_ extends agent_
{
	public $argv = array('template');

	protected $maxage = -1;
	protected $private = false;
	protected $expires = 'ontouch';
	protected $watch = array('public/templates');

	public function getTemplate()
	{
		return str_replace('../', '_', strtr(
			$this->argv->template,
			"\\:*?\"<>|\t\r\n",
			'/__________'
		));
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
