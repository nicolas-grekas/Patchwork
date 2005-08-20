<?php

class CIA
{
	public static $pageId = '';
	public static $handlesOb = false;

	protected static $maxage = 0;
	protected static $private = false;
	protected static $expires = false;

	protected static $cia;
	protected static $redirectUrl = false;
	protected static $watchTable = array();
	protected static $headers = array();

	public static function start()
	{
		if (DEBUG) self::$cia = new debug_CIA;
		else self::$cia = new CIA;
	}

	/**
	 * Replacement for PHP's header() function
	 */
	public static function header($string, $replace = true)
	{
		if (0===stripos($string, 'http/'));
		else if (0===stripos($string, 'etag'));
		else if (0===stripos($string, 'last-modified'));
		else if (0===stripos($string, 'expires'));
		else if (0===stripos($string, 'cache-control'));
		else if (0===stripos($string, 'content-length'));
		else
		{
			$name = strtolower(substr($string, 0, strpos($string, ':')));

			if ($replace || !isset(self::$headers[$name])) self::$headers[$name] = $string;
			else self::$headers[$name] .= "\n" . $string;

			header($string, $replace);
		}
	}

	/**
	 * Redirect the web browser to an other GET request
	 */
	public static function redirect($url = '', $external = false, $exit = true)
	{
		self::$redirectUrl = $url=='' ? $_SERVER['REQUEST_URI'] : (($external ? '' : CIA_ROOT) . $url);
		if ($exit) exit;
	}

	/**
	 * Controls the Cache Control headers.
	 */
	public static function setCacheControl($maxage, $private, $expires, $watch = array())
	{
		static $firstCall = true;

		if ($maxage < 0) $maxage = CIA_MAXAGE;
		else $maxage = min(CIA_MAXAGE, $maxage);

		$expires = !('ontouch' == $expires && $watch);

		if ($firstCall)
		{
			$firstCall = false;
			self::$maxage = $maxage;
			self::$private = (bool) $private;
			self::$expires = (bool) $expires;
		}
		else
		{
			self::$maxage = min(self::$maxage, $maxage);
			if ($private) self::$private = 1;
			if ($expires) self::$expires = 1;
		}

		foreach (array_unique((array) $watch) as $message)
		{
			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			self::$watchTable[] = './tmp/cache/watch/' . $message . '/table.php';
		}
	}

	/*
	* Replacement for PHP's htmlspecialchars() function, with some differencies
	*/
	public static function htmlescape($string, $amps = false)
	{
		if ($amps) $string = str_replace('&', '&amp;', $string);

		return str_replace(
			array('<'   , '>'   , '"'     , "'"     ),
			array('&lt;', '&gt;', '&quot;', '&#039;'),
			$string
		);
	}

	public static function string($a)
	{
		return is_object($a) ? $a->__toString() : (string) $a;
	}

	public static function uniqid() {return md5( uniqid(mt_rand(), true) );}
	public static function hash($str) {return md5( $str . CIA_SECRET );}

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
				else unlink($file);
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
	 * The following methods are used internally, mainly by the IA class
	 */

	public static function makeCacheDir($prefix, $extension = '', $key = '', $lang = true)
	{
		if ($key!=='')
		{
			$key = md5($key);
			$key{5} = $key{2} = '/';
		}

		return './tmp/cache/' . ($lang ? CIA_LANG . '/' : '') . $prefix . $key . ($extension!=='' ? '.' . $extension : '');
	}

	public static function ciaLog($message, $is_end = false, $html = true)
	{
		return self::$cia->log($message, $is_end, $html);
	}

	public static function agentClass($agent)
	{
		return $agent == '' ? 'agent_index' : preg_replace("'[^a-zA-Z\d]+'u", '_', "agent_$agent");
	}

	public static function delCache()
	{
		self::touch('');
		self::delDir(CIA_PROJECT_PATH . '/tmp/cache/', true);
	}

