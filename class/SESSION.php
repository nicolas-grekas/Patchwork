<?php

abstract class SESSION
{
	/* Public properties */

	public static $driverClass;

	public static $IPlevel = 2;

	public static $maxIdleTime = 3600;
	public static $maxLifeTime = 0;

	public static $cookiePath = '';
	public static $cookieDomain = '';
	public static $cookieSecure = false;
	public static $cookieHttpOnly = true;

	public static $gcProbabilityNumerator = 1;
	public static $gcProbabilityDenominator = 100;


	/* Private properties */

	private static $started = false;

	private static $DATA;
	private static $driver;

	private static $SID = '';
	private static $lastseen = '';
	private static $birthtime = '';
	private static $sslid = '';
	private static $renew = '';


	/* Public methods */

	public static function getSID() {return self::$SID;}
	public static function getLastseen() {return self::$lastseen;}

	public static function &get($name)
	{
		self::start();
		if (!isset(self::$DATA->$name)) self::$DATA->$name = '';
		return self::$DATA->$name;
	}

	public static function set($name, $value = '')
	{
		self::start();

		if ('' === $value) unset(self::$DATA->$name);
		else if (is_array($name)) foreach(array_keys($name) as $k) self::$DATA->$k =& $name[$k];
		else self::$DATA->$name =& $value;
	}

	public static function renew($destroy = true, $initSession = false)
	{
		self::start();

		if ($destroy) self::$driver->destroy(self::$SID);
		if ($initSession) self::$DATA = (object) array();

		self::$renew = true;
		self::setSID($destroy = CIA::uniqid());
		self::$lastseen = CIA_TIME;
		self::$birthtime = CIA_TIME;

		header(
			'Set-Cookie: SID=' . $destroy .
			( self::$cookiePath ? '; path=' . rawurlencode(self::$cookiePath) : '; path=/' ) .
			( self::$cookieDomain ? '; domain=' . rawurlencode(self::$cookieDomain) : '' ) .
			( self::$cookieSecure ? '; secure' : '' ) .
			( self::$cookieHttpOnly ? '; HttpOnly' : '' )
		);
	}

	public static function end()
	{
		self::start();

		static $firstCall = true;
		if ($firstCall)
		{
			$firstCall = false;

			self::$driver->write(self::$SID, serialize(array(
				CIA_TIME,
				self::$birthtime,
				(array) self::$DATA,
				self::$sslid
			)));
			self::$driver->close();
		}

		return (bool) self::$renew;
	}


	/* Private methods */

	private static function start()
	{
		CIA::setPrivate();

		if (self::$started) return;

		self::$started = true;

		global $CONFIG;
		self::$driverClass = $driver = 'driver_session_' . $CONFIG['session_driver'];
		self::$driver = new $driver($CONFIG['session_params']);

		if (self::$maxIdleTime<1 && $maxLifeTime<1) trigger_error('At least one of the SESSION::$max* variables must be strictly positive.');

		self::$sslid = strtolower(@$_SERVER['HTTPS']) == 'on';

		self::$driver->open('', 'SID');

		$i = self::$gcProbabilityNumerator + 1;
		$j = self::$gcProbabilityDenominator - 1;
		while (--$i && mt_rand(0, $j));
		if ($i)
		{
			$i = self::$driver->read('_lastGC');
			$j = max(self::$maxIdleTime, self::$maxLifeTime);
			if ($j && CIA_TIME - $i > $j)
			{
				self::$driver->write('_lastGC', CIA_TIME);
				self::$driver->gc($j);
			}
		}

		self::setSID(@$_COOKIE['SID']);

		if ($i = self::$driver->read(self::$SID))
		{
			$i = unserialize($i);
			self::$lastseen =  $i[0];
			self::$birthtime = $i[1];
			if ((self::$maxIdleTime && CIA_TIME - self::$lastseen > self::$maxIdleTime))
			{
				// Session hax expired
				self::renew(true, true);
			}
			else if ((self::$maxLifeTime && CIA_TIME - self::$birthtime > self::$maxLifeTime))
			{
				// Session has idled
				self::renew(true, true);
			}
			else self::$DATA = (object) $i[2];

			if (self::$sslid)
			{
				self::$sslid = md5(@$_SERVER['SSL_SESSION_ID']);

				if (!$i[3]) self::renew();
				else if ($i[3]!=self::$sslid) self::renew(true, true);
			}
		}
		else
		{
			if (self::$sslid) self::$sslid = md5(@$_SERVER['SSL_SESSION_ID']);
			self::renew(false, true);
		}
	}

	private static function setSID($SID)
	{
		if (self::$IPlevel)
		{
			$IPs = @(
				$_SERVER['REMOTE_ADDR'] . ',' .
				$_SERVER['HTTP_X_FORWARDED_FOR'] . ',' .
				$_SERVER['HTTP_CLIENT_IP']
			);
			preg_match_all('/(?<![\.\d])\d+(?:\.\d+){'.(self::$IPlevel-1).'}/u', $IPs, $IPs);
			$IPs = implode(',', $IPs[0]);
		}
		else $IPs = '';

		self::$SID = @(
			$SID .
			'-' . $IPs .
			'-' . $_SERVER['HTTP_USER_AGENT'] .
			'-' . $_SERVER['HTTP_ACCEPT_LANGUAGE'] .
			'-' . $_SERVER['HTTP_ACCEPT_ENCODING'] .
			'-' . $_SERVER['HTTP_ACCEPT_CHARSET']
		);

		self::$SID = md5(self::$SID);
	}


	/* Driver interface */

	abstract public function open($path, $name);
	abstract public function close();
	abstract public function read($sid);
	abstract public function write($sid, $value);
	abstract public function destroy($sid);
	abstract public function gc($lifetime);
}
