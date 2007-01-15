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
	protected static $is_enabled = true;

	static function send($headers, $body, $options = null)
	{
		return self::sendPut(array(
			'headers' => &$headers,
			'body' => &$body,
			'options' => &$options,
		));
	}

	static function sendAgent($headers, $agent, $argv = array(), $options = null)
	{
		return self::sendPut(array(
			'headers' => &$headers,
			'agent' => &$agent,
			'argv' => &$argv,
			'options' => &$options,
		));
	}

	protected static function sendPut($data)
	{
#>		$data['headers']['X-Original-To'] = $data['headers']['To'];
#>		$data['headers']['To'] = $GLOBALS['CONFIG']['debug_email'];

		$data['session'] = isset($_COOKIE['SID']) ? SESSION::getAll() : array();

		return self::put(
			$data,
			isset($data['options']['delay']) ? $data['options']['delay'] : 0,
			isset($data['options']['archive']) && $data['options']['archive']
		);
	}

	static function put($data, $delay = 0, $archive = false)
	{
		$sqlite = self::getSqlite();

		$home = sqlite_escape_string(CIA::home('', true));
		$data = sqlite_escape_string(serialize($data));
		$delay = time() + $delay;
		$archive = (int)(bool) $archive;

		$sent = - (int)(bool) !self::$is_enabled;

		$sql = "INSERT INTO queue VALUES('{$home}', '{$data}', {$delay}, {$archive}, {$sent})";
		$sqlite->query($sql);

		$id = $sqlite->lastInsertRowid();

		self::isRunning() || tool_touchUrl::call(CIA::home('iaMail/queue?do=1'));

		return $id;
	}

	static function pop($id)
	{
		$id = (int) $id;
		$sql = "DELETE FROM queue WHERE OID={$id}";
		self::getSqlite()->query($sql);
	}

	protected static $sqlite;

	static function isRunning()
	{
		$lock = resolvePath('class/iaMail/queue/') . 'lock';

		if (!file_exists($lock)) return false;

		$lock = fopen($lock, 'wb');
		flock($lock, LOCK_EX+LOCK_NB, $type);
		fclose($lock);

		return $type;
	}

	static function getSqlite()
	{
		if (isset(self::$sqlite)) return self::$sqlite;

		$sqlite = resolvePath('class/iaMail/queue/') . 'queue.sqlite';

		if (file_exists($sqlite)) $sqlite = new SQLiteDatabase($sqlite);
		else
		{
			$sqlite = new SQLiteDatabase($sqlite);
			@$sqlite->query('CREATE TABLE queue (
				home TEXT,
				data BLOB,
				send_time INTEGER,
				archive INTEGER,
				sent_time INTEGER
			);
			CREATE INDEX email_times ON queue (send_time, sent_time)');
		}

		return self::$sqlite = $sqlite;
	}
}
