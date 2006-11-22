<?php

if (!isset($GLOBALS['CONFIG']['session_driver'])) $GLOBALS['CONFIG']['session_driver'] = 'default';

eval('class SESSION extends driver_session_' . $GLOBALS['CONFIG']['session_driver'] . '{}');

class driver_session_default
{
	/* Public properties */

	static $class = 'driver_session_default';

	static $IPlevel = 2;

	static $maxIdleTime = 3600;
	static $maxLifeTime = 0;

	static $cookieName = 'SID';
	static $cookiePath = '';
	static $cookieDomain = '';
	static $cookieSecure = false;
	static $cookieHttpOnly = true;

	static $gcProbabilityNumerator = 1;
	static $gcProbabilityDenominator = 100;


	/* Protected properties */

	protected static $started = false;

	protected static $DATA;
	protected static $driver;

	protected static $SID = '';
	protected static $lastseen = '';
	protected static $birthtime = '';
	protected static $sslid = '';


	/* Public methods */

	static function start($private = true)
	{
		if ($private) CIA::setGroup('private');

		if (self::$started) return;

		self::$started = true;

		self::$class = 'driver_session_' . $GLOBALS['CONFIG']['session_driver'];

		if (self::$maxIdleTime<1 && $maxLifeTime<1) trigger_error('At least one of the SESSION::$max*Time variables must be strictly positive.');

		self::$sslid = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? md5($_SERVER['SSL_SESSION_ID']) : false;

		$i = self::$gcProbabilityNumerator + 1;
		$j = self::$gcProbabilityDenominator - 1;
		while (--$i && mt_rand(0, $j));
		if ($i)
		{
			$driver = new self::$class('0lastGC');
			$i = self::$driver->read();
			$j = max(self::$maxIdleTime, self::$maxLifeTime);
			if ($j && $_SERVER['REQUEST_TIME'] - $i > $j)
			{
				self::$driver->write($_SERVER['REQUEST_TIME']);
				self::gc($j);
			}
			unset($driver);
		}

		self::setSID(isset($_COOKIE[self::$cookieName]) ? $_COOKIE[self::$cookieName] : '');

		self::$driver = new self::$class(self::$SID);

		if ($i = self::$driver->read())
		{
			$i = unserialize($i);
			self::$lastseen =  $i[0];
			self::$birthtime = $i[1];
			if ((self::$maxIdleTime && $_SERVER['REQUEST_TIME'] - self::$lastseen > self::$maxIdleTime))
			{
				// Session hax expired
				self::regenerateId(true);
			}
			else if ((self::$maxLifeTime && $_SERVER['REQUEST_TIME'] - self::$birthtime > self::$maxLifeTime))
			{
				// Session has idled
				self::regenerateId(true);
			}
			else self::$DATA = (object) $i[2];

			if (self::$sslid)
			{
				if (!$i[3]) self::regenerateId();
				else if ($i[3]!=self::$sslid) self::regenerateId(true);
			}
		}
		else self::regenerateId(true);
	}

	static function getSID() {return self::$SID;}
	static function getLastseen() {return self::$lastseen;}

	static function &get($name)
	{
		self::$started || self::start();
		if (!isset(self::$DATA->$name)) self::$DATA->$name = '';
		return self::$DATA->$name;
	}

	static function set($name, $value = '')
	{
		self::$started || self::start();

		if ('' === $value) unset(self::$DATA->$name);
		else if (is_array($name)) foreach(array_keys($name) as $k) self::$DATA->$k =& $name[$k];
		else self::$DATA->$name =& $value;
	}

	static function regenerateId($initSession = false)
	{
		self::$started || self::start();

		self::$driver->destroy();
		self::$driver = false;

		if ($initSession) self::$DATA = (object) array();

		$sid = CIA::uniqid();
		self::setSID($sid);

		self::$driver = new self::$class(self::$SID);

		self::$lastseen = $_SERVER['REQUEST_TIME'];
		self::$birthtime = $_SERVER['REQUEST_TIME'];

		header(
			'Set-Cookie: ' . urlencode(self::$cookieName) . '=' . $sid .
			( self::$cookiePath ? '; path=' . urlencode(self::$cookiePath) : '; path=/' ) .
			( self::$cookieDomain ? '; domain=' . urlencode(self::$cookieDomain) : '' ) .
			( self::$cookieSecure ? '; secure' : '' ) .
			( self::$cookieHttpOnly ? '; HttpOnly' : '' )
		);

		// 304 Not Modified response code does not allow Set-Cookie headers, so we remove any header that could trigger a 304
		unset($_SERVER['HTTP_IF_NONE_MATCH']);
		unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	}

	static function close()
	{
		if (!self::$started) return;

		self::$driver->write(serialize(array(
			$_SERVER['REQUEST_TIME'],
			self::$birthtime,
			(array) self::$DATA,
			self::$sslid
		)));

		self::$driver = false;
		self::$started = false;
	}


	/* Protected methods */

	protected static function setSID($SID)
	{
		if (self::$IPlevel)
		{
			// TODO : handle ipv6 ?
			$IPs = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')
				. ',' . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '')
				. ',' . (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '');

			preg_match_all('/(?<![\.\d])\d+(?:\.\d+){'.(self::$IPlevel-1).'}/u', $IPs, $IPs);

			$IPs = implode(',', $IPs[0]);
		}
		else $IPs = '';

		self::$SID = $SID . '-' . $IPs
			. '-' . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')
			. '-' . (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '')
			. '-' . (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '')
			. '-' . (isset($_SERVER['HTTP_ACCEPT_CHARSET' ]) ? $_SERVER['HTTP_ACCEPT_CHARSET' ] : '');

		self::$SID = md5(self::$SID);
	}


	/* Driver */

	protected $handle;
	protected $path;

	protected function __construct($sid)
	{
		$this->path = CIA::$cachePath . '0/'. $sid[0] .'/session.'. substr($sid, 1) .'.txt';

		CIA::makeDir($this->path);

		$this->handle = fopen($this->path, 'a+b');
		flock($this->handle, LOCK_EX);
	}

	function __destruct()
	{
		if ($this->handle) fclose($this->handle);
	}

	protected function read()
	{
		return stream_get_contents($this->handle);
	}

	protected function write($value)
	{
		ftruncate($this->handle, 0);
		rewind($this->handle);
		fwrite($this->handle, $value, strlen($value));
	}

	protected function destroy()
	{
		fclose($this->handle);
		$this->handle = false;

		unlink($this->path);
	}

	protected static function gc($lifetime)
	{
		foreach (glob(CIA::$cachePath . '0/?/session.*.txt', GLOB_NOSORT) as $file)
		{
			if ($_SERVER['REQUEST_TIME'] - filemtime($file) > $lifetime) unlink($file);
		}
	}
}
