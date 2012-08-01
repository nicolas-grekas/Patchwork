<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork as p;
use SESSION   as s;

class agent_queue_pTask extends agent
{
    const contentType = 'image/gif';


    public

    $get = array(
        '__1__:i:1',
        '__2__:c:[-_0-9a-zA-Z]{32}'
    );


    protected

    $maxage = -1,

    $lock,
    $queueName = 'queue',
    $queueFolder = 'data/queue/pTask/',
    $dual = 'pTask',

    $db;


    function control()
    {
        $d = $this->dual;
        $d = $this->dual = new $d;
        $this->db = $d->getPdoConnection();

        if (!empty($this->get->__1__))
        {
            $id = $this->get->__1__;

            if (!empty($this->get->__2__) && $this->get->__2__ == $this->getToken())
            {
                if ($this->getLock())
                {
                    ob_start(array($this, 'ob_handler'));
                    $this->doOne($id);
                    ob_end_flush();
                }
                else $this->doAsap($id);

                return;
            }
            else $this->touchOne($id);
        }

        $this->queueNext();
    }

    function compose($o)
    {
        echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
    }

    function ob_handler($buffer)
    {
        $this->releaseLock();
        $this->queueNext();

        '' !== $buffer && user_error($buffer);

        return '';
    }

    protected function queueNext()
    {
        $time = time();
        $sql = "SELECT rowid, base, run_time FROM queue WHERE run_time>0 ORDER BY run_time, rowid LIMIT 1";
        if ($data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC))
        {
            $data = $data[0];

            0 > $this->maxage && $this->maxage = $CONFIG['maxage'];

            if ($data['run_time'] <= $time)
            {
                $sql = "UPDATE queue SET run_time=0
                        WHERE rowid={$data['rowid']} AND run_time>0";
                if ($this->db->exec($sql)) tool_url::touch("{$data['base']}queue/pTask/{$data['rowid']}/" . $this->getToken());

                $sql = "SELECT run_time FROM queue WHERE run_time>{$time} ORDER BY run_time LIMIT 1";
                if ($data = $this->db->query($sql)->fetchAll(PDO::FETCH_NUM)) p::setMaxage(min($this->maxage, $data[0][0] - $time));
            }
            else p::setMaxage(min($this->maxage, $data['run_time'] - $time));
        }
    }

    protected function doAsap($id)
    {
        $sql = "UPDATE queue SET run_time=1
                WHERE rowid={$id} AND run_time=0";
        $this->db->exec($sql);
    }

    protected function doOne($id)
    {
        $db = $this->db;

        $sql = "SELECT data FROM queue WHERE rowid={$id} AND run_time=0";
        $data = $db->query($sql)->fetchAll(PDO::FETCH_NUM);

        if (!$data) return;

        $data_serialized = $data[0][0];
        $data = unserialize($data_serialized);

        $this->restoreContext($data['cookie'], $data['session']);

        try
        {
            try
            {
                if (0 < $time = (int) $data['task']->getNextRun())
                {
                    $sql = time();
                    if ($time < $sql - 366*86400) $time += $sql;

                    $sql = "UPDATE queue SET run_time={$time} WHERE rowid={$id}";
                    $db->exec($sql);
                }
            }
            catch (Exception $e)
            {
                $data['task']->run();
                throw $e;
            }

            $data['task']->run();
        }
        catch (Exception $e)
        {
            echo "Exception on pTask #{$id}:\n\n";
            print_r($e);
            $time = false;
        }

        if ($time > 0)
        {
            $data['session'] = class_exists('SESSION', false) ? s::getAll() : array();

            if ($data_serialized !== $data = serialize($data))
            {
                $sql = "UPDATE queue SET data=? WHERE rowid=?";
                $db->prepare($sql)->execute(array($data, $id));
            }
        }
        else if (false !== $time)
        {
            $sql = "DELETE FROM queue WHERE rowid={$id}";
            $db->exec($sql);
        }
    }

    protected function touchOne($id)
    {
    }

    protected function restoreContext(&$cookie, &$session)
    {
        if ($session)
        {
            $_COOKIE = array();
            foreach ($session as $k => &$v) s::set($k, $v);
            s::regenerateId(false, false);
        }

        $_COOKIE =& $cookie;
    }

    protected function getLock()
    {
        $lock = patchworkPath($this->queueFolder) . $this->queueName . '.lock';

        if (!file_exists($lock))
        {
            touch($lock);
            chmod($lock, 0666);
        }

        $this->lock = $lock = fopen($lock, 'wb');
        flock($lock, LOCK_EX | LOCK_NB, $wb) || $wb = true;

        if ($wb)
        {
            fclose($lock);
            return false;
        }

        set_time_limit(0);

        return true;
    }

    protected function releaseLock()
    {
        flock($this->lock, LOCK_UN);
        fclose($this->lock);
    }

    protected function getToken()
    {
        $token = patchworkPath($this->queueFolder) . $this->queueName . '.token';

        //XXX user right problem?
        file_exists($token) || file_put_contents($token, p::strongId());

        return trim(file_get_contents($token));
    }
}
