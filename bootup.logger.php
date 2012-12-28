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
 * This class is a helper for plugging Patchwork\PHP\ErrorHandler as your main error
 * and exception reporting handler. It gives you the hooks needed to configure which
 * and where errors are written and adds a log() static method that can be used
 * in your code to log your own messages/data in the same debug stream.
 */
class MyLogger extends Patchwork\PHP\ErrorHandler
{
    static function start($log_file = 'php://stderr', parent $handler = null)
    {
        if (null === $handler)
        {
            $handler = new self;
    
            // Configure your error handler here
            error_reporting(E_ALL | E_STRICT);
            $handler->scream = -1; // Do not silence any error (amongst the catchable ones)
        }

        return parent::start($log_file, $handler);
    }

    function getLogger()
    {
        if (isset($this->logger)) return $this->logger;
        $logger = parent::getLogger();

        // Configure your logger here
        $logger->loggedGlobals = array(); // Do not log any global with the first event (default is '_SERVER')

        return $logger;
    }

    static function log($message, $data)
    {
        self::getHandler()->getLogger()->log($message, $data);
    }
}

/**
 * This function encapsulates a require in its own isolated scope and forces
 * the error reporting level to be always enabled for uncatchable fatal errors.
 * By using it instead of a straight require, you are sure that any otherwise
 * @-silenced fatal error will be reported to you.
 */
function patchwork_require($file)
{
    $h = MyLogger::getHandler();
    set_error_handler(array($h, 'stackError'));
    $e = error_reporting(error_reporting() | E_PARSE | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);

    try {$file = patchwork_require_empty_scope($file);}
    catch (Exception $file) {$x = true;}

    error_reporting($e);
    restore_error_handler();
    $h->unstackErrors();

    if (isset($x)) throw $file;
    else return $file;
}

/**
 * This function encapsulates a require in its own isolated scope free from any variable.
 * By using it instead of a straight require, you are sure that variables inside the required
 * file can not become global inadvertently nor collide with the current local scope.
 * Using the above patchwork_require() instead of this one is recommended in the general case.
 */
function patchwork_require_empty_scope()
{
    return require func_get_arg(0);
}

/**
 * This function should be used instead of register_shutdown_function()
 * so that shutdown functions are always called encapsulated into a try/catch
 * that avoids any "Exception thrown without a stack frame" cryptic error.
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
        else user_error("Uncaught exception '" . get_class($e) . "' with message '{$e->getMessage()}' in {$e->getFile()} on line {$e->getLine()}", E_USER_WARNING);
        exit(255);
    }
}
