<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

isset($_SERVER['REQUEST_TIME_FLOAT']) or $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

/**
 * This function encapsulates a require in its own isolated scope and forces
 * the error reporting level to be always enabled for uncatchable fatal errors.
 * By using it instead of a straight require, you are sure that any otherwise
 * @-silenced fatal error will be reported to you.
 */
function patchwork_require()
{
    try
    {
        Patchwork\PHP\InDepthErrorHandler::stackErrors();
        return Patchwork\PHP\InDepthErrorHandler::unstackErrors(require func_get_arg(0));
    }
    catch (Exception $x)
    {
        Patchwork\PHP\InDepthErrorHandler::unstackErrors();
        throw $x;
    }
}

/**
 * This function should be used instead of register_shutdown_function()
 * so that shutdown functions are always called encapsulated into a try/catch
 * that avoids any "Exception thrown without a stack frame" cryptic error
 * and restores any custom exception handler.
 */
function patchwork_shutdown_register($callback)
{
    if (array() !== @array_map($callback, array())) return register_shutdown_function($callback);
    $callback = func_get_args();
    register_shutdown_function('patchwork_shutdown_call', $callback);
}

/**
 * Do not use this function directly, see above.
 */
function patchwork_shutdown_call($c)
{
    try
    {
        call_user_func_array(array_shift($c), $c);
    }
    catch (Exception $e)
    {
        $c = set_exception_handler('var_dump');
        restore_exception_handler();
        if (null !== $c) call_user_func($c, $e);
        else if (PHP_VERSION_ID >= 50306)
        {
            throw $e;
        }
        else
        {
            user_error(
                "Uncaught exception '" . get_class($e) . "'"
                . ('' !== $e->getMessage() ? " with message '{$e->getMessage()}'" : "" )
                . " in {$e->getFile()}:{$e->getLine()}" . PHP_EOL
                . "Stack trace:" . PHP_EOL
                . "{$e->getTraceAsString()}" . PHP_EOL,
                E_USER_WARNING
            );
        }
        exit(255);
    }
}
