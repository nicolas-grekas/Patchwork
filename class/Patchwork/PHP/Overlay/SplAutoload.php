<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Overlay_SplAutoload
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

    static function spl_autoload_register($c, $throw = true, $prepend = false)
    {
        if (array() !== @array_map($c, array()))
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
        if (false !== self::$canonicStack) return false;

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
}

/**/if (!function_exists('spl_autoload_call'))
/**/{
/**/    if (!class_exists('LogicException'))
/**/    {
            class LogicException extends Exception {}
/**/    }

        function spl_autoload_call($class)          {return Patchwork_PHP_Overlay_SplAutoload::spl_autoload_call($class);}
        function spl_autoload_functions()           {return Patchwork_PHP_Overlay_SplAutoload::spl_autoload_functions();}
        function spl_autoload_unregister($callback) {return Patchwork_PHP_Overlay_SplAutoload::spl_autoload_unregister($callback);}
        function spl_autoload_register($callback, $throw = true, $prepend = false) {return Patchwork_PHP_Overlay_SplAutoload::spl_autoload_register($callback, $throw, $prepend);}
/**/}
