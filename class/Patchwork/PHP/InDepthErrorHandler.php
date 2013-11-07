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
 * - screamErrors: never @-silenced errors
 * - thrownErrors: errors thrown as RecoverableErrorException
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
    $tracedErrors = 0x170B, // E_RECOVERABLE_ERROR | E_USER_ERROR | E_ERROR | E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE

    $logger = null,
    $loggedTraces = array();

    protected static

    $handler = null,
    $logFile,
    $logStream,
    $shuttingDown = 0,
    $stackedErrors = array(),
    $stackedErrorLevels = array();


    static function register($handler = null, $log_file = 'php://stderr')
    {
        isset($handler) or $handler = new static;

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

        // Register the handler and top it to the current error_reporting() level.

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
        $e = static::getLastError();
        while (self::$stackedErrorLevels) static::unstackErrors();

        if ($e)
        {
            switch ($e['type'])
            {
            case E_ERROR: case E_PARSE:
            case E_CORE_ERROR: case E_CORE_WARNING:
            case E_COMPILE_ERROR: case E_COMPILE_WARNING:
                if (!(error_reporting() & $e['type']))
                {
                    $h = self::$handler;
                    $t = $h->thrownErrors;
                    $h->thrownErrors = 0;
                    $h->handleError($e['type'], $e['message'], $e['file'], $e['line'], $null, -1);
                    $h->thrownErrors = $t;
                }
                static::resetLastError();
            }
        }
    }

    static function getLastError()
    {
        $e = error_get_last();
        return null !== $e && '' === $e['message'] && E_USER_NOTICE === $e['type'] ? null : $e;
    }

    /**
     * Resets error_get_last() by triggering a silenced empty user notice
     */
    static function resetLastError()
    {
        set_error_handler('var_dump', 0); // Use $error_types = 0 so that the internal error handler is called.
        $e = error_reporting(0);          // Do not use the @-operator as it may be disabled.
        user_error('', E_USER_NOTICE);
        error_reporting($e);
        restore_error_handler();
    }

    /**
     * Sets stacking logger for delayed logging.
     *
     * As shown by http://bugs.php.net/42098 and http://bugs.php.net/60724
     * PHP has a compile stage where it behaves unusually. To workaround it,
     * we plug a logger that only stacks errors for delayed handling.
     *
     * The most important feature of this is to never ever trigger PHP's
     * autoloading nor any require until ::unstackErrors() is called.
     *
     * Ensures also that non-catchable fatal errors are never silenced.
     */
    static function stackErrors()
    {
        self::$stackedErrorLevels[] = error_reporting(error_reporting() | /*<*/E_PARSE | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR/*>*/);
    }

    /**
     * Unstacks stacked errors and forwards them for logging.
     */
    static function unstackErrors($ret = null)
    {
        $e = array_pop(self::$stackedErrorLevels);
        if (isset($e)) error_reporting($e);
        if (!empty(self::$stackedErrorLevels)) return $ret;
        if (empty(self::$stackedErrors)) return $ret;
        $e = self::$stackedErrors;
        self::$stackedErrors = array();
        $l = self::$handler->getLogger();
        foreach ($e as $e) $l->logError($e[0], $e[1], $e[2], $e[3]);
        return $ret;
    }


    /**
     * Sets the logger and all the bitfields that configure errors' logging.
     */
    function __construct($logger = null, $error_levels = array())
    {
        if (isset($logger)) $this->logger = $logger;
        if (isset($error_levels['log']))    $this->loggedErrors = $error_levels['log'];
        if (isset($error_levels['scream'])) $this->screamErrors = $error_levels['scream'];
        if (isset($error_levels['throw']))  $this->thrownErrors = $error_levels['throw'];
        if (isset($error_levels['scope']))  $this->scopedErrors = $error_levels['scope'];
        if (isset($error_levels['trace']))  $this->tracedErrors = $error_levels['trace'];
    }

    /**
     * Handles errors by filtering then logging them according to the configured bitfields.
     *
     * @param int   $trace_offset The number of noisy items to skip from the current trace or -1 to disable any trace logging.
     * @param float $log_time     The microtime(true) when the event has been triggered.
     */
    function handleError($type, $message, $file, $line, &$scope, $trace_offset = 0, $log_time = 0)
    {
        if (isset(self::$caughtToStringException))
        {
            $type = self::$caughtToStringException;
            self::$caughtToStringException = null;
            throw $type;
        }

        $log = $this->loggedErrors & $type & error_reporting();
        $throw = $this->thrownErrors & $type;

        if ($log || $throw || $scream = $this->screamErrors & $type)
        {
            $log_time || $log_time = microtime(true);

            if ($throw)
            {
                // To prevent extra logging of caught RecoverableErrorException and
                // to remove logged and uncaught exception messages duplication and
                // to dismiss any cryptic "Exception thrown without a stack frame"
                // recoverable errors are logged but only at shutdown time.
                $throw = new InDepthRecoverableErrorException($message, 0, $type, $file, $line);
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
                $trace_args = 0;

                if ($log)
                {
                    if ($this->scopedErrors & $type)
                    {
                        null !== $scope && $e['scope'] =& $scope;
                        0 <= $trace_offset && $e['trace'] = debug_backtrace(true); // DEBUG_BACKTRACE_PROVIDE_OBJECT
                        $trace_args = 1;
                    }
                    else if ($throw && 0 <= $trace_offset) $e['trace'] = $throw->getTrace();
                    else if (0 <= $trace_offset) $e['trace'] = debug_backtrace(/*<*/PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_IGNORE_ARGS : false/*>*/);
                }

                if (self::$stackedErrorLevels) self::$stackedErrors[] = array($e, $trace_offset, $trace_args, $log_time);
                else $this->getLogger()->logError($e, $trace_offset, $trace_args, $log_time);
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
     * Forwards an exception to ->handleError().
     *
     * @param \Exception $e        The exception to log.
     * @param float      $log_time The microtime(true) when the event has been triggered.
     */
    function handleException(\Exception $e, $log_time = 0)
    {
        $type = $e instanceof RecoverableErrorException ? $e->getSeverity() : E_ERROR;

        $thrown = $this->thrownErrors;
        $this->thrownErrors = 0;

        $scoped = $this->scopedErrors;
        if ($this->tracedErrors & $type) $this->scopedErrors |= $type;

        $this->handleError(
            $type,
            "Uncaught exception: " . $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e,
            -1,
            $log_time
        );

        $this->scopedErrors = $scoped;
        $this->thrownErrors = $thrown;
    }

    /**
     * Returns the logger used by this error handler.
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

class InDepthRecoverableErrorException extends RecoverableErrorException
{
    public $traceOffset = -1, $scope = null;
}
