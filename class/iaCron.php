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
		$queue = new iaCron;
		$sqlite = $queue->getSqlite();

		if (!is_array($arguments)) $arguments = array($arguments);

		if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

		$home = sqlite_escape_string(CIA::__HOME__());
		$data = array(
			'function' => &$function,
			'arguments' => &$arguments,
			'session' => isset($_COOKIE['SID']) ? SESSION::getAll() : array()
		);
		$data = sqlite_escape_string(serialize($data));

		$sql = "INSERT INTO queue VALUES('{$home}','{$data}',{$time})";
		$sqlite->query($sql);

		$id = $sqlite->lastInsertRowid();

		$queue->is_registered || $queue->registerQueue();

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
	protected $queueFolder = 'class/iaCron/queue/';
	protected $queueUrl = 'iaCron/queue?do=1';
	protected $queueSql = 'CREATE TABLE queue
		(
			home TEXT,
			data BLOB,
			run_time INTEGER
		);
		CREATE INDEX run_time ON queue (run_time)';


	// The following functions should not be used directly

	protected static $sqlite = array();
	protected $is_registered = false;

	function registerQueue()
	{
		register_shutdown_function(array($this, 'doQueue'));
		$this->is_registered = true;
	}

	function doQueue()
	{
		$this->isRunning() || tool_touchUrl::call($this->queueUrl);
	}

	function isRunning()
	{
		$lock = resolvePath($this->queueFolder) . $this->queueName . '.lock';

		if (!file_exists($lock)) return false;

		$lock = fopen($lock, 'wb');
		flock($lock, LOCK_EX+LOCK_NB, $type);
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
