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

class ErrorHandler
{
    public

    $recoverableErrors = 0x1100, // E_RECOVERABLE_ERROR | E_USER_ERROR
    $scopedErrors = 0x0202, // E_WARNING | E_USER_WARNING
    $tracedErrors = 0x1302; // E_RECOVERABLE_ERROR | E_USER_ERROR | E_WARNING | E_USER_WARNING

    protected

    $logger,
    $loggedTraces = array();

    protected static

    $logFile,
    $logStream,
    $shuttingDown = false,
    $handlers = array();


    static function start($log_file = 'php://stderr', self $handler = null)
    {
        null === $handler && $handler = new self;

        // See also http://php.net/error_reporting
        // Formatting errors with html_errors, error_prepend_string or
        // error_append_string only works with displayed errors, not logged ones.
        ini_set('display_errors', false);
        ini_set('log_errors', true);
        ini_set('error_log', $log_file);

        // Some fatal errors can be catched at shutdown time!
        // Then, any fatal error is really fatal: remaining shutdown
        // functions, output buffering handlers or destructors are not called!
        register_shutdown_function(array(__CLASS__, 'shutdown'));

        self::$logFile = $log_file;

        $handler->register();

        return $handler;
    }

    static function getHandler()
    {
        return end(self::$handlers);
    }

    static function shutdown()
    {
        self::$shuttingDown = true;

        if (false === $handler = end(self::$handlers)) return;

        if ($e = self::getLastError())
        {
            switch ($e['type'])
            {
            // Get the last fatal error and format it appropriately
            case E_ERROR: case E_PARSE: case E_CORE_ERROR:
            case E_COMPILE_ERROR: case E_COMPILE_WARNING:
                $handler->handleFatalError($e['type'], $e['message'], $e['file'], $e['line']);
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
        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'), $error_types);
        self::$handlers[] = $this;
    }

    function unregister()
    {
        $ok = array(
            $this === end(self::$handlers),
            array($this, 'handleError') === set_error_handler(array(__CLASS__, 'falseError')),
            array($this, 'handleException') === set_exception_handler(array(__CLASS__, 'falseError')),
        );

        if ($ok = array(true, true, true) === $ok)
        {
            array_pop(self::$handlers);
            restore_error_handler();
            restore_exception_handler();
        }
        else user_error('Failed to unregister: the current error or exception handler is not me', E_USER_WARNING);

        restore_error_handler();
        restore_exception_handler();

        return $ok;
    }

    function handleError($code, $message, $file, $line, $scope, $trace_offset = 0, $log_time = 0)
    {
        if ($log_error = (error_reporting() | $this->recoverableErrors) & $code)
        {
            $log_time || $log_time = microtime(true);

            // To prevent logging of catched exceptions and
            // to remove duplicate logged and uncatched exception messages,
            // do not log recoverable errors except at shutdown time.
            self::$shuttingDown || $log_error &= ~$this->recoverableErrors;

            if (0 <= $trace_offset)
            {
                ++$trace_offset;

                // For duplicate errors, log the trace only once
                $e = md5("{$code}/{$line}/{$file}\x00{$message}", true);

                if (!($this->tracedErrors & $code) || isset($this->loggedTraces[$e])) $trace_offset = -1;
                else if ($log_error) $this->loggedTraces[$e] = 1;
            }

            if ($log_error)
            {
                $e = array('code' => $code, 'message' => $message, 'file' => $file, 'line' => $line);

                if ($this->scopedErrors & $code) $e['scope'] = $scope;
                if (0 <= $trace_offset) $e['trace'] = debug_backtrace(false);

                $this->getLogger()->logError($e, $trace_offset, $log_time);
            }

            if ($this->recoverableErrors & $code)
            {
                $e = new RecoverableErrorException($message, $code, 0, $file, $line);
                $log_error || $e->traceOffset = $trace_offset;
                $e->scope = $scope;
                throw $e;
            }
        }

        return $log_error;
    }

    function handleException(\Exception $e, $log_time = 0)
    {
        // Do not consider error_reporting level: uncatched exception are always logged
        $this->getLogger()->logException($e, $log_time);
    }

    function handleFatalError($code, $message, $file, $line)
    {
        // Log fatal errors when they have not been logged by the native PHP error handler
        if (error_reporting() & $code) return;
        $this->getLogger()->logError(array('code' => $code, 'message' => $message, 'file' => $file, 'line' => $line), -1, 0);
    }

    function getLogger()
    {
        if (isset($this->logger)) return $this->logger;
        isset(self::$logStream) || self::$logStream = fopen(self::$logFile, 'ab');
        return $this->logger = new Logger(self::$logStream);
    }
}

class RecoverableErrorException extends \ErrorException implements RecoverableErrorInterface
{
    public $traceOffset = -1, $scope = array();
}

interface RecoverableErrorInterface {}
