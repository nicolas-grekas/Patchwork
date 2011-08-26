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
    $recoverableErrors = 0x1100,   // E_RECOVERABLE_ERROR | E_USER_ERROR
    $traceDisabledErrors = 0x6c08; // E_STRICT | E_NOTICE | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED

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

    $prevTime = 0,
    $startTime = 0,
    $prevMemory = 0,
    $loggedTraces = array(),
    $logStream,
    $lineFormat = "%s\n";


    static function start($log_file = 'php://stderr', self $logger = null)
    {
        null === $logger && $logger = new self;

        // See also error_reporting and log_errors_max_len
        // Formatting errors with html_errors, error_prepend_string or
        // error_append_string only works with displayed errors, not logged ones.
        ini_set('display_errors', false);
        ini_set('log_errors', true);
        ini_set('error_log', $log_file);

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
        // Reset error_get_last() by triggering a silenced empty user notice
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


    function register($error_types = -1)
    {
        set_exception_handler(array($this, 'logException'));
        set_error_handler(array($this, 'logError'), $error_types);
        self::$loggers[] = $this;
        $this->startTime = microtime(true);
    }

    function unregister()
    {
        $ok = array(
            $this === end(self::$loggers),
            array($this, 'logError') === set_error_handler(array(__CLASS__, 'falseError')),
            array($this, 'logException') === set_exception_handler(array(__CLASS__, 'falseError')),
        );

        if ($ok = array(true, true, true) === $ok)
        {
            array_pop(self::$loggers);
            restore_error_handler();
            restore_exception_handler();
        }
        else user_error('Failed to unregister: the current error or exception handler is not me', E_USER_WARNING);

        restore_error_handler();
        restore_exception_handler();

        return $ok;
    }

    function logError($code, $mesg, $file, $line, $k, $trace_offset = 0, $log_time = 0)
    {
        $log_error = error_reporting() & $code;

        if ($log_error || ($this->recoverableErrors & $code))
        {
            $log_time || $log_time = microtime(true);

            if (0 <= $trace_offset)
            {
                ++$trace_offset;

                // For duplicate errors, log the trace only once
                $k = md5("{$code}/{$line}/{$file}\x00{$mesg}", true);

                if (($this->traceDisabledErrors & $code) || isset($this->loggedTraces[$k])) $trace_offset = -1;
                else if ($log_error) $this->loggedTraces[$k] = 1;
            }

            $k = new RecoverableErrorException($mesg, $code, 0, $file, $line);
            $k->traceOffset = $trace_offset;

            if ($log_error)
            {
                $k = array(
                    'mesg' => $mesg,
                    'code' => self::$errorCodes[$code] . ' ' . $file . ':' . $line,
                );

                if (0 <= $trace_offset) $k['trace'] = $this->filterTrace(debug_backtrace(false), $trace_offset);

                $this->log('php-error', $k, $log_time);
            }

            if ($this->recoverableErrors & $code)
            {
                $k = new RecoverableErrorException($mesg, $code, 0, $file, $line);
                $k->traceOffset = $log_error ? -1 : $trace_offset;
                throw $k;
            }
        }

        return $log_error;
    }

    function logException(\Exception $e, $log_time = 0)
    {
        $this->log('php-exception', $e, $log_time);
    }

    function logLastError($code, $message, $file, $line)
    {
        // This serves as a hook if a derivated class wants to catch the last fatal error
    }

    function castException($e)
    {
        $a = array(
            'mesg' => $e->getMessage(),
            'code' => $e->getCode() . ' ' . $e->getFile() . ':' . $e->getLine(),
            'trace' => $this->filterTrace($e->getTrace(), $e instanceof ExceptionWithTraceOffset ? $e->traceOffset : 0),
        ) + (array) $e;

        if (null === $a['trace']) unset($a['trace']);
        if ($e instanceof ExceptionWithTraceOffset) unset($a['traceOffset']);
        if (empty($a["\0Exception\0previous"])) unset($a["\0Exception\0previous"]);
        if ($e instanceof \ErrorException && empty($a["\0*\0severity"])) unset($a["\0*\0severity"]);
        unset($a["\0*\0message"], $a["\0Exception\0string"], $a["\0*\0code"], $a["\0*\0file"], $a["\0*\0line"], $a["\0Exception\0trace"], $a['xdebug_message']);

        return $a;
    }

    function filterTrace($trace, $offset)
    {
        if (0 > $offset) return null;

        if (isset($trace[$offset]['function']))
        {
            $t = $trace[$offset]['function'];
            if ('user_error' === $t || 'trigger_error' === $t) ++$offset;
        }

        $offset && array_splice($trace, 0, $offset);

        foreach ($trace as &$t)
        {
            $t = array(
                'call' => (isset($t['class']) ? $t['class'] . $t['type'] : '')
                    . $t['function'] . '()'
                    . (isset($t['line']) ? " {$t['file']}:{$t['line']}" : '')
            ) + $t;

            unset($t['class'], $t['type'], $t['function'], $t['file'], $t['line']);
        }

        return $trace;
    }

    function log($type, $data, $log_time = 0)
    {
        // Get time and memory profiling information

        $log_time || $log_time = microtime(true);

        $this->prevTime
            || ($this->prevTime = $this->startTime)
            || ($this->prevTime = $this->startTime = $log_time);

        $data = array(
            'time' => date('c', $log_time) . sprintf(
                ' %06dus - %0.3fms - %0.3fms',
                100000 * ($log_time - floor($log_time)),
                  1000 * ($log_time - $this->startTime),
                  1000 * ($log_time - $this->prevTime)
            ),
            'mem'  => memory_get_peak_usage(true) . ' - ' . (memory_get_usage(true) - $this->prevMemory),
            'data' => $data,
        );

        isset($this->logStream)
            || ($this->logStream = self::$logFileStream)
            || ($this->logStream = self::$logFileStream = fopen(self::$logFile, 'ab'));

        $this->lock && flock($this->logStream, LOCK_EX);
        $this->dumpEvent($type, $data);
        $this->lock && flock($this->logStream, LOCK_UN);

        $this->prevMemory = memory_get_usage(true);
        $this->prevTime = microtime(true);
    }

    function dumpEvent($type, $data)
    {
        fprintf($this->logStream, $this->lineFormat, "*** {$type} ***");

        $d = new Dumper;
        $d->setCallback('line', array($this, 'dumpLine'));
        $d->setCallback('o:exception', array($this, 'castException'));
        $d->dumpLines($data);

        fprintf($this->logStream, $this->lineFormat, '***');
    }

    function dumpLine($line)
    {
        fprintf($this->logStream, $this->lineFormat, $line);
    }
}

class RecoverableErrorException extends \ErrorException implements ExceptionWithTraceOffset
{
    public $traceOffset = 0;
}

interface ExceptionWithTraceOffset {}
