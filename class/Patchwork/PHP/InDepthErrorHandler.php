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

/**
 * InDepthErrorHandler logs in depth context info about the errors you want it to.
 *
 * It provides five bit fields that control how errors are handled:
 * - loggedErrors: logged errors, when not @-silenced
 * - screamErrors: never silenced errors
 * - thrownErrors: errors thrown as RecoverableErrorException, when not @-silenced
 * - scopedErrors: errors logged with their local scope
 * - tracedErrors: errors logged with their trace, but only once for repeated errors
 *
 * Errors are logged by a Logger object by default, which provides unprecedented accuracy.
 * Of course, any other logger with the right interface can be injected.
 * Errors are logged to the same file where non catchable errors are written by PHP.
 * Silenced non catchable errors that can be detected at shutdown time are logged
 * when the scream bit field allows so.
 * Uncaught exceptions are logged as E_ERROR.
 * As errors have a performance cost, repeated errors are all logged, so that the developper
 * can see them and weight them as more important to fix than others of the same level.
 */
class InDepthErrorHandler extends ThrowingErrorHandler
{
    protected

    $loggedErrors = -1,     // Log everything
    $screamErrors = 0x1155, // E_RECOVERABLE_ERROR | E_USER_ERROR | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_PARSE
    $thrownErrors = 0x1155, // E_RECOVERABLE_ERROR | E_USER_ERROR | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_PARSE
    $scopedErrors = 0x1303, // E_RECOVERABLE_ERROR | E_USER_ERROR | E_ERROR | E_WARNING | E_USER_WARNING
    $tracedErrors = 0x1303, // E_RECOVERABLE_ERROR | E_USER_ERROR | E_ERROR | E_WARNING | E_USER_WARNING

    $logger,
    $loggedTraces = array(),
    $stackedErrors = array();

    protected static

    $logFile,
    $logStream,
    $shuttingDown = 0,
    $handler = null;


    static function register($handler = null, $log_file = 'php://stderr')
    {
        isset($handler) or $handler = new self;

        // See also http://php.net/error_reporting
        // Formatting errors with html_errors, error_prepend_string or
        // error_append_string only works with displayed errors, not logged ones.
        ini_set('display_errors', false);
        ini_set('log_errors', true);
        ini_set('error_log', $log_file);

        // Some fatal errors can be detected at shutdown time.
        // Then, any fatal error is really fatal: remaining shutdown
        // functions, output buffering handlers or destructors are not called.
        register_shutdown_function(array(__CLASS__, 'shutdown'));

        self::$logFile = $log_file;

        // Register the handler and top it to the current error_reporting() level

        $error_types = error_reporting();
        $error_types &= $handler->loggedErrors | $handler->thrownErrors | $handler->screamErrors;
        $error_types |= E_RECOVERABLE_ERROR;

        set_error_handler(array($handler, 'handleError'), $error_types);
        set_exception_handler(array($handler, 'handleException'));
        return self::$handler = $handler;
    }

    /**
     * Returns the currently registered error handler.
     */
    static function getHandler()
    {
        return self::$handler;
    }

    /**
     * Gets the last uncatchable error and forwards it to ->handleError()
     * when it has not been already logged by PHP's native error handler.
     */
    static function shutdown()
    {
        self::$shuttingDown = 1;

        self::$handler->unstackErrors();

        if ($e = self::getLastError())
        {
            switch ($e['type'])
            {
            case E_ERROR: case E_PARSE:
            case E_CORE_ERROR: case E_CORE_WARNING:
            case E_COMPILE_ERROR: case E_COMPILE_WARNING:
                if (!(error_reporting() & $e['type']))
                    self::$handler->handleError($e['type'], $e['message'], $e['file'], $e['line'], null, -1);
                self::resetLastError();
            }
        }
    }

    static function getLastError()
    {
        $e = error_get_last();
        return empty($e['message']) ? false : $e;
    }

