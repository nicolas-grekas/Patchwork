<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP;

use Patchwork\PHP\Dumper\ExceptionCaster;

/**
 * Logger logs messages to an output stream.
 *
 * Messages just have a type and associated data. The dump format is handled by JsonDumper
 * which allows unprecedented accuracy for associated data representation.
 *
 * Error messages are handled specifically in order to make them more friendly,
 * especially for traces and exceptions.
 */
class Logger
{
    public

    $lineFormat = "%s",
    $loggedGlobals = array('_SERVER');

    protected

    $uniqId,
    $logStream,
    $prevTime = 0,
    $startTime = 0,
    $isFirstEvent = true;


    function __construct($log_stream, $start_time = 0)
    {
        $this->uniqId = mt_rand();
        $start_time || $start_time = microtime(true);
        $this->startTime = $this->prevTime = $start_time;
        $this->logStream = $log_stream;
    }

    function log($type, $data, $log_time = 0)
    {
        // Get time and memory profiling information

        $log_time || $log_time = microtime(true);

        $data = array(
            'time' => date('c', $log_time) . sprintf(
                ' %06dus - %0.3fms - %0.3fms',
                100000 * ($log_time - floor($log_time)),
                  1000 * ($log_time - $this->startTime),
                  1000 * ($log_time - $this->prevTime)
            ),
            'mem'  => memory_get_peak_usage() . ' - ' . memory_get_usage(),
            'data' => $data,
        );

        if ($this->isFirstEvent && $this->loggedGlobals)
        {
            $data['globals'] = array();
            foreach ($this->loggedGlobals as $log_time)
                $data['globals'][$log_time] = isset($GLOBALS[$log_time]) ? $GLOBALS[$log_time] : null;
        }

        $this->writeEvent($type, $data);

        $this->prevTime = microtime(true);
        $this->isFirstEvent = false;
    }

    function logError($e, $trace_offset = -1, $trace_args = 0, $log_time = 0)
    {
        $e = array(
            'mesg' => $e['message'],
            'type' => ExceptionCaster::$errorTypes[$e['type']] . ' ' . $e['file'] . ':' . $e['line'],
        ) + $e;

        unset($e['message'], $e['file'], $e['line']);
        if (0 > $trace_offset) unset($e['trace']);
        else if (!empty($e['trace'])) ExceptionCaster::filterTrace($e['trace'], $trace_offset, $trace_args);

        $this->log('php-error', $e, $log_time);
    }

    function castException(\Exception $e, array $a)
    {
        $trace = $e->getTrace();

        if (isset($trace[0][$this->uniqId]))
        {
            $a["\0Exception\0trace"] = array();
            $a = ExceptionCaster::castException($e, $a);
            $a["\0Exception\0trace"] = array('seeHash' => spl_object_hash($e));
        }
        else if (isset($trace[0]))
        {
            static $traceProp;

            if (! isset($traceProp))
            {
                $traceProp = new \ReflectionProperty('Exception', 'trace');
                $traceProp->setAccessible(true);
            }

            $trace[0][$this->uniqId] = 1;
            $traceProp->setValue($e, $trace);

            $a = ExceptionCaster::castException($e, $a);
            $a["\0~\0hash"] = spl_object_hash($e);
        }

        return $a;
    }

    function writeEvent($type, $data)
    {
        fprintf($this->logStream, $this->lineFormat . PHP_EOL, "*** {$type} ***");

        $d = JsonDumper::$defaultCasters;
        $d['o:Exception'] = array($this, 'castException');
        $d = new JsonDumper($d);
        $d->setLineDumper(array($this, 'writeLine'));
        $d->walk($data);

        fprintf($this->logStream, $this->lineFormat . PHP_EOL, '***');
    }

    function writeLine($line, $depth)
    {
        fprintf($this->logStream, $this->lineFormat . PHP_EOL, str_repeat('  ', $depth) . $line);
    }
}
