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

    $scream = 0x51, // E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR,
    $recoverableErrors = 0x1100, // E_RECOVERABLE_ERROR | E_USER_ERROR
    $scopedErrors = 0x0203, // E_ERROR | E_WARNING | E_USER_WARNING
    $tracedErrors = 0x1306; // E_RECOVERABLE_ERROR | E_USER_ERROR | E_WARNING | E_USER_WARNING | E_PARSE

    protected

    $logger,
    $loggedTraces = array(),
    $registeredErrors = 0;

    protected static

    $logFile,
    $logStream,
    $shuttingDown = 0,
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

        // Some fatal errors can be caught at shutdown time!
        // Then, any fatal error is really fatal: remaining shutdown
        // functions, output buffering handlers or destructors are not called!
        register_shutdown_function(array(__CLASS__, 'shutdown'));

        self::$logFile = $log_file;

        // Register the handler and top it to the current error_reporting() level
        $handler->register(error_reporting());

        return $handler;
    }

    static function getHandler()
    {
        return end(self::$handlers);
    }

    static function shutdown()
    {
        self::$shuttingDown = 1;

        if (false === $handler = end(self::$handlers)) return;

        if ($e = self::getLastError())
        {
            switch ($e['type'])
            {
            // Get the last uncatchable error
            case E_ERROR: case E_PARSE:
            case E_CORE_ERROR: case E_CORE_WARNING:
            case E_COMPILE_ERROR: case E_COMPILE_WARNING:
                $handler->handleLastError($e);
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
        $r = error_reporting(81);
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
        $this->registeredErrors = $error_types;
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
            $this->registeredErrors = 0;
        }
        else user_error('Failed to unregister: the current error or exception handler is not me', E_USER_WARNING);

        restore_error_handler();
        restore_exception_handler();

        return $ok;
    }

    function handleError($type, $message, $file, $line, $scope, $trace_offset = 0, $log_time = 0)
    {
        $throw = $this->recoverableErrors & $type;
        $log = error_reporting() & $type;

        if ($log || $throw || $scream = $this->scream & $type)
        {
            $log_time || $log_time = microtime(true);

            if ($throw)
            {
                // To prevent extra logging of caught RecoverableErrorException and
                // to remove logged and uncaught exception messages duplication and
                // to dismiss any cryptic "Exception thrown without a stack frame"
                // recoverable errors are logged but only at shutdown time.
                $throw = new RecoverableErrorException($message, 0, $type, $file, $line);
                $scream = self::$shuttingDown ? 1 : $log = 0;
            }

            if (0 <= $trace_offset)
            {
                ++$trace_offset;

                // For duplicate errors, log the trace only once
                $e = md5("{$type}/{$line}/{$file}\x00{$message}", true);

                if (!($this->tracedErrors & $type) || isset($this->loggedTraces[$e])) $trace_offset = -1;
                else if ($log) $this->loggedTraces[$e] = 1;
            }

            if ($log || $scream)
            {
                $e = compact('type', 'message', 'file', 'line');
                $e['level'] = $type . '/' . error_reporting();

                if ($log)
                {
                    if ($this->scopedErrors & $type) null !== $scope && $e['scope'] = $scope;
                    if ($throw && 0 <= $trace_offset) $e['trace'] = $throw->getTrace();
                    else if (0 <= $trace_offset) $e['trace'] = debug_backtrace(false);
                }

                $this->getLogger()->logError($e, $trace_offset, $log_time);
            }

            if ($throw)
            {
                $throw->scope = $scope;
                $log || $throw->traceOffset = $trace_offset;
                throw $throw;
            }
        }

        return (bool) $log;
    }

    function handleException(\Exception $e, $log_time = 0)
    {
        $this->recoverableErrors &= ~E_ERROR; // Prevent any accidental rethrow
        $this->handleError(
            E_ERROR, "Uncaught exception '" . get_class($e) . "'",
            $e->getFile(), $e->getLine(),
            array('uncaught-exception' => $e),
            -1, $log_time
        );
    }

    function handleLastError($e)
    {
        // Handle errors when they have not been logged by the native PHP error handler.
        // If this is the first event, handle it also to log any context data with it.
        // Otherwise, do not duplicate it.
        if (isset($this->logger) && (error_reporting() & $e['type'])) return;
        call_user_func_array(array($this, 'handleError'), $e + array(null, -1));
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
