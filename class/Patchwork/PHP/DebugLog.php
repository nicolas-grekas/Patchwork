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

class DebugLog
{
    public

    $lock = true,
    $traceDisabledErrors = array(
        E_STRICT => E_STRICT,
        E_NOTICE => E_NOTICE,
        E_DEPRECATED => E_DEPRECATED,
        E_USER_NOTICE => E_USER_NOTICE,
        E_USER_DEPRECATED => E_USER_DEPRECATED,
    );

    protected static

    $errorCodes = array(
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
    ),
    $logFile,
    $logFileStream = null,
    $loggers = array();

    protected

    $startTime  = 0,
    $prevTime   = 0,
    $prevMemory = 0,
    $seenErrors = array(),
    $logStream,
    $lineFormat = "%s\n";


    static function start($log_file = 'php://stderr', self $logger = null)
    {
        null === $logger && $logger = new self;

        // Too bad: formatting errors with html_errors, error_prepend_string or
        // error_append_string only works with displayed errors, not logged ones.
        ini_set('display_errors', false);
        ini_set('log_errors', true);
        ini_set('error_log', $log_file);
        ini_set('ignore_repeated_errors', true);
        ini_set('ignore_repeated_source', false);

        // Some fatal errors can be catched at shutdown time
        register_shutdown_function(array(__CLASS__, 'shutdown'));

        self::$logFile = $log_file;

        $logger->register();

        return $logger;
    }

    static function getLogger()
    {
        return end(self::$loggers);
    }

    static function shutdown()
    {
        if (false === $logger = self::getLogger()) return;

        if ($e = self::getLastError())
        {
            switch ($e['type'])
            {
            // Get the last fatal error and format it appropriately
            case E_ERROR: case E_PARSE: case E_CORE_ERROR:
            case E_COMPILE_ERROR: case E_COMPILE_WARNING:
                $logger->logLastError($e['type'], $e['message'], $e['file'], $e['line']);
                self::resetLastError();
            }
        }
    }

    static function getLastError()
    {
        $e = error_get_last();
        return empty($e['message']) ? false : $e;
    }

    static function resetLastError()
    {
        // Reset error_get_last() by triggering a silenced user notice
        set_error_handler(array(__CLASS__, 'falseError'));
        $r = error_reporting(0);
        user_error('', E_USER_NOTICE);
        error_reporting($r);
        restore_error_handler();
    }

    static function falseError()
    {
        return false;
    }


    function register()
    {
        set_exception_handler(array($this, 'logException'));
        set_error_handler(array($this, 'logError'));
        self::$loggers[] = $this;
        $this->startTime = microtime(true);
    }

    function unregister()
    {
        if ($this === end(self::$loggers))
        {
            array_pop(self::$loggers);
            restore_error_handler();
            restore_exception_handler();
        }
        else
        {
            user_error(__CLASS__ . ' objects have to be unregistered in the exact reverse order they have been registered', E_USER_WARNING);
        }
    }

    function logError($code, $msg, $file, $line, $k, $trace_offset = 0, $log_time = 0)
    {
        $log_time || $log_time = microtime(true);

        if (0 <= $trace_offset)
        {
            ++$trace_offset;

            // For duplicate errors, log the trace only once
            $k = md5("{$code}/{$line}/{$file}\x00{$msg}", true);

            if ( isset($this->traceDisabledErrors[$code])
              || isset($this->traceDisabledErrors[E_ALL])
              || isset($this->seenErrors[$k]) )
            {
                $trace_offset = -1;
            }
            else $this->seenErrors[$k] = 1;
        }

        $k = new ErrorException($msg, $code, 0, $file, $line);
        $k->traceOffset = $trace_offset;
        $k->logTime = $log_time;

        if (E_RECOVERABLE_ERROR === $code) throw $k;
        else $this->logException($k, $log_time);
    }

    function logException(\Exception $e, $log_time = 0)
    {
        $log_time || $log_time = microtime(true);

        $data = array(
            'type' => get_class($e),
            'mesg' => $e->getMessage(),
            'code' => $e->getCode() . ' on line ' . $e->getLine() . ' in ' . $e->getFile(),
            'trace' => $e->getTrace(),
        );

        if ($e instanceof ErrorException)
        {
            unset($data['type']);
            $data['code'] = explode(' ', $data['code'], 2);
            $data['code'] = isset(self::$errorCodes[$data['code'][0]])
                ? self::$errorCodes[$data['code'][0]] . ' ' . $data['code'][1]
                : $data['code'][0] . ' ' . $data['code'][1];

            $e->logTime && $log_time = $e->logTime;

            if (0 <= $e = $e->traceOffset)
            {
                if (isset($data['trace'][$e]['function']))
                {
                    $f = $data['trace'][$e]['function'];
                    if ('user_error' === $f || 'trigger_error' === $f) ++$e;
                }

                $e && array_splice($data['trace'], 0, $e);
            }
            else unset($data['trace']);

            $e = 'php-error';
        }
        else $e = 'php-exception';

        if (isset($data['trace'])) foreach ($data['trace'] as &$t)
        {
            $t = array(
                'call' => (isset($t['class']) ? $t['class'] . $t['type'] : '') . $t['function'] . '() on line ' . $t['line'] . ' in ' . $t['file'],
                'args' => isset($t['args']) ? $t['args'] : false,
            );
        }

        $this->log($e, $data, $log_time);
    }

    function logLastError($code, $message, $file, $line)
    {
        // This serves as a hook if a derivated class wants to catch the last fatal error
    }

    function log($type, array $data = array(), $log_time = 0)
    {
        // Get time and memory profiling information

        $log_time || $log_time = microtime(true);

        $this->prevTime
            || ($this->prevTime = $this->startTime)
            || ($this->prevTime = $this->startTime = $log_time);

        $meta = array(
            'type' => $type,
            'time' => date('c', $log_time) . sprintf(
                ' %06dus - %0.3fms - %0.3fms',
                100000 * ($log_time - floor($log_time)),
                  1000 * ($log_time - $this->startTime),
                  1000 * ($log_time - $this->prevTime)
            ),
            'mem'  => memory_get_peak_usage(true) . ' - ' . (memory_get_usage(true) - $this->prevMemory),
        );

        isset($this->logStream)
            || ($this->logStream = self::$logFileStream)
            || ($this->logStream = self::$logFileStream = fopen(self::$logFile, 'ab'));

        $this->lock && flock($this->logStream, LOCK_EX);
        $this->dumpEvent($meta, $data);
        $this->lock && flock($this->logStream, LOCK_UN);

        $data = array();
        $this->prevMemory = memory_get_usage(true);
        $this->prevTime = microtime(true);
    }

    function dumpEvent($meta, $data)
    {
        fprintf($this->logStream, $this->lineFormat, "*** {$meta['type']} ***");
        unset($meta['type']);

        foreach ($meta as $d => $meta) fprintf(
            $this->logStream,
            $this->lineFormat,
            "- {$d}: " . strtr($meta, "\r\n", '  ')
        );

        $d = new Dumper;
        $d->setCallback('line', array($this, 'dumpLine'));
        $d->dumpLines($data, false);

        fprintf($this->logStream, $this->lineFormat, '***');
    }

    function dumpLine($line)
    {
        fprintf($this->logStream, $this->lineFormat, $line);
    }
}

class ErrorException extends \ErrorException
{
    public $traceOffset = 0, $logTime = 0;
}
