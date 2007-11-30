<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class
{
	protected

	$callback,
	$arguments,
	$nextRun = 0;


	function __construct($callback = false, $arguments = array())
	{
		is_array($arguments) || $arguments = array($arguments);

		$this->callback  = $callback;
		$this->arguments = $arguments;
	}

	function run($time = false)
	{
		if ($time) self::schedule($this, $time);
		else $this->callback ? call_user_func_array($this->callback, $this->arguments) : $this->execute();

		return $this;
	}

	function execute()
	{
	}

	function getNextRun()
	{
		return $this->nextRun;
	}


	static function schedule(self $task, $time = 0)
	{
		$queue = new self;
		$sqlite = $queue->getSqlite();

		if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

		$base = sqlite_escape_string(p::__BASE__());
		$data = array(
			'task' => $task,
			'session' => class_exists('SESSION', false) ? SESSION::getAll() : array()
		);
		$data = sqlite_escape_string(serialize($data));

		$sql = "INSERT INTO queue VALUES('{$base}','{$data}',{$time})";
		$sqlite->queryExec($sql);

		$id = $sqlite->lastInsertRowid();

		self::$is_registered || $queue->registerQueue();

		return $id;
	}

	static function cancel($id)
	{
		$queue = new self;
		$id = (int) $id;
		$sql = "DELETE FROM queue WHERE OID={$id}";
		$queue->getSqlite()->queryExec($sql);
	}


	// The following functions should not be used directly

	protected function defineQueue()
	{
		$this->queueName = 'queue';
		$this->queueFolder = 'data/queue/pTask/';
		$this->queueUrl = 'queue/pTask';
		$this->queueSql = '
			CREATE TABLE queue (base TEXT, data BLOB, run_time INTEGER);
			CREATE INDEX run_time ON queue (run_time);
			CREATE VIEW waiting AS SELECT * FROM queue WHERE run_time>0;
			CREATE VIEW error   AS SELECT * FROM queue WHERE run_time=0;

			CREATE TABLE registry (task_id INTEGER, task_name TEXT, level INTEGER, zcache TEXT);
			CREATE INDEX task_id ON registry registry (task_id);

			CREATE TRIGGER DELETE ON queue
			BEGIN
				DELETE FROM registry WHERE task_id=OLD.OID;
			END;

			CREATE TRIGGER DELETE ON registry
			BEGIN
				DELETE FROM queue WHERE OID=OLD.task_id
			END;';
	}


	protected static

	$sqlite = array(),
	$is_registered = false;


	function preSerialize()
	{
		unset($this->queueName, $this->queueFolder, $this->queueUrl, $this->queueSql);
		return $this;
	}

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
		$this->isRunning() || tool_url::touch($this->queueUrl);
	}

	protected function isRunning()
	{
		$lock = resolvePath($this->queueFolder) . $this->queueName . '.lock';

		if (!file_exists($lock)) return false;

		$lock = fopen($lock, 'wb');
		flock($lock, LOCK_EX+LOCK_NB, $type) || $type = true;
		fclose($lock);

		return $type;
	}

	function getSqlite()
	{
		$this->defineQueue();

		$sqlite =& self::$sqlite[get_class($this)];
		if ($sqlite) return $sqlite;

		$sqlite = resolvePath($this->queueFolder) . $this->queueName . '.sqlite';

		if (file_exists($sqlite)) $sqlite = new SQLiteDatabase($sqlite);
		else
		{
			$sqlite = new SQLiteDatabase($sqlite);
			@$sqlite->queryExec($this->queueSql);
		}

		return $sqlite;
	}
}
