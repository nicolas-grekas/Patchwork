<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

use Patchwork as p;
use SESSION   as s;

class pTask
{
    protected

    $callback = false,
    $arguments = array(),
    $nextRun = 0;


    protected static

    $staticRegistry = array();


    function __construct($callback = false, $arguments = array())
    {
        is_array($arguments) || $arguments = array($arguments);

        $this->callback  = $callback;
        $this->arguments = $arguments;
    }

    function run($time = false)
    {
        if ($time) self::schedule($this, $time);
        else $this->callback ? call_user_func_array($this->callback, $this->arguments) : $this->execute();

        return $this;
    }

    function execute()
    {
    }

    function getNextRun()
    {
        return $this->nextRun;
    }


    final static function schedule(self $task, $time = 0)
    {
        return $task->doSchedule($time);
    }

    static function cancel($id, $sqlite = false)
    {
        $sqlite || ($sqlite = new self) && $sqlite = $sqlite->getSqlite();

        $id = (int) $id;
        $sql = "DELETE FROM queue WHERE OID={$id}";
        $sqlite->queryExec($sql);
    }


    protected function doSchedule($time)
    {
        $sqlite = $this->getSqlite();

        if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

        $base = sqlite_escape_string(p::__BASE__());
        $data = array(
            'task' => $this,
            'cookie' => &$_COOKIE,
            'session' => class_exists('SESSION', false) ? s::getAll() : array()
        );
        $data = sqlite_escape_string(serialize($data));

        $sql = "INSERT INTO queue (base, data, run_time)
                VALUES('{$base}','{$data}',{$time})";
        $sqlite->queryExec($sql);

        $id = $sqlite->lastInsertRowid();

        $this->registerQueue();

        return $id;
    }

    protected function getQueueDefinition()
    {
        return (object) array(
            'name'   => 'queue',
            'folder' => 'data/queue/pTask/',
            'url'    => 'queue/pTask',
            'sql'    => <<<EOSQL
                CREATE TABLE queue (base TEXT, data BLOB, run_time INTEGER);
                CREATE INDEX run_time ON queue (run_time);
                CREATE VIEW waiting AS SELECT * FROM queue WHERE run_time>0;
                CREATE VIEW error   AS SELECT * FROM queue WHERE run_time=0;

                CREATE TABLE registry (task_id INTEGER, task_name TEXT, level INTEGER, zcache TEXT);
                CREATE INDEX task_id ON registry registry (task_id);

                CREATE TRIGGER sync_clean_registry DELETE ON queue
                BEGIN
                    DELETE FROM registry WHERE task_id=OLD.OID;
                END;

                CREATE TRIGGER sync_clean_queue DELETE ON registry
                BEGIN
                    DELETE FROM queue WHERE OID=OLD.task_id
                END;
EOSQL
        );
    }

    protected function registerQueue()
    {
        $is_registered =& $this->getStatic('isRegistered');

        if (!$is_registered)
        {
            register_shutdown_function(array($this, 'startQueue'));
            $is_registered = true;
        }
    }

    function startQueue($q = false)
    {
        $q || $q = $this->getQueueDefinition();
        $this->isRunning($q) || tool_url::touch($q->url);
    }

    protected function isRunning($q)
    {
        $lock = patchworkPath($q->folder) . $q->name . '.lock';

        if (!file_exists($lock)) return false;

        $lock = fopen($lock, 'wb');
        flock($lock, LOCK_EX+LOCK_NB, $type) || $type = true;
        fclose($lock);

        return $type;
    }

    protected function &getStatic($key)
    {
        $c = get_class($this);

        isset(self::$staticRegistry[$c]) || self::$staticRegistry[$c] = array();

        return self::$staticRegistry[$c][$key];
    }

    function getSqlite()
    {
        $sqlite =& $this->getStatic('sqlite');

        if ($sqlite) return $sqlite;

        $q = $this->getQueueDefinition();
        $sqlite = patchworkPath($q->folder) . $q->name . '.sqlite';

        if (file_exists($sqlite)) $sqlite = new SQLiteDatabase($sqlite);
        else
        {
            $sqlite = new SQLiteDatabase($sqlite);
            @$sqlite->queryExec($q->sql);
        }

        $sqlite->def = $q;

        return $sqlite;
    }
}
