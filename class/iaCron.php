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
	static function put($function, $arguments = array(), $delay = 0)
	{
		$sqlite = self::getSqlite();

		$home = sqlite_escape_string(CIA::home('', true));
		$data = array(
			'function' => &$function,
			'arguments' => &$arguments,
			'session' => isset($_COOKIE['SID']) ? SESSION::getAll() : array()
		);

		$data = sqlite_escape_string(serialize($data));
		$delay = time() + $delay;

		$sql = "INSERT INTO queue VALUES('{$home}', '{$data}', {$delay})";
		$sqlite->query($sql);

		$id = $sqlite->lastInsertRowid();

		self::isRunning() || tool_touchUrl::call(CIA::home('iaCron/queue?do=1'));

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
		$lock = resolvePath('class/iaCron/queue/') . 'lock';

		if (!file_exists($lock)) return false;

		$lock = fopen($lock, 'wb');
		flock($lock, LOCK_EX+LOCK_NB, $type);
		fclose($lock);

		return $type;
	}

	static function getSqlite()
	{
		if (isset(self::$sqlite)) return self::$sqlite;

		$sqlite = resolvePath('class/iaCron/queue/') . 'queue.sqlite';

		if (file_exists($sqlite)) $sqlite = new SQLiteDatabase($sqlite);
		else
		{
			$sqlite = new SQLiteDatabase($sqlite);
			@$sqlite->query('CREATE TABLE queue (
				home TEXT,
				data BLOB,
				run_time INTEGER
			);
			CREATE INDEX run_time ON queue (run_time)');
		}

		return self::$sqlite = $sqlite;
	}
}
