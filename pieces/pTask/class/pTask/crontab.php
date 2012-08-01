<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pTask_crontab extends pTask
{
    static function getCrontab()
    {
        return array(
#           'name_of_my_custom_task'  => new pTask_my_periodic_task(/*...*/),

#           'name_of_my_hourly__task' => new pTask_periodic(' 0 * * * *', /*my_hourly__callback*/ /*[, callback_arguments_array]*/),
#           'name_of_my_daily___task' => new pTask_periodic(' 1 3 * * *', /*my_daily___callback*/ /*[, callback_arguments_array]*/),
#           'name_of_my_weekly__task' => new pTask_periodic('15 4 * * 0', /*my_weekly__callback*/ /*[, callback_arguments_array]*/),
#           'name_of_my_monthly_task' => new pTask_periodic('30 5 1 * *', /*my_monthly_callback*/ /*[, callback_arguments_array]*/),
        );
    }


    static function setup()
    {
        $db = new pTask;
        $db = $db->getPdoConnection();

        $zcache = $db->quote(PATCHWORK_ZCACHE);

        $sql = "DELETE FROM registry WHERE level=" . PATCHWORK_PATH_LEVEL . " AND zcache={$zcache}";
        $db->exec($sql);

        foreach (self::getCrontab() as $name => $task)
        {
            if (is_int($name))
            {
                $name = array();
                $sql = get_class($task);
                while (false !== $sql = get_parent_class($sql)) $name[] = $sql;
                $name = md5(serialize(array($task, $name)));
            }

            $name = $db->quote($name);
            $sql = "DELETE FROM registry WHERE task_name={$name} AND level>=" . PATCHWORK_PATH_LEVEL . " AND zcache={$zcache}";
            $db->exec($sql);

            $id = pTask::schedule($task, $task->getNextRun());
            $sql = "INSERT INTO registry VALUES ({$id}, {$name}, " . PATCHWORK_PATH_LEVEL . ", {$zcache})";
            $db->exec($sql);
        }
    }
}
