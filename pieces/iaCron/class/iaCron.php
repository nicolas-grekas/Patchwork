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
	static function push($time, $function, $arguments = array())
	{
		$queue = new iaCron;
		$sqlite = $queue->getSqlite();

		if (!is_array($arguments)) $arguments = array($arguments);

		if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

		$base = sqlite_escape_string(CIA::__BASE__());
		$data = array(
			'function' => &$function,
			'arguments' => &$arguments,
			'session' => class_exists('SESSION', false) ? SESSION::getAll() : array()
		);
		$data = sqlite_escape_string(serialize($data));

		$sql = "INSERT INTO queue VALUES('{$base}','{$data}',{$time})";
		$sqlite->query($sql);

		$id = $sqlite->lastInsertRowid();

		self::$is_registered || $queue->registerQueue();

		return $id;
	}

	static function pop($id)
	{
		$queue = new iaCron;
		$id = (int) $id;
		$sql = "DELETE FROM queue WHERE OID={$id}";
		$queue->getSqlite()->query($sql);
	}


	protected $queueName = 'queue';
	protected $queueFolder = 'data/queue/iaCron/';
	protected $queueUrl = 'queue/iaCron';
	protected $queueSql = '
		CREATE TABLE queue (base TEXT, data BLOB, run_time INTEGER);
		CREATE INDEX run_time ON queue (run_time);
		CREATE VIEW waiting AS SELECT * FROM queue WHERE run_time>0;
		CREATE VIEW error   AS SELECT * FROM queue WHERE run_time=0;';


	// The following functions should not be used directly

	protected static $sqlite = array();
	protected static $is_registered = false;

	protected function registerQueue()
	{
		if (!self::$is_registered)
		{
			register_shutdown_function(array($this, 'startQueue'));
			self::$is_registered = true;
		}
	}

	function startQueue()
	{
		$this->isRunning() || tool_touchUrl::call($this->queueUrl);
	}

	protected function isRunning()
	{
		$lock = resolvePath($this->queueFolder) . $this->queueName . '.lock';

		if (!file_exists($lock)) return false;

		$lock = fopen($lock, 'wb');
		flock($lock, LOCK_EX+LOCK_NB, $type) || $type = CIA_WINDOWS;
		fclose($lock);

		return $type;
	}

	function getSqlite()
	{
		$sqlite =& self::$sqlite[get_class($this)];
		if ($sqlite) return $sqlite;

		$sqlite = resolvePath($this->queueFolder) . $this->queueName . '.sqlite';

		if (file_exists($sqlite)) $sqlite = new SQLiteDatabase($sqlite);
		else
		{
			$sqlite = new SQLiteDatabase($sqlite);
			@$sqlite->query($this->queueSql);
		}

		return $sqlite;
	}
}
