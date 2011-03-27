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

class pMail extends pTask
{
    protected $testMode = DEBUG;

    static function send($headers, $text, $options = array())
    {
        is_object($options) && $options = (array) $options;

        $options['text'] =& $text;

        return self::queueMail('pMail_text', $headers, $options);
    }

    static function sendAgent($headers, $agent, $args = array(), $options = array())
    {
        is_object($args)    && $args    = (array) $args;
        is_object($options) && $options = (array) $options;

        $options['agent'] = $agent;
        $options['args']  =& $args;

        return self::queueMail('pMail_agent', $headers, $options);
    }

    static function sendTemplate($headers, $template, $data = array(), $options = array())
    {
        is_object($options) && $options = (array) $options;

        $options['template'] = $template;
        $options['data']     =& $data;

        return self::queueMail('pMail_template', $headers, $options);
    }

    protected static function queueMail($mailer, &$headers, &$options, $queue = false)
    {
        $queue || $queue = new self;

        is_object($headers) && $headers = (array) $headers;
        is_object($options) && $options = (array) $options;

        return $queue->pushMail($mailer, $headers, $options);
    }

    protected function pushMail($mailer, &$headers, &$options)
    {
        if (isset($options['testMode'])) $this->testMode = $options['testMode'];
        else if ($this->testMode) $options['testMode'] = 1;

        $sent = - (int)(bool) !empty($options['testMode']);
        $archive = (int) !(empty($options['archive']) && empty($options['testMode']));

        $time = isset($options['time']) ? $options['time'] : 0;
        if ($time < $_SERVER['REQUEST_TIME'] - 366*86400) $time += $_SERVER['REQUEST_TIME'];

        if (!empty($options['attachments']) && is_array($options['attachments']))
        {
            $tmpToken = false;

            foreach ($options['attachments'] as &$file)
            {
                if (is_uploaded_file($file) || PATCHWORK_ZCACHE === substr($file, 0, strlen(PATCHWORK_ZCACHE)))
                {
                    $tmpToken || $tmpToken = p::strongId(8);
                    $base = PATCHWORK_ZCACHE . p::strongId(8) . '~' . $tmpToken;
                    copy($file, $base);
                    $file = $base;
                }
            }

            unset($file, $options['attachments.tmpToken']);

            $tmpToken && $options['attachments.tmpToken'] = $tmpToken;
        }

        $data = array(
            'mailer'  => $mailer,
            'headers' => &$headers,
            'options' => &$options,
            'cookie'  => &$_COOKIE,
            'session' => class_exists('SESSION', false) ? s::getAll() : array(),
        );

        $sqlite = $this->getSqlite();

        $base = sqlite_escape_string(p::__BASE__());
        $data = sqlite_escape_string(serialize($data));

        $sql = "INSERT INTO queue (base, data, send_time, archive, sent_time)
                VALUES('{$base}','{$data}',{$time},{$archive},{$sent})";
        $sqlite->queryExec($sql);

        $sql = $sqlite->lastInsertRowid();

        $this->registerQueue();

        return $sql;
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
                CREATE VIEW waiting AS SELECT * FROM queue WHERE sent_time=0 AND send_time>0;
                CREATE VIEW error   AS SELECT * FROM queue WHERE sent_time=0 AND send_time=0;
                CREATE VIEW archive AS SELECT * FROM queue WHERE sent_time>0;
EOSQL
        );
    }
}
