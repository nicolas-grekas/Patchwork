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
	static function getCrontab()
	{
		return array(
#			'name_of_my_custom_task'  => new pTask_my_periodic_task(/*...*/),

#			'name_of_my_hourly__task' => new pTask_periodic(' 0 * * * *', /*my_hourly__callback*/ /*[, callback_arguments_array]*/),
#			'name_of_my_daily___task' => new pTask_periodic(' 1 3 * * *', /*my_daily___callback*/ /*[, callback_arguments_array]*/),
#			'name_of_my_weekly__task' => new pTask_periodic('15 4 * * 0', /*my_weekly__callback*/ /*[, callback_arguments_array]*/),
#			'name_of_my_monthly_task' => new pTask_periodic('30 5 1 * *', /*my_monthly_callback*/ /*[, callback_arguments_array]*/),
		);
	}


	static function setup()
	{
		$crontab = self::getCrontab();
		$zcache = sqlite_escape_string(PATCHWORK_ZCACHE);


		$sqlite = new pTask;
		$sqlite = $sqlite->getSqlite();

		$sql = "DELETE FROM registry WHERE level=" . PATCHWORK_PATH_LEVEL . " AND zcache='{$zcache}'";
		$sqlite->queryExec($sql);

		$noname = false;
		$sqlite->queryExec('BEGIN');
		foreach ($crontab as $name => $task)
		{
			if (is_int($name))
			{
				$noname || W('Every periodic task in the crontab should have a unique name.');
				$noname = true;

				$name = array();
				while (false !== $sql = get_parent_class($task)) $name[] = $sql;
				$name = md5(serialize(array($task, $name)));
			}

			$name = sqlite_escape_string($name);
			$sql = "DELETE FROM registry WHERE name='{$name}' AND level>=" . PATCHWORK_PATH_LEVEL . " AND zcache='{$zcache}'";
			$sqlite->queryExec($sql);

			$id = pTask::schedule($task, $task->getNextRun());
			$sql = "INSERT INTO registry VALUES ({$id}, '{$name}', " . PATCHWORK_PATH_LEVEL . ", '{$zcache}')";
			$sqlite->queryExec($sql);
		}
		$sqlite->queryExec('COMMIT');
	}
}
