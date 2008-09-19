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
	protected $testMode = DEBUG;

	static function send($headers, $body, $options = null)
	{
		is_array($headers) || $headers = (array) $headers;

		return self::pushMail(array(
			'headers' => &$headers,
			'options' => &$options,
			'body' => &$body,
		));
	}

	static function sendAgent($headers, $agent, $args = array(), $options = null)
	{
		is_array($headers) || $headers = (array) $headers;
		is_array($args)    || $args    = (array) $args;		

		return self::pushMail(array(
			'headers' => &$headers,
			'options' => &$options,
			'agent' => &$agent,
			'args' => &$args,
		));
	}

	protected static function pushMail($data, $queue = false)
	{
		$queue || $queue = new self;

		if ($queue->testMode)
		{
			if (isset($data['agent']))
			{
				if (!empty($data['options']['lang']))
				{
					$lang = p::__LANG__();
					p::setLang($data['options']['lang']);
				}

				$url = p::base($data['agent'], true);
				empty($data['args']) || $url .= '?' . http_build_query($data['args']);

				if (!empty($data['options']['lang']))
				{
					p::setLang($lang);
				}

				p::log('Sending email &lt;<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($data['agent']) . '</a>&gt;');
			}
			else p::log('Sending email');

			E($data);

			if (empty($CONFIG['pMail.debug_email'])) return 0;

			$headers =& $data['headers'];
			Mail_mime::cleanHeaders($headers, 'From|To|Cc|Bcc');

			$headers['X-Original-To'] = $headers['To'];
			isset($headers[ 'Cc']) && $headers['X-Original-Cc' ] = $headers[ 'Cc'];
			isset($headers['Bcc']) && $headers['X-Original-Bcc'] = $headers['Bcc'];

			$headers['To'] = $CONFIG['pMail.debug_email'];
			unset($headers[ 'Cc'], $headers[ 'Bcc']);
		}

		$data['cookie']  =& $_COOKIE;
		$data['session'] = class_exists('SESSION', false) ? SESSION::getAll() : array();


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
