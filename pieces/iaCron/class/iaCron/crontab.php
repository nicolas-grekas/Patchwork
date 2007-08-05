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


class extends iaCron_periodic
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


	protected $lastRun = 1;

	function execute()
	{
		$now = $_SERVER['REQUEST_TIME'];
		$this->nextRun = 0;

		foreach (self::crontab() as $crontab => $task)
		{
			$this->setCrontab($crontab);

			if (parent::getNextRun($this->lastRun) <= $now)
			{
				$task = 'iaCron_' . $task;
				iaCron::schedule(new iaCron(array(new $task, 'run')), $now);
				$task = parent::getNextRun();
				if (!$this->nextRun || $task < $this->nextRun) $this->nextRun = $task;
			}
		}

		$this->crontab = array();
		$this->lastRun = time();
	}

	function getNextRun() {return $this->nextRun;}
}
