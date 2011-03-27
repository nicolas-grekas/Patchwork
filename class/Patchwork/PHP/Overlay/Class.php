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


class Patchwork_PHP_Overlay_Class
{
    protected static $us2ns = array();


    static function add($ns)
    {
        self::$us2ns[strtolower(strtr($ns, '\\', '_'))] = $ns;
    }

    static function spl_object_hash($o)
    {
        if (!is_object($o))
        {
            trigger_error("spl_object_hash() expects parameter 1 to be object, " . gettype($o) . " given", E_USER_WARNING);
            return null;
        }

        isset($o->__spl_object_hash__) || $o->__spl_object_hash__ = md5(mt_rand() . 'spl_object_hash');

        return $o->__spl_object_hash__;
    }

    static function class_implements($c, $autoload = true)
    {
        is_string($c) && $c = strtr(ltrim($c), '\\', '_');
        $autoload = class_implements($c, $autoload);
        foreach ($autoload as $c) isset(self::$us2ns[$a = strtolower($c)]) && $autoload[$c] = self::$us2ns[$a];
        return $autoload;
    }

    static function class_parents($c, $autoload = true)
    {
        is_string($c) && $c = strtr(ltrim($c), '\\', '_');
        $autoload = class_parents($c, $autoload);
        foreach ($autoload as $c) isset(self::$us2ns[$a = strtolower($c)]) && $autoload[$c] = self::$us2ns[$a];
        return $autoload;
    }

    static function class_exists($c, $autoload = true)
    {
        return class_exists(strtr(ltrim($c), '\\', '_'), $autoload);
    }

    static function get_class_methods($c)
    {
        is_string($c) && $c = strtr(ltrim($c), '\\', '_');
        return get_class_methods($c);
    }

    static function get_class_vars($c)
    {
        return get_class_vars(strtr(ltrim($c), '\\', '_'));
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
        is_string($c) && $c = strtr(ltrim($c), '\\', '_');
        $c = get_parent_class($c);
        isset(self::$us2ns[$a = strtolower($c)]) && $c = self::$us2ns[$a];
        return $c;
    }

    static function interface_exists($c, $autoload = true)
    {
        return interface_exists(strtr(ltrim($c), '\\', '_'), $autoload);
    }

    static function is_a($o, $c)
    {
        $c = strtr(ltrim($c), '\\', '_');
        return $o instanceof $c;
    }

    static function is_subclass_of($o, $c)
    {
        is_string($o) && $o = strtr(ltrim($o), '\\', '_');
        return is_subclass_of($o, strtr(ltrim($c), '\\', '_'));
    }

    static function method_exists($c, $m)
    {
        is_string($c) && $c = strtr(ltrim($c), '\\', '_');
        return method_exists($c, $m);
    }

    static function property_exists($c, $p)
    {
        is_string($c) && $c = strtr(ltrim($c), '\\', '_');
        return property_exists($c, $p);
    }
}
