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

	function control()
	{
		$sqlite = iaMail::getSqlite();

		if ($this->argv->__1__)
		{
			if ($this->argv->__2__ != $this->getToken()) return;

			$id = (int) $this->argv->__1__;
			$sql = "SELECT archive, data FROM queue WHERE OID={$id}";

			if ($data = $sqlite->query($sql)->fetchObject())
			{
				$archive = $data->archive;
				$data = (object) unserialize($data->data);

				if ($data->session)
				{
					class iaMail_SESSION_ extends SESSION__0
					{
						static function setDATA($data) {self::$DATA = $data;}
						static function regenerateId($initSession = false) {if ($initSession) self::$DATA = array();}
						protected static function start() {self::$lastseen = $_SERVER['REQUEST_TIME'];}
					}

					eval('class SESSION extends iaMail_SESSION_ {}');
					SESSION::setDATA($data->session);
				}

				isset($data->agent)
					? iaMail_mime::sendAgent($data->headers, $data->agent, $data->argv, $data->options)
					: iaMail_mime::send($data->headers, $data->body, $data->options);

				$time = time();
				$sql = $archive
					? "UPDATE queue SET sent_time={$time} WHERE OID={$id}"
					: "DELETE FROM queue WHERE OID={$id}";
				$sqlite->query($sql);
			}
		}
		else if (!$this->argv->do)
		{
			$sql = "SELECT 1 FROM queue WHERE sent_time=0 AND send_time <= {$_SERVER['REQUEST_TIME']} LIMIT 1";
			!$sqlite->query($sql)->fetchObject() || iaMail::isRunning() || tool_touchUrl::call(CIA::home('iaMail/queue?do=1'));
		}
		else
		{
			if (!$this->getLock()) return;

			$token = $this->getToken();

			require_once 'HTTP/Request.php';

			do
			{
				$time = time();
				$sql = "SELECT OID, home FROM queue WHERE sent_time=0 AND send_time <= {$time} LIMIT 1";
				$result = $sqlite->query($sql);

				if ($data = $result->fetchObject())
				{
					$data = new HTTP_Request("{$data->home}iaMail/queue/{$data->OID}/{$token}");
					$data->sendRequest();
					}
				else break;
			}

			$this->releaseLock();
		}
	}

	function getLock()
	{
		$lock = resolvePath('class/iaMail/queue/') . 'lock';

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
		$token = resolvePath('class/iaMail/queue/') . 'token';

		file_exists($token) || file_put_contents($token, CIA::uniqid());

		return trim(file_get_contents($token));
	}
}
