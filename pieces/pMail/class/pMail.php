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


class extends pTask
{
	protected $testMode = false;

	static function send($headers, $body, $options = null)
	{
		is_array($headers) || $headers = (array) $headers;

		return self::pushMail(array(
			'headers' => &$headers,
			'body' => &$body,
			'options' => &$options,
		));
	}

	static function sendAgent($headers, $agent, $args = array(), $options = null)
	{
		is_array($headers) || $headers = (array) $headers;
		is_array($args)    || $args    = (array) $args;		

		return self::pushMail(array(
			'headers' => &$headers,
			'agent' => &$agent,
			'args' => &$args,
			'options' => &$options,
		));
	}

	protected static function pushMail($data, $queue = false)
	{
		$queue || $queue = new self;

		if ($queue->testMode)
		{
			$data['headers']['X-Original-To'] = $data['headers']['To'];
			$data['headers']['To'] = $CONFIG['pMail.debug_email'];
		}

		if (!isset($data['headers']['From']) && $CONFIG['pMail.from']) $data['headers']['From'] = $CONFIG['pMail.from'];
		if (isset($data['headers']['From']) && !$data['headers']['From']) W("Email is likely not to be sent: From header is empty.");

		$data['session'] = isset($_COOKIE['SID']) ? SESSION::getAll() : array();


		$sqlite = $queue->getSqlite();

		$sent = - (int)(bool) $queue->testMode;
		$archive = (int) (!empty($data['options']['archive']) || $queue->testMode);

		$time = isset($data['options']['time']) ? $data['options']['time'] : 0;
		if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

		$base = sqlite_escape_string(p::__BASE__());
		$data = sqlite_escape_string(serialize($data));

		$sql = "INSERT INTO queue (base, data, send_time, archive, sent_time)
				VALUES('{$base}','{$data}',{$time},{$archive},{$sent})";
		$sqlite->queryExec($sql);

		$id = $sqlite->lastInsertRowid();

		$queue->registerQueue();

		return $id;
	}


	protected function doSchedule($time)
	{
		throw new Exception(get_class($this) . '::schedule() is disabled');
	}

	protected function getQueueDefinition()
	{
		return (object) array(
			'name'   => 'queue',
			'folder' => 'data/queue/pMail/',
			'url'    => 'queue/pMail',
			'sql'    => <<<EOSQL
				CREATE TABLE queue (base TEXT, data BLOB, send_time INTEGER, archive INTEGER, sent_time INTEGER);
				CREATE INDEX send_time ON queue (send_time);
				CREATE INDEX sent_time ON queue (sent_time);
				CREATE VIEW waiting AS SELECT * FROM queue WHERE send_time>0 AND sent_time=0;
				CREATE VIEW error   AS SELECT * FROM queue WHERE send_time=0;
				CREATE VIEW archive AS SELECT * FROM queue WHERE sent_time>0;
EOSQL
		);
	}
}
