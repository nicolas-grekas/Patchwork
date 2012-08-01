<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_PHP_Override_Php539
{
    static function is_a($o, $c, $allow_string = false)
    {
        if ($o instanceof $c) return true;

        if ($allow_string && !is_object($o))
        {
            if (is_subclass_of($o, $c)) return true;
            if (0 === strcasecmp($o = self::nsTrim($o), $c = self::nsTrim($c))) return true;

            // Work around http://bugs.php.net/53727
            if (interface_exists($c, false))
                foreach (class_implements($o, false) as $o)
                    if (0 === strcasecmp($o, $c))
                        return true;
        }

        return false;
    }

    static function is_subclass_of($o, $c, $allow_string = true)
    {
        if (!self::is_a($o, $c, $allow_string)) return false;
        is_object($o) && $o = get_class($o);
        return 0 !== strcasecmp(self::nsTrim($o), self::nsTrim($c));
    }

    protected static function nsTrim($c)
    {
        $c = (string) $c;
        return isset($c[0]) && '\\' === $c[0] ? substr($c, 1) : $c;
    }
}
