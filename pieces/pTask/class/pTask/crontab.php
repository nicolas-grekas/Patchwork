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


class extends pTask_periodic
{
	static function crontab()
	{
		return array(
			' 0 * * * *' => 'hourly',
			' 1 3 * * *' => 'daily',
			'15 4 * * 0' => 'weekly',
			'30 5 1 * *' => 'monthly',
		);
	}


	protected $lastRun;

	function __construct() {$this->lastRun = $_SERVER['REQUEST_TIME'];}

	function execute()
	{
		$now = $_SERVER['REQUEST_TIME'];
		$this->nextRun = 0;

		foreach (self::crontab() as $crontab => $task)
		{
			$this->setCrontab($crontab);

			if (parent::getNextRun($this->lastRun) <= $now)
			{
				$task = 'pTask_' . $task;
				pTask::schedule(new pTask(array(new $task, 'run')), $now);
			}

			$task = parent::getNextRun();
			if (!$this->nextRun || $task < $this->nextRun) $this->nextRun = $task;
		}

		$this->crontab = array();
		$this->lastRun = time();
	}

	function getNextRun($time = false) {return $this->nextRun;}


	static function setup()
	{
		$file = new pTask;
		$file->setupQueue();
		$file = resolvePath($file->queueFolder) . $file->queueName . '.crontab';

		$id = file_exists($file) ? file_get_contents($file) : 0;
		$id && pTask::cancel($id);
		$id = pTask::schedule(new self);

		file_put_contents($file, $id);
	}
}