	public static function watch($message, $file)
	{
		$file =  "@unlink(" . var_export($file, true) . ");\n";

		foreach (array_unique((array) $message) as $message)
		{
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
		ob_start(array($this, 'ob_handler'));
	}

	public function &ob_handler(&$buffer)
	{
		self::$handlesOb = true;
		chdir(CIA_PROJECT_PATH);

		if (class_exists('USER', false)) USER::end();
		if (class_exists('AUTH', false)) AUTH::end();
		if (class_exists('SESSION', false)) SESSION::end();

		if (self::$redirectUrl !== false)
		{
			if (CIA_DIRECT)
			{
				$buffer = "window.location='" . addslashes(self::$redirectUrl) . "'";
			}
			else
			{
				header('HTTP/1.x 302 Found');
				header('Location: ' . self::$redirectUrl);
				self::$handlesOb = false;
				return '';
			}
		}

		if (!CIA_POSTING && $buffer !== '')
		{
			if (DEBUG > 1)
			{
				self::$maxage = 0;
				self::$private = true;
			}


			/* ETag / Last-Modified validation */

			$ETag = sprintf('%u', crc32($buffer .'_'. self::$maxage .'_'. self::$private .'_'. self::$expires));
			$LastModified = $ETag - 2147483647 * (int) ($ETag / 2147483647);
			$LastModified = gmdate('D, d M Y H:i:s \G\M\T', $LastModified);

			$is304 = @$_SERVER['HTTP_IF_NONE_MATCH'] == $ETag || @$_SERVER['HTTP_IF_MODIFIED_SINCE'] == $LastModified;

			if (!self::$expires) $ETag = '/' . md5($buffer) . "-$ETag";

			if (!$is304)
			{
				header('ETag: ' . $ETag);
				header('Last-Modified: ' . $LastModified);
			}

			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', CIA_TIME + self::$maxage));
			header('Cache-Control: max-age=' . self::$maxage . (self::$private ? ',private,must' : ',public,proxy') . '-revalidate');


			/* Write watch table */

			if (!self::$expires)
			{
				$ETag{6} = $ETag{3} = '/';
				$ETag = './tmp/cache/validator/' . CIA_PROJECT_ID . $ETag . '.txt';

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

class agent
{
	public $argv = array();
	public $binary = false;

	protected $template;
	protected $maxage  = 0;
	protected $private = false;
	protected $expires = 'ontouch';
	protected $watch = array();

	public function init() {}
	public function render() {return (object) array();}
	public function getTemplate()
	{
		return isset($this->template) ? $this->template : str_replace('_', '/', substr(get_class($this), 6));
	}

	final public function __construct($args)
	{
		$args = (array) $args;

		$a = $this->argv;

		$this->argv = (object) array();
		foreach ($a as $key) $this->argv->$key = @$args[$key];

		$this->init();
	}

	public function __destruct()
	{
		CIA::setCacheControl(
			$this->maxage,
			$this->private,
			$this->expires,
			$this->watch
		);
	}
}

class agentTemplate_ extends agent
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
	private $length = false;
	private $renderer = array();

	protected function prepare() {}
	protected function next() {}

	final public function &render()
	{
		if ($this->length === false) $this->length = $this->prepare();

		if (!$this->length) return false;
		else
		{
			$data = $this->next();
			if ($data || is_array($data))
			{
				$data = (object) $data;
				$i = 0;
				$len = count($this->renderer);
				while ($i<$len) $data = (object) call_user_func($this->renderer[$i++], $data);

				return $data;
			}
			else
			{
				$this->length = false;
				return $data;
			}
		}
	}

	final public function addRenderer($renderer) {if ($renderer) $this->renderer[] = $renderer;}
	
	final public function __toString()
	{
		if ($this->length === false) $this->length = $this->prepare();
		return (string) $this->length;
	}

	final public function getLength()
	{
		return (int) $this->__toString();
	}
}
