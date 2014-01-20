<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork;

/**
 * ShutdownHandler modifies how shutdown time behaves for greater control.
 *
 * The modified shutdown sequence is:
 * - release any remaining output buffering handler
 * - call fastcgi_finish_request() when available
 * - call registered shutdown functions, encapsulated into a try/catch that
 *   avoids any "Exception thrown without a stack frame" cryptic error
 * - call static destructors
 * - call session_write_close()
 *
 * As usual, global and static objects' destructors are triggered after the last step.
 * Any output done during the shutdown sequence is cancelled with a warning.
 *
 * Because shutdown time is special, the methods of this class must remain fully public and static.
 */

class ShutdownHandler
{
    static $destructors = array();

    protected static $class = null;


    static function setup()
    {
        isset(self::$class) || self::$class = __CLASS__;
        self::$class .= '::';
        self::register(self::$class . '_start');
    }

    static function register($callback)
    {
        if (array() !== @array_map($callback, array())) return register_shutdown_function($callback);

        $callback = func_get_args();
        register_shutdown_function(self::$class . '_call', $callback);
    }

    // Protected methods

    static function _call($c)
    {
        try
        {
            if (__CLASS__ . '::' !== self::$class) array_unshift($c, self::$class . __FUNCTION__);
            call_user_func_array(array_shift($c), $c);
        }
        catch (\Exception $e)
        {
            $c = set_exception_handler('var_dump');
            restore_exception_handler();
            if (null !== $c) call_user_func($c, $e);
            else
            {
/**/            if (PHP_VERSION_ID >= 50306)
/**/            {
                    throw $e;
/**/            }
/**/            else
/**/            {
                    user_error(
                        "Uncaught exception '" . get_class($e) . "'"
                        . ('' !== $e->getMessage() ? " with message '{$e->getMessage()}'" : "" )
                        . " in {$e->getFile()}:{$e->getLine()}" . PHP_EOL
                        . "Stack trace:" . PHP_EOL
                        . "{$e->getTraceAsString()}" . PHP_EOL,
                        E_USER_WARNING
                    );
/**/            }
            }
            exit(255);
        }
    }

    static function _start()
    {
        if (self::_flushOutputBuffers())
        {
           if (function_exists('fastcgi_finish_request'))
                fastcgi_finish_request();
            else
                flush();
        }

        ob_start(self::$class . '_checkOutputBuffer');
        self::register(self::$class . '_end');
    }

    static function _flushOutputBuffers()
    {
        // See http://bugs.php.net/54114

        $l = ob_get_level();

        while ( $l--
            && ($s = ob_get_status())
            && ( ! empty($s['del'])
              || (isset($s['flags']) && ($s['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) ) )
        {
            if (! isset($s['flags']) || ($s['flags'] & PHP_OUTPUT_HANDLER_FLUSHABLE))
            {
                ob_end_flush();
            }
            else if ($s['flags'] & PHP_OUTPUT_HANDLER_CLEANABLE)
            {
                ob_end_clean();
            }
            else break;
        }

        return ! $s;
    }

    static function _checkOutputBuffer($buffer)
    {
        if ('' !== $buffer) user_error("Cancelling shutdown time output", E_USER_WARNING);
        return '';
    }

    static function _end()
    {
        self::register(self::$class . '_callStaticDestructors');
    }

    static function _callStaticDestructors()
    {
        if (empty(self::$destructors))
        {
            self::_flushOutputBuffers();

            session_write_close(); // See http://bugs.php.net/54157

            ob_start('gc_disable'); // See http://news.php.net/php.internals/67735
            ob_start(self::$class . '_checkOutputBuffer');

            if (empty(self::$destructors)) return;
        }
        else
        {
            call_user_func(array(array_shift(self::$destructors), '__free'));
        }

        self::register(self::$class . __FUNCTION__);
    }
}
