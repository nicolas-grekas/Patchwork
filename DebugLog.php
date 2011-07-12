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
                $logger->logError($e['type'], $e['message'], $e['file'], $e['line'], array(), 1);
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

        // Get backtrace and exclude irrelevant items
        $trace = new \Exception;
        $trace = explode("\n", $trace->getTraceAsString());
        do unset($trace[$trace_offset]);
        while ($trace_offset--);

        $this->log('php-error', array(
            'code'    => $code,
            'message' => $msg,
            'file'    => $file,
            'line'    => $line,
            'trace'   => implode("\n", $trace),
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
            'trace'   => $e->getTraceAsString(),
        ));
    }

    function log($type, array $context = array())
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

        $delta_ms  = sprintf('%0.3f', 1000*($log_time - $this->prevTime));
        $total_ms  = sprintf('%0.3f', 1000*($log_time - $this->startTime));
        $delta_mem = isset($this->prevMemory) ? memory_get_usage(true) - $this->prevMemory : 0;
        $peak_mem  = memory_get_peak_usage(true);
        $log_time  = date('c', $log_time) . sprintf(' %06dus', 100000*($log_time - floor($log_time)));

        foreach ($context as $k => $v)
        {
            $v = $this->serialize($v);

            if (strcspn($v, "\r\n\0") !== strlen($v))
            {
                // Encode CR and LF using the null character as
                // escape character and get a single line string
                $v = str_replace(
                    array(  "\0",  "\r",  "\n"),
                    array("\0\0", "\0r", "\0n"),
                    $v
                );
            }

            $context[$k] = "\n  " . strtr($k, "\r\n:", '---') . ': ' . $v;
        }

        $v = self::$session . ':' . $this->token . ':' . mt_rand();
        $context = implode('', $context);
        $type = strtr($type, "\r\n", '--');

        isset($this->logStream)
            || ($this->logStream = self::$logFileStream)
            || ($this->logStream = self::$logFileStream = fopen(self::$logFile, 'ab'));

        fwrite(
            $this->logStream,
            <<<EOTXT
<event:{$v}>
  type: {$type}
  log-time: {$log_time}
  peak-mem: {$peak_mem}
  delta-ms: {$delta_ms}
  total-ms: {$total_ms}
  delta-mem: {$delta_mem}
  ---{$context}
</event:{$v}>


EOTXT
        );

        $context = '';
        $this->prevMemory = memory_get_usage(true);
        $this->prevTime = microtime(true);
    }

    function serialize($v)
    {
        // serialize() is the only native way to get a string representation
        // of complex variables while both dealing with recursivity in data structures
        // and working in output buffering handlers.
        // Objects implementing the magic __sleep() method or the Serializable interface
        // may fail, especially internal ones (PDO, etc.)
        // Resources are also casted to null integers.

        try
        {
            return serialize($v);
        }
        catch (\Exception $v)
        {
            return serialize(
                "*** Failed to serialize: `" . get_class($v)
                . "` exception with message `{$v->getMessage()}` thrown in {$v->getFile()} on line {$v->getLine()} ***\n"
                . $v->getTraceAsString()
            );
        }
    }
}