    /**
     * Resets error_get_last() by triggering a silenced empty user notice
     */
    static function resetLastError()
    {
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


    /**
     * Sets the logger and all the bitfields that configure errors' logging.
     */
    function __construct($logger = null, $logged = null, $scream = null, $thrown = null, $scoped = null, $traced = null)
    {
        if (isset($logger)) $this->logger = $logger;
        if (isset($logged)) $this->loggedErrors = $logged;
        if (isset($scream)) $this->screamErrors = $scream;
        if (isset($thrown)) $this->thrownErrors = $thrown;
        if (isset($scoped)) $this->scopedErrors = $scoped;
        if (isset($traced)) $this->tracedErrors = $traced;
    }

    /**
     * Handles errors by filtering then logging them according to the configured bitfields.
     *
     * @param integer $trace_offset The number of noisy items to skip from the current trace or -1 to disable any trace logging.
     * @param float $log_time The microtime(true) when the event has been triggered.
     */
    function handleError($type, $message, $file, $line, $scope, $trace_offset = 0, $log_time = 0)
    {
        if (isset(self::$caughtToStringException))
        {
            $type = self::$caughtToStringException;
            self::$caughtToStringException = null;
            throw $type;
        }

        $log = error_reporting() & $type;
        $throw = $this->thrownErrors & $log;
        $log &= $this->loggedErrors;

        if ($log || $throw || $scream = $this->screamErrors & $type)
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
                $line = 0; // Read $trace_args

                if ($log)
                {
                    if ($this->scopedErrors & $type)
                    {
                        null !== $scope && $e['scope'] = $scope;
                        0 <= $trace_offset && $e['trace'] = debug_backtrace(true); // DEBUG_BACKTRACE_PROVIDE_OBJECT
                        $line = 1;
                    }
                    else if ($throw && 0 <= $trace_offset) $e['trace'] = $throw->getTrace();
                    else if (0 <= $trace_offset) $e['trace'] = debug_backtrace(/*<*/PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_IGNORE_ARGS : false/*>*/);
                }

                $this->getLogger()->logError($e, $trace_offset, $line, $log_time);
            }

            if ($throw)
            {
                if ($this->scopedErrors & $type) $throw->scope = $scope;
                $log || $throw->traceOffset = $trace_offset;
                throw $throw;
            }
        }

        return (bool) $log;
    }

    /**
     * Stack an error for delayed handling.
     *
     * As shown by http://bugs.php.net/42098 and http://bugs.php.net/60724
     * PHP has a compile stage where it behaves unusually. To workaround it,
     * this minimalistic error handler only stacks them for delayed handling.
     *
     * The most important feature of this error handler is to never
     * ever trigger PHP's autoloading mechanism nor any require.
     *
     * @param float $log_time The microtime(true) when the event has been triggered.
     */
    function stackError($type, $message, $file, $line, $scope, $log_time = 0)
    {
        $log = error_reporting() & $type;
        $throw = $this->thrownErrors & $log;
        $log &= $this->loggedErrors;

        if ($log || $throw || $this->screamErrors & $type)
        {
            $log_time || $log_time = microtime(true);
            if (!($this->scopedErrors & $type)) unset($scope);
            $this->stackedErrors[] = array($type, $message, $file, $line, $scope, $log_time);
        }

        return $log || $throw;
    }

    /**
     * Unstacks stacked errors and forward them to ->handleError().
     *
     * @param integer $trace_offset The number of noisy items to skip from the current trace or -1 to disable any trace logging.
     */
    function unstackErrors($trace_offset = 0)
    {
        if (empty($this->stackedErrors)) return;
        if (0 <= $trace_offset) ++$trace_offset;
        $e = $this->stackedErrors;
        $this->stackedErrors = array();
        foreach ($e as $e) $h->handleError($e[0], $e[1], $e[2], $e[3], $e[4], $trace_offset, $e[5]);
    }

    /**
     * Forwards an exception to ->handleError().
     *
     * @param \Exception $e The exception to log.
     * @param float $log_time The microtime(true) when the event has been triggered.
     */
    function handleException(\Exception $e, $log_time = 0)
    {
        $thrown = $this->thrownErrors;
        $scoped = $this->scopedErrors;
        $type = $e instanceof RecoverableErrorException ? $e->getSeverity() : E_ERROR;
        $this->scopedErrors |= $type;
        $this->thrownErrors = 0;
        $this->handleError(
            $type, "Uncaught exception: " . $e->getMessage(),
            $e->getFile(), $e->getLine(),
            array($e),
            -1, $log_time
        );
        $this->thrownErrors = $thrown;
        $this->scopedErrors = $scoped;
    }

    /**
     * Returns the logger used by this error handler
     */
    function getLogger()
    {
        if (isset($this->logger)) return $this->logger;
        if (!isset(self::$logStream))
        {
            self::$logStream = fopen(self::$logFile, 'ab');
            flock(self::$logStream, LOCK_SH); // This shared lock allows readers to wait for the end of the stream.
        }
        return $this->logger = new Logger(self::$logStream, $_SERVER['REQUEST_TIME_FLOAT']);
    }
}

class RecoverableErrorException extends \ErrorException
{
    public $traceOffset = -1, $scope = null;
}
