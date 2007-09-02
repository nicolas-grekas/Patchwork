<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends agent
{
	const contentType = 'image/gif';


	public

	$get = array(
		'__1__:i:1',
		'__2__:c:[a-f0-9]{32}'
	);


	protected

	$maxage = 3600,

	$lock,
	$queueName = 'queue',
	$queueFolder = 'data/queue/pTask/',
	$dual = 'pTask',

	$sqlite;


	static $callbackError = false;


	function control()
	{
		$sqlite = $this->dual;
		$sqlite = new $sqlite;
		$this->sqlite = $sqlite->getSqlite();

		if (isset($this->get->__1__) && $this->get->__1__)
		{
			if (isset($this->get->__2__) && $this->get->__2__ == $this->getToken())
			{
				if ($this->getLock())
				{
					ob_start(array($this, 'ob_handler'));
					$this->doOne($this->get->__1__);
					ob_end_flush();
				}

				return;
			}
			else $this->touchOne($this->get->__1__);
		}

		$this->queueNext();
	}

	function compose($o)
	{
		echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
	}

	function ob_handler($buffer)
	{
		$this->releaseLock();
		$this->queueNext();

		if ('' !== $buffer) self::$callbackError = true;

		return $buffer;
	}

	protected function queueNext()
	{
		$time = time();
		$sql = "SELECT OID, base, run_time FROM queue WHERE run_time>0 ORDER BY run_time, OID LIMIT 1";
		if ($data = $this->sqlite->query($sql)->fetchObject())
		{
			0 > $this->maxage && $this->maxage = PHP_INT_MAX;

			if ($data->run_time <= $time)
			{
				// XXX What if the URL is not valid anymore ?
				tool_touchUrl::call("{$data->base}queue/pTask/{$data->OID}/" . $this->getToken());

				$sql = "SELECT run_time FROM queue WHERE run_time>{$time} ORDER BY run_time LIMIT 1";
				if ($data = $this->sqlite->query($sql)->fetchObject()) p::setMaxage(min($this->maxage, $data->run_time - $time));
			}
			else p::setMaxage(min($this->maxage, $data->run_time - $time));
		}
	}

	protected function doOne($id)
	{
		$sqlite = $this->sqlite;

		$sql = "SELECT data FROM queue WHERE OID={$id}";
		$data = $sqlite->query($sql)->fetchObject();

		if (!$data) return;

		$sql = "UPDATE queue SET run_time=0 WHERE OID={$id}";
		$sqlite->queryExec($sql);

		$data = unserialize($data->data);

		$this->restoreSession($data['session']);

		$data['task']->run();
		$time = $data['task']->getNextRun();

		if ($time > 0)
		{
			$sql = time();
			if ($time < $sql - 366*86400) $time += $sql;

			$data['session'] = class_exists('SESSION', false) ? SESSION::getAll() : array();
			$data = sqlite_escape_string(serialize($data));

			$sql = "UPDATE queue SET run_time={$time},data='{$data}' WHERE OID={$id}";
		}
		else $sql = "DELETE FROM queue WHERE OID={$id}";

		$sqlite->queryExec($sql);
	}

	protected function touchOne($id)
	{
	}

	protected function restoreSession(&$session)
	{
		if ($session)
		{
			foreach ($session as $k => &$v) SESSION::set($k, $v);
			SESSION::regenerateId(false, false);
		}
	}

	protected function getLock()
	{
		$lock = resolvePath($this->queueFolder) . $this->queueName . '.lock';

		if (!file_exists($lock))
		{
			touch($lock);
			chmod($lock, 0666);
		}

		$this->lock = $lock = fopen($lock, 'wb');
		flock($lock, LOCK_EX+LOCK_NB, $wb) || $wb = true;

		if ($wb)
		{
			fclose($lock);
			return false;
		}

		set_time_limit(0);

		return true;
	}

	protected function releaseLock()
	{
		fclose($this->lock);
	}

	protected function getToken()
	{
		$token = resolvePath($this->queueFolder) . $this->queueName . '.token';

		file_exists($token) || file_put_contents($token, p::uniqid());

		return trim(file_get_contents($token));
	}
}
