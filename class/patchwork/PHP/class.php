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


class patchwork_PHP_class
{
    protected static

    $ns2us = array(),
    $us2ns = array();


    static function add($ns, $us)
    {
        self::$ns2us[strtolower($ns)] = $us;
        self::$us2ns[strtolower($us)] = $ns;
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
        is_string($c) && isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        $autoload = class_implements($c, $autoload);
        foreach ($autoload as $c) isset(self::$us2ns[$a = strtolower($c)]) && $autoload[$c] = self::$us2ns[$a];
        return $autoload;
    }

    static function class_parents($c, $autoload = true)
    {
        is_string($c) && isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        $autoload = class_parents($c, $autoload);
        foreach ($autoload as $c) isset(self::$us2ns[$a = strtolower($c)]) && $autoload[$c] = self::$us2ns[$a];
        return $autoload;
    }

    static function class_exists($c, $autoload = true)
    {
        isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        return class_exists($c, $autoload);
    }

    static function get_class_methods($c)
    {
        is_string($c) && isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        return get_class_methods($c);
    }

    static function get_class_vars($c)
    {
        isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        return get_class_vars($c);
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
        is_string($c) && isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        $c = get_parent_class($c);
        isset(self::$us2ns[$a = strtolower($c)]) && $c = self::$us2ns[$a];
        return $c;
    }

    static function interface_exists($c, $autoload = true)
    {
        isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        return interface_exists($c, $autoload);
    }

    static function is_a($o, $c)
    {
        isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        return $o instanceof $c;
    }

    static function is_subclass_of($o, $c)
    {
        isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        is_string($o) && isset(self::$ns2us[$a = ltrim(strtolower($o), '\\')]) && $o = self::$ns2us[$a];
        return is_subclass_of($o, $c);
    }

    static function method_exists($c, $m)
    {
        is_string($c) && isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        return method_exists($c, $m);
    }

    static function property_exists($c, $p)
    {
        is_string($c) && isset(self::$ns2us[$a = ltrim(strtolower($c), '\\')]) && $c = self::$ns2us[$a];
        return property_exists($c, $p);
    }
}
