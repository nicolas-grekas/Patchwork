<?php

abstract class
{
	static function file($file, $from, $to)
	{
		$driver = self::getDriver($from, $to);
		if (!$driver) return false;

		return $driver->file($file);
	}

	static function data($data, $from, $to)
	{
		$driver = self::getDriver($from, $to);
		if (!$driver) return false;

		return $driver->data($data);
	}

	protected static function getDriver($from, $to)
	{
		$class = 'driver_convertTo_' . $to . '_' . $from;
		if (preg_match("'[^a-zA-Z0-9_]'u", $class))
		{
			E('Disallowed classname: ' . $class);
			return false;
		}

		if (class_exists($class)) return new $class;

		E('No defined driver for this convertion: ' . $class);

		return false;
	}
}
