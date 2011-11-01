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


class Patchwork_PHP_Override_Php530
{
    protected static $us2ns = array();


    static function add($ns)
    {
        self::$us2ns[strtolower(strtr($ns, '\\', '_'))] = $ns;
    }

    static function class_implements($c, $autoload = true)
    {
        if (is_object($c)) $class = get_class($c);
        else if (!class_exists($c, $autoload) && !interface_exists($c, false))
        {
            user_error(__FUNCTION__ . '(): Class ' . $c . ' does not exist and could not be loaded', E_USER_WARNING);
            return false;
        }
        else $c = self::ns2us($c);

/**/    if (function_exists('class_implements'))
/**/    {
            $autoload = class_implements($c, false);
            foreach ($autoload as $c) isset(self::$us2ns[$a = strtolower($c)]) && $autoload[$c] = self::$us2ns[$a];
            return $autoload;
/**/    }
/**/    else
/**/    {
            return array(); // TODO
/**/    }
    }

    static function class_parents($c, $autoload = true)
    {
        if (is_object($c)) $class = get_class($c);
        else if (!class_exists($c, $autoload) && !interface_exists($c, false))
        {
            user_error(__FUNCTION__ . '(): Class ' . $c . ' does not exist and could not be loaded', E_USER_WARNING);
            return false;
        }
        else $c = self::ns2us($c);

/**/    if (!function_exists('class_parents'))
/**/    {
            $autoload = array();
            while (false !== $class = get_parent_class($class)) $autoload[$class] = $class;
/**/    }
/**/    else
/**/    {
            $autoload = class_parents($c, false);
/**/    }

        foreach ($autoload as $c) isset(self::$us2ns[$a = strtolower($c)]) && $autoload[$c] = self::$us2ns[$a];
        return $autoload;
    }

    static function class_exists($c, $autoload = true)
    {
        return class_exists(self::ns2us($c), $autoload);
    }

    static function get_class_methods($c)
    {
        is_string($c) && $c = self::ns2us($c);
        return get_class_methods($c);
    }

    static function get_class_vars($c)
    {
        return get_class_vars(self::ns2us($c));
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
        is_string($c) && $c = self::ns2us($c);
        $c = get_parent_class($c);
        isset(self::$us2ns[$a = strtolower($c)]) && $c = self::$us2ns[$a];
        return $c;
    }

    static function interface_exists($c, $autoload = true)
    {
        return interface_exists(self::ns2us($c), $autoload);
    }

    static function is_a($o, $c, $allow_string = false)
    {
        $c = self::ns2us($c);

        if ($o instanceof $c) return true;

        if ($allow_string && !is_object($o))
        {
            $o = self::ns2us($o);
            if (is_subclass_of($o, $c)) return true;
            if (0 === strcasecmp($o, $c)) return true;

            // Work around http://bugs.php.net/53727
            if (interface_exists($c, false))
                foreach (self::class_implements($o, false) as $o)
                    if (0 === strcasecmp($o, $c))
                        return true;
        }

        return false;
    }

    static function is_subclass_of($o, $c, $allow_string = true)
    {
        $c = self::ns2us($c);
        if (!self::is_a($o, $c, $allow_string)) return false;
        $o = is_object($o) ? get_class($o) : self::ns2us($o);
        return 0 !== strcasecmp($o, $c);
    }

    static function method_exists($c, $m)
    {
        is_string($c) && $c = self::ns2us($c);
        return method_exists($c, $m);
    }

    static function property_exists($c, $p)
    {
        is_string($c) && $c = self::ns2us($c);
        return property_exists($c, $p);
    }

    static function spl_object_hash($o)
    {
        if (!is_object($o))
        {
            user_error("spl_object_hash() expects parameter 1 to be object, " . gettype($o) . " given", E_USER_WARNING);
            return null;
        }

        isset($o->__spl_object_hash__) || $o->__spl_object_hash__ = md5(mt_rand() . 'spl_object_hash');

        return $o->__spl_object_hash__;
    }

    protected static function ns2us($c)
    {
        $u = strtr($c, '\\', '_');
        return isset($u[0]) && '\\' === $c[0] ? substr($u, 1) : $u;
    }
}
