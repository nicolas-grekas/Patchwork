<?php

abstract class AUTH
{
	public static $driverClass;

	private static $started = false;

	private static $driver;
	private static $DATA;

	public static function check($right)
	{
		self::start();

		foreach ((array) $right as $r) if (!in_array($r, self::$DATA)) return false;

		return true;
	}

	public static function add($right)
	{
		self::start();

		foreach((array) $right as $right)
		{
			if (!in_array($right, self::$DATA))
			{
				self::$driver->add($right);
				self::$DATA[] = $right;
			}
		}
	}

	public static function del($right)
	{
		self::start();

		foreach((array) $right as $right)
		{
			$key = array_search($right, self::$DATA);
			if ($key !== false)
			{
				self::$driver->del($right);
				unset(self::$DATA[$key]);
			}
		}
	}

	/* Private methods */

	public static function start()
	{
		if (self::$started) return;

		self::$started = true;

		global $CONFIG;
		self::$driverClass = $driver = 'driver_auth_' . $CONFIG['auth_driver'];
		self::$driver = new $driver($CONFIG['auth_params']);

		self::$driver->open();

		self::$DATA = (array) self::$driver->loadAuth();
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

	abstract public function loadAuth();
	abstract public function add($right);
	abstract public function del($right);
	abstract public function close();
}
