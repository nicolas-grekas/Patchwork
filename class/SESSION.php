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


class
{
	/* Public properties */

	static $IPlevel = 2;

	static $maxIdleTime = 0;
	static $maxLifeTime = 43200;

	static $cookiePath = '';
	static $cookieDomain = '';
	static $cookieHttpOnly = true;

	static $gcProbabilityNumerator = 1;
	static $gcProbabilityDenominator = 100;


	/* Protected properties */

	protected static $DATA;
	protected static $adapter = false;

	protected static $SID = '';
	protected static $lastseen = '';
	protected static $birthtime = '';
	protected static $sslid = '';


	/* Public methods */

	static function getSID() {return self::$SID;}
	static function getLastseen() {return self::$lastseen;}

	static function get($name)
	{
		return isset(self::$DATA[$name]) ? self::$DATA[$name] : '';
	}

	static function set($name, $value = '')
	{
		if (is_array($name) || is_object($name)) foreach($name as $k => &$value) self::$DATA[$k] =& $value;
		else if ('' === $value) unset(self::$DATA[$name]);
		else self::$DATA[$name] =& $value;
	}

	static function bind($name, &$value)
	{
		$value = self::get($name);
		self::set(array($name => &$value));
	}

	static function getAll()
	{
		$a = array();
		foreach (self::$DATA as $k => &$v) $a[$k] =& $v;
		return $a;
	}

	static function regenerateId($initSession = false, $restartNew = true)
	{
		if (self::$adapter)
		{
			self::$adapter->reset();
			self::$adapter = false;
		}

		if ($initSession) self::$DATA = array();


		// Generate a new antiCSRF token

		$GLOBALS['cia_token'] = CIA::uniqid();

		$sid = implode($_SERVER['CIA_LANG'], explode('__', $_SERVER['CIA_HOME'], 2));
		$sid = preg_replace("'\?.*$'", '', $sid);
		$sid = preg_replace("'^https?://[^/]*'i", '', $sid);
		$sid = dirname($sid . ' ');
		if (1 == strlen($sid)) $sid = '';

		setcookie('T$', $GLOBALS['cia_token'], 0, $sid .'/');


		if (!$initSession || $restartNew)
		{
			$sid = CIA::uniqid();
			self::setSID($sid);

			self::$adapter = new SESSION(self::$SID);

			self::$lastseen = $_SERVER['REQUEST_TIME'];
			self::$birthtime = $_SERVER['REQUEST_TIME'];
		}
		else $sid = '';

		header(
			'Set-Cookie: SID=' . $sid .
				( self::$cookiePath ? '; path=' . urlencode(self::$cookiePath) : '; path=/' ) .
				( self::$cookieDomain ? '; domain=' . urlencode(self::$cookieDomain) : '' ) .
				( $sid ? '' : '; expires=Thu, 01-Jan-1970 00:00:00 GMT' ) .
				( self::$cookieHttpOnly ? '; HttpOnly' : '' ),
			false
		);

		// 304 Not Modified response code does not allow Set-Cookie headers,
		// so we remove any header that could trigger a 304
		unset($_SERVER['HTTP_IF_NONE_MATCH']);
		unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	}

	static function destroy()
	{
		self::regenerateId(true, false);
	}

	static function close()
	{
		self::$adapter = false;
	}


	/* Protected methods */

	static function __static_construct()
	{
		CIA::setGroup('private');

		if (self::$maxIdleTime<1 && self::$maxLifeTime<1) W('At least one of the SESSION::$max*Time variables must be strictly positive.');

		self::$sslid = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? hash('md5', $_SERVER['SSL_SESSION_ID']) : false;

		$i = self::$gcProbabilityNumerator + 1;
		$j = self::$gcProbabilityDenominator - 1;
		while (--$i && mt_rand(0, $j));
		if ($i)
		{
			$adapter = new SESSION('0lastGC');
			$i = $adapter->read();
			$j = max(self::$maxIdleTime, self::$maxLifeTime);
			if ($j && $_SERVER['REQUEST_TIME'] - $i > $j)
			{
				$adapter->write($_SERVER['REQUEST_TIME']);
				self::gc($j);
			}
			unset($adapter);
		}

		self::setSID(isset($_COOKIE['SID']) ? $_COOKIE['SID'] : '');

		self::$adapter = new SESSION(self::$SID);

		if ($i = self::$adapter->read())
		{
			$i = unserialize($i);
			self::$lastseen =  $i[0];
			self::$birthtime = $i[1];
			if ((self::$maxIdleTime && $_SERVER['REQUEST_TIME'] - self::$lastseen > self::$maxIdleTime))
			{
				// Session has expired
				self::regenerateId(true);
			}
			else if ((self::$maxLifeTime && $_SERVER['REQUEST_TIME'] - self::$birthtime > self::$maxLifeTime))
			{
				// Session has idled
				self::regenerateId(true);
			}
			else self::$DATA =& $i[2];

			if (self::$sslid)
			{
				if (!$i[3]) self::regenerateId();
				else if ($i[3]!=self::$sslid) self::regenerateId(true);
			}
		}
		else self::regenerateId(true);
	}

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

		self::$SID = hash('md5', $SID .'-'. $IPs .'-'. (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') .'-'. $GLOBALS['cia_token']);
	}


	/* Adapter */

	protected $handle;
	protected $path;

	protected function __construct($sid)
	{
		$this->path = CIA::$cachePath . '0/'. $sid[0] .'/'. substr($sid, 1) .'.session';

		CIA::makeDir($this->path);

		$this->handle = fopen($this->path, 'a+b');
		flock($this->handle, LOCK_EX);
	}

	function __destruct()
	{
		if ($this->handle)
		{
			$this->write(serialize(array(
				$_SERVER['REQUEST_TIME'],
				self::$birthtime,
				self::$DATA,
				self::$sslid
			)));

			fclose($this->handle);
		}
	}

	protected function read()
	{
		return stream_get_contents($this->handle);
	}

	protected function write($value)
	{
		ftruncate($this->handle, 0);
		fwrite($this->handle, $value, strlen($value));
	}

	protected function reset()
	{
		ftruncate($this->handle, 0);
		fclose($this->handle);
		$this->handle = false;

		unlink($this->path);
	}

	protected static function gc($lifetime)
	{
		foreach (glob(CIA::$cachePath . '0/?/*.session', GLOB_NOSORT) as $file)
		{
			if ($_SERVER['REQUEST_TIME'] - filemtime($file) > $lifetime) unlink($file);
		}
	}
}
