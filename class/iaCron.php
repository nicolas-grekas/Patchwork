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
	static function put($time, $function, $arguments = array())
	{
		$sqlite = self::getSqlite();

		if (!is_array($arguments)) $arguments = array($arguments);

		$home = sqlite_escape_string(CIA::__HOME__());
		$data = array(
			'function' => &$function,
			'arguments' => &$arguments,
			'session' => isset($_COOKIE['SID']) ? SESSION::getAll() : array()
		);

		$data = sqlite_escape_string(serialize($data));

		if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

		$sql = "INSERT INTO queue VALUES('{$home}', '{$data}', {$time})";
		$sqlite->query($sql);

		$id = $sqlite->lastInsertRowid();

		self::$is_registered || self::registerQueue();

		return $id;
	}

	static function pop($id)
	{
		$id = (int) $id;
		$sql = "DELETE FROM queue WHERE OID={$id}";
		self::getSqlite()->query($sql);
	}


	protected static $queueName = 'queue';
	protected static $queueFolder = 'class/iaCron/queue/';
	protected static $queueUrl = 'iaCron/queue?do=1';
	protected static $queueSql = 'CREATE TABLE queue
		(
			home TEXT,
			data BLOB,
			run_time INTEGER
		);
		CREATE INDEX run_time ON queue (run_time)';


	// The following functions should not be used directly

	protected static $sqlite;
	protected static $is_registered = false;

	static function registerQueue()
	{
		register_shutdown_function(array(__CLASS__, 'doQueue'));
		self::$is_registered = true;
	}

	static function doQueue()
	{
		self::isRunning() || tool_touchUrl::call(self::$queueUrl);
	}

	static function isRunning()
	{
		$lock = resolvePath(self::$queueFolder) . self::$queueName . '.lock';

		if (!file_exists($lock)) return false;

		$lock = fopen($lock, 'wb');
		flock($lock, LOCK_EX+LOCK_NB, $type);
		fclose($lock);

		return $type;
	}

	static function getSqlite()
	{
		if (isset(self::$sqlite)) return self::$sqlite;

		$sqlite = resolvePath(self::$queueFolder) . self::$queueName . '.sqlite';

		if (file_exists($sqlite)) $sqlite = new SQLiteDatabase($sqlite);
		else
		{
			$sqlite = new SQLiteDatabase($sqlite);
			@$sqlite->query(self::$queueSql);
		}

		return self::$sqlite = $sqlite;
	}
}
