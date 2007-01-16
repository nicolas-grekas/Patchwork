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


class extends agent_bin
{
	public $argv = array(
		'do:bool',
		'__1__:int:1',
		'__2__:string:^[a-z0-9]{32}$'
	);

	protected $lock;
	protected $queueName = 'queue';
	protected $queueFolder = 'class/iaCron/queue/';
	protected $getSqlite = array('iaCron', 'getSqlite');

	protected $sqlite;

	function control()
	{
		$this->sqlite = call_user_func($this->getSqlite);

		if (isset($this->argv->__1__) && $this->argv->__1__)
		{
			if (isset($this->argv->__2__) && $this->argv->__2__)
			{
				if ($this->argv->__2__ != $this->getToken()) return;

				$this->doOne((int) $this->argv->__1__);
			}
			else $this->checkOne((int) $this->argv->__1__);
		}
		else if (isset($this->argv->do) && !$this->argv->do) $this->doDaemon();
		else
		{
			if (!$this->getLock()) return;
			$this->doQueue();
			$this->releaseLock();
		}
	}

	function doDaemon()
	{
		$sql = "SELECT 1 FROM queue WHERE run_time <= {$_SERVER['REQUEST_TIME']} LIMIT 1";
		!$this->sqlite->query($sql)->fetchObject() || iaCron::isRunning() || tool_touchUrl::call(CIA::home('iaCron/queue?do=1'));
	}

	function doQueue()
	{
		$token = $this->getToken();

		require_once 'HTTP/Request.php';

		do
		{
			$time = time();
			$sql = "SELECT OID, home FROM queue WHERE run_time <= {$time} ORDER BY run_time, OID LIMIT 1";
			$result = $this->sqlite->query($sql);
	
			if ($data = $result->fetchObject())
			{
				$data = new HTTP_Request("{$data->home}iaCron/queue/{$data->OID}/{$token}");
				$data->sendRequest();
			}
			else break;
		}
	}

	function doOne($id)
	{
		$sql = "SELECT data FROM queue WHERE OID={$id}";

		if ($data = $this->sqlite->query($sql)->fetchObject())
		{
			$data = (object) unserialize($data->data);

			$this->restoreSession($data->session);

			$time = call_user_func_array($data->function, $data->arguments);

			if ($time > 0)
			{
				$time += time();
				$sql = "UPDATE queue SET run_time={$time} WHERE OID={$id}";
			}
			else $sql = "DELETE FROM queue WHERE OID={$id}";

			$this->sqlite->query($sql);
		}
	}

	function touchOne($id)
	{
	}

	function restoreSession(&$session)
	{
		if (class_exists('SESSION', false))
		{
			$_SESSION = $session;
			return;
		}

		final class SESSION extends SESSION__0
		{
			static function setDATA($data) {self::$DATA = $data;}
			static function regenerateId($initSession = false) {if ($initSession) self::$DATA = array();}
			protected static function start() {self::$lastseen = $_SERVER['REQUEST_TIME'];}
		}

		SESSION::setDATA($session);
	}

	function getLock()
	{
		$lock = resolvePath($this->queueFolder) . $this->queueName . '.lock';

		if (!file_exists($lock))
		{
			touch($lock);
			chmod($lock, 0666);
		}

		$this->lock = $lock = fopen($lock, 'wb');
		flock($lock, LOCK_EX+LOCK_NB, $wb);

		if ($wb)
		{
			fclose($lock);
			return false;
		}

		set_time_limit(0);

		return true;
	}

	function releaseLock()
	{
		fclose($this->lock);
	}

	function getToken()
	{
		$token = resolvePath($this->queueFolder) . $this->queueName . '.token';

		file_exists($token) || file_put_contents($token, CIA::uniqid());

		return trim(file_get_contents($token));
	}
}
