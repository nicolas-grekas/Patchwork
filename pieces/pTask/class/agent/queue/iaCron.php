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


class extends agent
{
	const contentType = 'image/gif';


	public
	
	$get = array(
		'__1__:i:1',
		'__2__:c:[a-f0-9]{32}'
	);


	protected

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
		$sql = "SELECT OID, base FROM queue WHERE run_time AND run_time<={$time} ORDER BY run_time, OID LIMIT 1";
		if ($data = $this->sqlite->query($sql)->fetchObject()) tool_touchUrl::call("{$data->base}queue/pTask/{$data->OID}/" . $this->getToken());
	}

	protected function doOne($id)
	{
		$sqlite = $this->sqlite;

		$sql = "SELECT data FROM queue WHERE OID={$id}";
		$data = $sqlite->query($sql)->fetchObject();

		if (!$data) return;

		$sql = "UPDATE queue SET run_time=0 WHERE OID={$id}";
		$sqlite->query($sql);

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

		$sqlite->query($sql);
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

		file_exists($token) || file_put_contents($token, patchwork::uniqid());

		return trim(file_get_contents($token));
	}
}
