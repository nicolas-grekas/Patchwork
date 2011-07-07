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

namespace Patchwork;

class Logger
{
    protected static

    $session,
    $startTime,
    $outputFile,
    $outputFileStream = null,
    $loggers = array();

    protected

    $token,
    $prevTime,
    $prevMemory,
    $seenErrors = array(),
    $outputStream;


    static function start($output_file, $session, self $logger = null)
    {
        null === $logger && $logger = new self;

        // Too bad: formatting errors with html_errors, error_prepend_string
        // or error_append_string only works with display_errors=1
        ini_set('display_errors', false);
        ini_set('log_errors', true);
        ini_set('error_log', $output_file);
        ini_set('ignore_repeated_errors', true);
        ini_set('ignore_repeated_source', false);

        register_shutdown_function(array(__CLASS__, 'shutdown'));

        self::$session = $session;
        self::$outputFile = $output_file;

        $logger->register();
        $logger->log('logger-start', array(
            'start-time' => date('c'),
            'server-context' => $_SERVER,
        ));

        self::$startTime = $logger->prevTime;
    }

    static function shutdown()
    {
        $logger = end(self::$loggers);

        if (function_exists('error_get_last') && $e = error_get_last())
        {
            switch ($e['type'])
            {
            // Get the last fatal error and format it appropriately!
            case E_ERROR: case E_PARSE: case E_CORE_ERROR:
            case E_COMPILE_ERROR: case E_COMPILE_WARNING:
                $logger->logError($e['type'], $e['message'], $e['file'], $e['line']);
            }
        }

        $logger->log('logger-shutdown', array(
            'total-time-ms' => 1000*(microtime(true) - self::$startTime),
        ));

        while (self::$loggers) end(self::$loggers)->unregister();

        self::$outputFileStream && fclose(self::$outputFileStream);
        self::$outputFileStream = null;
    }


    function register()
    {
        set_exception_handler(array($this, 'logException'));
        set_error_handler(array($this, 'logError'));
        self::$loggers[] = $this;
        $this->token = mt_rand();
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

    function logError($no, $msg, $file, $line, $context = null)
    {
        $k = md5("{$no}/{$line}/{$file}\x00{$msg}", true);
        if (isset($this->seenErrors[$k])) return;
        $this->seenErrors[$k] = 1;

        $this->log('error', array(
            'level'   => $no,
            'message' => $msg,
            'file'    => $file,
            'line'    => $line,
//            'context' => $context,
//            'trace'   => debug_backtrace(false),
        ));
    }

    function logException($e)
    {
        $this->log('exception', array(
            'exception'   => get_class($e),
            'message'     => $e->getMessage(),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
            'code'        => $e->getCode(),
            'traceString' => $e->getTraceAsString(),
//            'trace'       => $e->getTrace(),
        ));
    }

    function log($type, $content)
    {
        $delta_time = isset($this->prevTime) ? 1000*(microtime(true) - $this->prevTime) : 0;
        $delta_mem  = isset($this->prevMemory) ? memory_get_usage(true) - $this->prevMemory : 0;
        $peak_mem   =  memory_get_peak_usage(true);

        if (null === $this->token)
        {
            return user_error('This ' . __CLASS__ . ' object has been unregistered', E_USER_WARNING);
        }

        if (is_array($content))
        {
            $v = $content;
            $content = '';

            foreach ($v as $k => $v)
            {
                $v = print_r($v, true);
                $v = htmlspecialchars($v);
                $content .= "\t<{$k}>{$v}</{$k}>\n";
            }
        }

        $v = self::$session . ':' . $this->token;

        isset($this->outputStream)
            || ($this->outputStream = self::$outputFileStream)
            || ($this->outputStream = self::$outputFileStream = fopen(self::$outputFile, 'ab'));

        fwrite(
            $this->outputStream,
            <<<EOTXT
<event:{$v} type="{$type}" delta-time-ms="{$delta_time}" delta-memory="{$delta_mem}" peak-memory="{$peak_mem}">
{$content}
</event:{$v}>\n
EOTXT
        );

        unset($type, $content, $k, $v, $delta_time, $delta_mem, $peak_mem);

        $this->prevTime = microtime(true);
        $this->prevMemory = memory_get_usage(true);
    }
}
