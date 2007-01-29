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


class extends iaCron
{
	protected $test_mode = false;

	static function send($headers, $body, $options = null)
	{
		return self::putMail(array(
			'headers' => &$headers,
			'body' => &$body,
			'options' => &$options,
		));
	}

	static function sendAgent($headers, $agent, $argv = array(), $options = null)
	{
		return self::putMail(array(
			'headers' => &$headers,
			'agent' => &$agent,
			'argv' => &$argv,
			'options' => &$options,
		));
	}

	protected static function putMail($data)
	{
		$queue = new iaMail;

		if ($queue->test_mode)
		{
			$data['headers']['X-Original-To'] = $data['headers']['To'];
			$data['headers']['To'] = $GLOBALS['CONFIG']['debug_email'];
		}

		$data['session'] = isset($_COOKIE['SID']) ? SESSION::getAll() : array();


		$sqlite = $queue->getSqlite();

		$sent = - (int)(bool) $queue->test_mode;
		$archive = (int) ((isset($data['options']['archive']) && $data['options']['archive']) || $queue->test_mode);

		$time = isset($data['options']['time']) ? $data['options']['time'] : 0;
		if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

		$home = sqlite_escape_string(CIA::__HOME__());
		$data = sqlite_escape_string(serialize($data));

		$sql = "INSERT INTO queue VALUES('{$home}','{$data}',{$time},{$archive},{$sent})";
		$sqlite->query($sql);

		$id = $sqlite->lastInsertRowid();

		$queue->is_registered || $queue->registerQueue();

		return $id;
	}

	static function put($time, $function, $arguments = array())
	{
		throw new Exception(__CLASS__ . '::put() is disabled');
	}

	protected $queueFolder = 'class/iaMail/queue/';
	protected $queueUrl = 'iaMail/queue?do=1';
	protected $queueSql = 'CREATE TABLE queue
		(
			home TEXT,
			data BLOB,
			send_time INTEGER,
			archive INTEGER,
			sent_time INTEGER
		);
		CREATE INDEX email_times ON queue (send_time, sent_time)';
}
