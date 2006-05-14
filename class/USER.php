<?php

abstract class USER
{
	public static $driverClass;

	private static $started = false;

	private static $driver;
	private static $DATA;

	public static function &get($name)
	{
		self::start();
		if (!isset(self::$DATA->$name)) self::$DATA->$name = '';
		return self::$DATA->$name;
	}

	public static function set($name, $value = '')
	{
		self::start();
		if (is_array($name)) foreach(array_keys($name) as $k)
		{
			if ('' === (string) $name[$k]) self::$driver->del($k);
			else self::$driver->set($k, $name[$k]);

			self::$DATA->$k =& $name[$k];
		}
		else
		{
			if ('' === (string) $value) self::$driver->del($name);
			else self::$driver->set($name, $value);

			self::$DATA->$name =& $value;
		}
	}


	/* Private methods */

	public static function start()
	{
		if (self::$started) return;

		self::$started = true;

		global $CONFIG;
		self::$driverClass = $driver = 'driver_user_' . $CONFIG['user_driver'];
		self::$driver = new $driver($CONFIG['user_params']);

		self::$driver->open();

		self::$DATA = (object) self::$driver->loadPref();
	}

	public static function end()
	{
		self::$driver->close();
	}


	/* Driver interface */

	protected $userId;
	protected $defaultUserId = 0;

	protected function getUserId()
	{
		$UID = SESSION::get('__USERID');
		return $UID ? $UID : $this->defaultUserId;
	}

	public function open() {$this->userId = $this->getUserId();}

	abstract public function loadPref();
	abstract public function set($prefName, $prefValue);
	abstract public function del($prefName);
	abstract public function close();
}
