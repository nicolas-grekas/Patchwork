<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


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
