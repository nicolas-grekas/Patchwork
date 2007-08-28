<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends pTask_periodic
{
	static function crontab()
	{
		return array(
			'hourly'  => ' 0 * * * *',
			'daily'   => ' 1 3 * * *',
			'weekly'  => '15 4 * * 0',
			'monthly' => '30 5 1 * *',
		);
	}


	protected $lastRun;

	function __construct() {$this->lastRun = $_SERVER['REQUEST_TIME'];}

	function execute()
	{
		$now = $_SERVER['REQUEST_TIME'];
		$this->nextRun = 0;

		foreach (self::crontab() as $task => $crontab)
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
}
