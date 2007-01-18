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
	protected static $test_mode = false;

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
		if (self::$test_mode)
		{
			$data['headers']['X-Original-To'] = $data['headers']['To'];
			$data['headers']['To'] = $GLOBALS['CONFIG']['debug_email'];
		}

		$data['session'] = isset($_COOKIE['SID']) ? SESSION::getAll() : array();

		$sqlite = self::getSqlite();

		$home = sqlite_escape_string(CIA::__HOME__());
		$data = sqlite_escape_string(serialize($data));

		$time = isset($data['options']['time']) ? $data['options']['time'] : 0;
		if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

		$archive = (int) ((isset($data['options']['archive']) && $data['options']['archive']) || self::$test_mode);

		$sent = - (int)(bool) self::$test_mode;

		$sql = "INSERT INTO queue VALUES('{$home}', '{$data}', {$time}, {$archive}, {$sent})";
		$sqlite->query($sql);

		$id = $sqlite->lastInsertRowid();

		self::$is_registered || self::registerQueue();

		return $id;
	}

	static function put($function, $arguments = array(), $time = 0)
	{
		throw new Exception('iaMail::put() is disabled');
	}

	protected static $queueFolder = 'class/iaMail/queue/';
	protected static $queueUrl = 'iaMail/queue?do=1';
	protected static $queueSql = 'CREATE TABLE queue
		(
			home TEXT,
			data BLOB,
			send_time INTEGER,
			archive INTEGER,
			sent_time INTEGER
		);
		CREATE INDEX email_times ON queue (send_time, sent_time)';
}
