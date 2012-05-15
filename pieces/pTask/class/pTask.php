<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
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

    static function cancel($id, $db = false)
    {
        $db || ($db = new self) && $db = $db->getPdoConnection();

        $id = (int) $id;
        $sql = "DELETE FROM queue WHERE OID={$id}";
        $db->exec($sql);
    }


    protected function doSchedule($time)
    {
        $db = $this->getPdoConnection();

        if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

        $base = $db->quote(p::__BASE__());
        $data = array(
            'task' => $this,
            'cookie' => &$_COOKIE,
            'session' => class_exists('SESSION', false) ? s::getAll() : array()
        );
        $data = $db->quote(serialize($data));

        $sql = "INSERT INTO queue (base, data, run_time)
                VALUES({$base},{$data},{$time})";
        $db->exec($sql);

        $id = $db->lastInsertId();

        $this->registerQueue();

        return $id;
    }

    protected function getQueueDefinition()
    {
        return (object) array(
            'name'   => 'queue',
            'folder' => 'data/queue/pTask/',
            'url'    => 'queue/pTask',
            'sql'    => array(
                "CREATE TABLE queue (base TEXT, data BLOB, run_time INTEGER)",
                "CREATE INDEX run_time ON queue (run_time)",
                "CREATE VIEW waiting AS SELECT * FROM queue WHERE run_time>0",
                "CREATE VIEW error   AS SELECT * FROM queue WHERE run_time=0",

                "CREATE TABLE registry (task_id INTEGER, task_name TEXT, level INTEGER, zcache TEXT)",
                "CREATE INDEX task_id ON registry registry (task_id)",

                "CREATE TRIGGER sync_clean_registry DELETE ON queue
                BEGIN
                    DELETE FROM registry WHERE task_id=OLD.OID;
                END",

                "CREATE TRIGGER sync_clean_queue DELETE ON registry
                BEGIN
                    DELETE FROM queue WHERE OID=OLD.task_id
                END",
            ),
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
        flock($lock, LOCK_EX | LOCK_NB, $type) || $type = true;
        $type || flock($lock, LOCK_UN);
        fclose($lock);

        return $type;
    }

    protected function &getStatic($key)
    {
        $c = get_class($this);

        isset(self::$staticRegistry[$c]) || self::$staticRegistry[$c] = array();

        return self::$staticRegistry[$c][$key];
    }

    function getPdoConnection()
    {
        $db =& $this->getStatic('db');

        if ($db) return $db;

        $q = $this->getQueueDefinition();
        $file = patchworkPath($q->folder) . $q->name . '.sqlite3';
        $sql = file_exists($file);

        $db = new PDO('sqlite:' . $file);

        if (!$sql) foreach ($q->sql as $sql) $db->exec($sql);

        $db->def = $q;

        return $db;
    }
}
