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


class patchwork_alias_spl_autoload
{
    protected static $stack = false;


    static function call($class)
    {
        if (false === self::$stack)
        {
            throw new LogicException("Class {$class} could not be loaded");
        }

        $ne = !class_exists($class, false);

        foreach (self::$stack as $c)
        {
            call_user_func($c, $class);
            if ($ne && class_exists($class, false)) return;
        }
    }

    static function functions()
    {
        return self::$stack;
    }

    static function register($callback, $throw = true, $prepend = false)
    {
        if (array() !== @array_map($callback, array()))
        {
            if ($throw) throw new LogicException('Invalid callback');
            else return false;
        }

        if (false === self::$stack)
        {
            self::$stack = array($callback);
        }
        else if (false === array_search($callback, self::$stack, true))
        {
            $prepend
                ? array_unshift(self::$stack, $callback)
                : array_push   (self::$stack, $callback);
        }

        return true;
    }

    static function unregister($callback)
    {
        if (false !== self::$stack && false !== $i = array_search($callback, self::$stack, true))
        {
            array_splice(self::$stack, $i, 1);
            return true;
        }

        return false;
    }
}

/**/if (!function_exists('spl_autoload_call'))
/**/{
/**/    if (!class_exists('LogicException'))
/**/    {
            class LogicException extends Exception {}
/**/    }

        function spl_autoload_call($class)          {return patchwork_alias_spl::spl_autoload_call($class);}
        function spl_autoload_functions()           {return patchwork_alias_spl::spl_autoload_functions();}
        function spl_autoload_register($callback, $throw = true, $prepend = false) {return patchwork_alias_spl::spl_autoload_register($callback, $throw, $prepend);}
        function spl_autoload_unregister($callback) {return patchwork_alias_spl::spl_autoload_unregister($callback);}
/**/}
