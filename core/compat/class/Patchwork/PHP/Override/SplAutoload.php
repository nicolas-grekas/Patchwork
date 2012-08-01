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
 * SplAutoload is a PHP implementation of spl_autoload_* functions.
 */
class Patchwork_PHP_Override_SplAutoload
{
    protected static

    $canonicStack = false,
    $loweredStack = false;


    static function spl_autoload_call($class)
    {
        if (false === self::$canonicStack)
        {
            throw new LogicException("Class {$class} could not be loaded");
        }

        $ne = !class_exists($class, false);

        foreach (self::$canonicStack as $c)
        {
            call_user_func($c, $class);
            if ($ne && class_exists($class, false)) return;
        }
    }

    static function spl_autoload_functions()
    {
        return self::$canonicStack;
    }

    static function spl_autoload_register($c = array(__CLASS__, 'spl_autoload'), $throw = true, $prepend = false)
    {
        $l = array(error_reporting(81), array() !== array_map($c, array()));
        error_reporting($l[0]);

        if ($l[1])
        {

            if ($throw) throw new LogicException('Invalid callback');
            else return false;
        }

        $l = $c;

        if (is_string($l)) $l = strtolower($l);
        else if (is_array($l))
        {
            $l[1] = strtolower($l[1]);
            is_string($l[0]) && $l[0] = strtolower($l[0]);
        }

        if (false === self::$canonicStack)
        {
            self::$canonicStack = array($c);
            self::$loweredStack = array($l);
        }
        else if (false === array_search($l, self::$loweredStack, true))
        {
            if ($prepend)
            {
                array_unshift(self::$canonicStack, $c);
                array_unshift(self::$loweredStack, $l);
            }
            else
            {
                array_push(self::$canonicStack, $c);
                array_push(self::$loweredStack, $l);
            }
        }

        return true;
    }

    static function spl_autoload_unregister($c)
    {
        if (false === self::$canonicStack) return false;

        if (is_string($c)) $c = strtolower($c);
        else if (is_array($c) && isset($c[0], $c[1]))
        {
            is_string($c[1]) && $c[1] = strtolower($c[1]);
            is_string($c[0]) && $c[0] = strtolower($c[0]);
        }

        if (false === $c = array_search($c, self::$loweredStack, true)) return false;

        array_splice(self::$canonicStack, $c, 1);
        array_splice(self::$loweredStack, $c, 1);

        return true;
    }

    static function spl_autoload($class)
    {
        user_error("spl_autoload() evades Patchwork's code preprocessing", E_USER_WARNING);
        spl_autoload($class);
    }
}
