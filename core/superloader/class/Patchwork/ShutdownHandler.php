<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

/**
 * ShutdownHandler modifies how shutdown time behaves for greater control.
 *
 * The modified shutdown sequence is:
 * - release any remaining output buffering handler
 * - call fastcgi_finish_request() when available
 * - call registered shutdown functions, encapsulated into a try/catch that
 *   avoids any "Exception thrown without a stack frame" cryptic error
 * - call session_write_close()
 * - call static destructors
 *
 * As usual, global and static objects' destructors are triggered after the last step.
 * Any output done during the shutdown sequence is cancelled with a warning.
 *
 * Because shutdown time is special, the methods of this class must remain fully public and static.
 */
class Patchwork_ShutdownHandler
{
    static $destructors = array();

    protected static $class = null;


    static function setup()
    {
        isset(self::$class) || self::$class = __CLASS__;
        self::register(array(self::$class, '_start'));
    }

    static function register($callback)
    {
        if (array() !== @array_map($callback, array())) return register_shutdown_function($callback);

        $callback = func_get_args();
        register_shutdown_function(array(self::$class, '_call'), $callback);
    }

    // Protected methods

    static function _call($c)
    {
        try
        {
            if (__CLASS__ !== self::$class) array_unshift($c, array(self::$class, __FUNCTION__));
            call_user_func_array(array_shift($c), $c);
        }
        catch (Exception $e)
        {
            $c = set_exception_handler('var_dump');
            restore_exception_handler();
            if (null !== $c) call_user_func($c, $e);
            else user_error("Uncaught exception '" . get_class($e) . "' in {$e->getFile()}:{$e->getLine()}", E_USER_WARNING);
            exit(255);
        }
    }

    static function _start()
    {
        // See http://bugs.php.net/54114
        while (ob_get_level() && ob_end_flush()) {}
        ob_start(array(self::$class, '_checkOutputBuffer'));

/**/    if (function_exists('fastcgi_finish_request'))
            fastcgi_finish_request();

        self::register(array(self::$class, '_end'));
    }

    static function _checkOutputBuffer($buffer)
    {
        if ('' !== $buffer) user_error("Cancelling shutdown time output", E_USER_WARNING);
        return '';
    }

    static function _end()
    {
        // See http://bugs.php.net/54157
        self::register('session_write_close');
        self::register(array(self::$class, '_callStaticDestructors'));
    }

    static function _callStaticDestructors()
    {
        if (empty(self::$destructors))
        {
            while (ob_get_level() && ob_end_flush()) {}
            ob_start(array(self::$class, '_checkOutputBuffer'));
        }
        else
        {
            call_user_func(array(array_shift(self::$destructors), '__free'));
            self::register(array(self::$class, __FUNCTION__));
        }
    }
}
