<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_echo
{
    protected static $args;

    static function php($format = '')
    {
        $args = func_get_args();

        if ('' !== $format) 
        {
            self::$args = $args;
            $args = preg_replace_callback(
                "'(%+)([0-9]?)'",
                array(__CLASS__, 'replace_callback'),
                $format
            );
            self::$args = null;
        }
        else $args = implode('', $args);

        return $args;
    }

    protected static function replace_callback($m)
    {
        if (1 === strlen($m[1]) % 2)
        {
            $m[1] = substr($m[1], 1);
            $m[2] = '' !== $m[2] ? (isset(self::$args[$m[2]+1]) ? (string) self::$args[$m[2]+1] : '') : '%';
        }

        return substr($m[1], 0, strlen($m[1])>>1) . $m[2];
    }

    static function js()
    {
        ?>/*<script>*/

function($format)
{
    var $i = 1, $args = arguments;
    $format = str($format);

    if ($format != '')
    {
        $i = function($m0, $m1, $m2)
        {
            if (1 == $m1.length % 2)
            {
                $m1 = $m1.substr(1);
                $m2 = '' != $m2 ? str($args[+$m2+1]) : '%';
            }

            return $m1.substr(0, $m1.length>>1) + $m2;
        }

        $format = $format.replace(/(%+)([0-9]?)/g, $i);
    }
    else
    {
        $format = [];
        for (; $i<$args.length; ++$i) $format[$i] = $args[$i];
        $format = $format.join('');
    }

    return $format;
}

<?php   }
}
