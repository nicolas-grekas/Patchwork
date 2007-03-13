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
		'__1__:int:1',
		'__2__:string:^[a-f0-9]{32}$'
	);

	public static $callbackError = false;

	protected $lock;
	protected $queueName = 'queue';
	protected $queueFolder = 'data/queue/iaCron/';
	protected $dual = 'iaCron';

	protected $sqlite;

	function control()
	{
		$sqlite = $this->dual;
		$sqlite = new $sqlite;
		$this->sqlite = $sqlite->getSqlite();

		if (isset($this->argv->__1__) && $this->argv->__1__)
		{
			if (isset($this->argv->__2__) && $this->argv->__2__ == $this->getToken())
			{
				if ($this->getLock())
				{
					ob_start(array($this, 'ob_handler'));
					$this->doOne($this->argv->__1__);
					ob_end_clean();
				}

				return;
			}
			else $this->touchOne($this->argv->__1__);
		}

		$this->queueNext();
	}

	function ob_handler($buffer)
	{
		$this->releaseLock();
		$this->queueNext();

		if ('' !== $buffer)
		{
			self::$callbackError = true;

			if ('' !== $buffer) iaMail_mime::send(
				array('To' => $GLOBALS['CONFIG']['debug_email']),
				$buffer
			);
		}

		return '';
	}

	protected function queueNext()
	{
		$time = time();
		$sql = "SELECT OID, home FROM queue WHERE run_time AND run_time<={$time} ORDER BY run_time, OID LIMIT 1";
		if ($data = $this->sqlite->query($sql)->fetchObject()) tool_touchUrl::call("{$data->home}queue/iaCron/{$data->OID}/" . $this->getToken());
	}

	protected function doOne($id)
	{
		$sqlite = $this->sqlite;

		$sql = "SELECT data FROM queue WHERE OID={$id}";
		$data = $sqlite->query($sql)->fetchObject();

		if (!$data) return;

		$sql = "UPDATE queue SET run_time=0 WHERE OID={$id}";
		$sqlite->query($sql);

		$data = (object) unserialize($data->data);

		$this->restoreSession($data->session);

		$time = call_user_func_array($data->function, $data->arguments);

		if ($time > 0)
		{
			$data = time();

			if ($time < $data - 366*86400) $time += $data;

			$sql = "UPDATE queue SET run_time={$time} WHERE OID={$id}";
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
		flock($lock, LOCK_EX+LOCK_NB, $wb);

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

		file_exists($token) || file_put_contents($token, CIA::uniqid());

		return trim(file_get_contents($token));
	}
}
