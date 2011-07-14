<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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

namespace Patchwork\PHP;

class DebugLog
{
    protected static

    $session,
    $logFile,
    $logFileStream = null,
    $loggers = array();

    protected

    $token,
    $index      = 0,
    $startTime  = 0,
    $prevTime   = 0,
    $prevMemory = 0,
    $seenErrors = array(),
    $logStream;


    static function start($log_file, $session = null, self $logger = null)
    {
        null === $logger && $logger = new self;
        null === $session && $session = empty(self::$session) ? mt_rand() : self::$session;

        // Too bad: formatting errors with html_errors, error_prepend_string
        // or error_append_string only works with display_errors=1
        ini_set('display_errors', false);
        ini_set('log_errors', true);
        ini_set('error_log', $log_file);
        ini_set('ignore_repeated_errors', true);
        ini_set('ignore_repeated_source', false);

        // Fatal errors can be catched at shutdown time
        if (function_exists('error_get_last'))
            register_shutdown_function(array(__CLASS__, 'shutdown'));

        self::$session = $session;
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
                $logger->logError($e['type'], $e['message'], $e['file'], $e['line'], array(), -1);
                self::resetLastError();
            }
        }
    }

    static function getLastError()
    {
        return function_exists('error_get_last') && ($e = error_get_last()) && !empty($e['message'])
            ? $e : false;
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
        $this->token = mt_rand();
        $this->index = 0;
        $this->startTime = microtime(true);
    }

    function unregister()
    {
        if ($this === end(self::$loggers))
        {
            $this->token = null;
            array_pop(self::$loggers);
            restore_error_handler();
            restore_exception_handler();
        }
        else
        {
            user_error(__CLASS__ . ' objects have to be unregistered in the exact reverse order they have been registered', E_USER_WARNING);
        }
    }

    function logError($code, $msg, $file, $line, $trace, $trace_offset = 0)
    {
        // Do not log duplicate errors
        $k = md5("{$code}/{$line}/{$file}\x00{$msg}", true);
        if (isset($this->seenErrors[$k])) return;
        $this->seenErrors[$k] = 1;

        $trace = null;

        // Do not log the backtrace for these levels
        if (0 === $trace_offset) switch ($code)
        {
            case E_NOTICE:
            case E_STRICT:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $trace_offset = -1;
                break;
        }

        // Get backtrace and exclude irrelevant items
        if (0 <= $trace_offset)
        {
            $trace = debug_backtrace(false);

            if (isset($trace[++$trace_offset]['function']))
            {
                $k = $trace[$trace_offset]['function'];
                if ('user_error' === $k || 'trigger_error' === $k) ++$trace_offset;
            }

            array_splice($trace, 0, $trace_offset);
        }

        $this->log('php-error', array(
            'code'    => $code,
            'message' => $msg,
            'file'    => $file,
            'line'    => $line,
            'trace'   => $trace,
        ));
    }

    function logException(\Exception $e)
    {
        $this->log('php-exception', array(
            'class'   => get_class($e),
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTrace(),
        ));
    }

    function log($type, array $data = array())
    {
        if (null === $this->token)
        {
            return user_error('This ' . __CLASS__ . ' object has been unregistered', E_USER_WARNING);
        }

        // Get time and memory profiling information

        $log_time = microtime(true);

        $this->prevTime
            || ($this->prevTime = $this->startTime)
            || ($this->prevTime = $this->startTime = $log_time);

        $type = strtr($type, "\r\n", '--');
        $v = self::$session . ':' . $this->token . ':' . ++$this->index;
        $data = array(
            'delta-ms' => sprintf('%0.3f', 1000*($log_time - $this->prevTime)),
            'total-ms' => sprintf('%0.3f', 1000*($log_time - $this->startTime)),
            'delta-mem' => isset($this->prevMemory) ? memory_get_usage(true) - $this->prevMemory : 0,
            'peak_mem' => memory_get_peak_usage(true),
            'log-time' => date('c', $log_time) . sprintf(' %06dus', 100000*($log_time - floor($log_time))),
            'log-data' => $data,
        );

        isset($this->logStream)
            || ($this->logStream = self::$logFileStream)
            || ($this->logStream = self::$logFileStream = fopen(self::$logFile, 'ab'));

        fwrite(
            $this->logStream,
            <<<EOTXT
<event:{$v} type="{$type}">
{$this->dump($data)}
</event:{$v}>


EOTXT
        );

        $data = '';
        $this->prevMemory = memory_get_usage(true);
        $this->prevTime = microtime(true);
    }

    function dump(&$v)
    {
        return Dumper::dump($v, false);
    }
}
