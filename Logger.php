<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

namespace Patchwork\PHP;

class Logger
{
    public

    $writeLock = true,
    $lineFormat = "%s\n",
    $loggedGlobals = array('_SERVER');

    protected

    $logStream,
    $prevTime = 0,
    $startTime = 0,
    $isFirstEvent = true;

    public static

    $errorTypes = array(
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    );


    function __construct($log_stream, $start_time = 0)
    {
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
            'mem'  => memory_get_peak_usage(true) . ' - ' . memory_get_usage(true),
            'data' => $data,
        );

        if ($this->isFirstEvent && $this->loggedGlobals)
        {
            $data['globals'] = array();
            foreach ($this->loggedGlobals as $log_time)
                $data['globals'][$log_time] = isset($GLOBALS[$log_time]) ? $GLOBALS[$log_time] : null;
        }

        $this->writeLock && flock($this->logStream, LOCK_EX);
        $this->writeEvent($type, $data);
        $this->writeLock && flock($this->logStream, LOCK_UN);

        $this->prevTime = microtime(true);
        $this->isFirstEvent = false;
    }

    function logError($e, $trace_offset = -1, $trace_args = 0, $log_time = 0)
    {
        $e = array(
            'mesg' => $e['message'],
            'type' => self::$errorTypes[$e['type']] . ' ' . $e['file'] . ':' . $e['line'],
        ) + $e;

        unset($e['message'], $e['file'], $e['line']);
        if (0 > $trace_offset) unset($e['trace']);
        else if (!empty($e['trace'])) $e['trace'] = $this->filterTrace($e['trace'], $trace_offset, $trace_args);

        $this->log('php-error', $e, $log_time);
    }

    function castException($e)
    {
        $a = (array) $e;

        $a["\0Exception\0trace"] = $this->filterTrace($a["\0Exception\0trace"], $e instanceof RecoverableErrorInterface ? $e->traceOffset : 0, 1);
        if (null === $a["\0Exception\0trace"]) unset($a["\0Exception\0trace"]);
        if ($e instanceof RecoverableErrorInterface) unset($a['traceOffset']);
        if (empty($a["\0Exception\0previous"])) unset($a["\0Exception\0previous"]);
        if ($e instanceof \ErrorException && isset(self::$errorTypes[$a["\0*\0severity"]])) $a["\0*\0severity"] = self::$errorTypes[$a["\0*\0severity"]];
        unset($a["\0Exception\0string"], $a['xdebug_message'], $a['__destructorException']);

        return $a;
    }

    function filterTrace($trace, $offset, $args)
    {
        if (0 > $offset || empty($trace[$offset])) return null;
        else $t = $trace[$offset];

        if (empty($t['class']) && isset($t['function']))
            if ('user_error' === $t['function'] || 'trigger_error' === $t['function'])
                ++$offset;

        $offset && array_splice($trace, 0, $offset);

        foreach ($trace as &$t)
        {
            $t = array(
                'call' => (isset($t['class']) ? $t['class'] . $t['type'] : '')
                    . $t['function'] . '()'
                    . (isset($t['line']) ? " {$t['file']}:{$t['line']}" : '')
            ) + $t;

            unset($t['class'], $t['type'], $t['function'], $t['file'], $t['line']);
            if (isset($t['args']) && !$args) unset($t['args']);
        }

        return $trace;
    }

    function writeEvent($type, $data)
    {
        fprintf($this->logStream, $this->lineFormat, "*** {$type} ***");

        $d = new JsonDumper;
        $d->setCallback('line', array($this, 'writeLine'));
        $d->setCallback('o:exception', array($this, 'castException'));
        $d->walk($data);

        fprintf($this->logStream, $this->lineFormat, '***');
    }

    function writeLine($line, $depth)
    {
        fprintf($this->logStream, $this->lineFormat, str_repeat('  ', $depth) . $line);
    }
}
