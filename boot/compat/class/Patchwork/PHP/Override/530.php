<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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


class Patchwork_PHP_Override_530
{
    protected static $us2ns = array();


    static function add($ns)
    {
        self::$us2ns[strtolower(strtr($ns, '\\', '_'))] = $ns;
    }

    static function class_implements($c, $autoload = true)
    {
        is_string($c) && $c = strtr(ltrim($c, '\\'), '\\', '_');
        $autoload = class_implements($c, $autoload);
        foreach ($autoload as $c) isset(self::$us2ns[$a = strtolower($c)]) && $autoload[$c] = self::$us2ns[$a];
        return $autoload;
    }

    static function class_parents($c, $autoload = true)
    {
        is_string($c) && $c = strtr(ltrim($c, '\\'), '\\', '_');
        $autoload = class_parents($c, $autoload);
        foreach ($autoload as $c) isset(self::$us2ns[$a = strtolower($c)]) && $autoload[$c] = self::$us2ns[$a];
        return $autoload;
    }

    static function class_exists($c, $autoload = true)
    {
        return class_exists(strtr(ltrim($c, '\\'), '\\', '_'), $autoload);
    }

    static function get_class_methods($c)
    {
        is_string($c) && $c = strtr(ltrim($c, '\\'), '\\', '_');
        return get_class_methods($c);
    }

    static function get_class_vars($c)
    {
        return get_class_vars(strtr(ltrim($c, '\\'), '\\', '_'));
    }

    static function get_class($c)
    {
        $c = get_class($c);
        isset(self::$us2ns[$a = strtolower($c)]) && $c = self::$us2ns[$a];
        return $c;
    }

    static function get_declared_classes()
    {
        $d = get_declared_classes();
        foreach ($d as $c) isset(self::$us2ns[$a = strtolower($c)]) && $d[] = self::$us2ns[$a];
        return $d;
    }

    static function get_declared_interfaces()
    {
        $d = get_declared_interfaces();
        foreach ($d as $c) isset(self::$us2ns[$a = strtolower($c)]) && $d[] = self::$us2ns[$a];
        return $d;
    }

    static function get_parent_class($c)
    {
        is_string($c) && $c = strtr(ltrim($c, '\\'), '\\', '_');
        $c = get_parent_class($c);
        isset(self::$us2ns[$a = strtolower($c)]) && $c = self::$us2ns[$a];
        return $c;
    }

    static function interface_exists($c, $autoload = true)
    {
        return interface_exists(strtr(ltrim($c, '\\'), '\\', '_'), $autoload);
    }

    static function is_a($o, $c, $allow_string = false)
    {
        $c = strtr(ltrim($c, '\\'), '\\', '_');

        if ($o instanceof $c) return true;

        if ($allow_string && !is_object($o))
        {
            $o = strtr(ltrim($o, '\\'), '\\', '_');
            if (is_subclass_of($o, $c)) return true;
            if (0 === strcasecmp(ltrim($o, '\\'), ltrim($c, '\\'))) return true;

            // Work around http://bugs.php.net/53727
            if (interface_exists($c, false))
                foreach (class_implements($o, false) as $o)
                    if (0 === strcasecmp($o, ltrim($c, '\\')))
                        return true;
        }

        return false;
    }

    static function is_subclass_of($o, $c, $allow_string = true)
    {
        $c = strtr(ltrim($c, '\\'), '\\', '_');
        if (!self::is_a($o, $c, $allow_string)) return false;
        $o = is_object($o) ? get_class($o) : strtr(ltrim($o, '\\'), '\\', '_');
        return 0 !== strcasecmp($o, $c);
    }

    static function method_exists($c, $m)
    {
        is_string($c) && $c = strtr(ltrim($c, '\\'), '\\', '_');
        return method_exists($c, $m);
    }

    static function property_exists($c, $p)
    {
        is_string($c) && $c = strtr(ltrim($c, '\\'), '\\', '_');
        return property_exists($c, $p);
    }
}
