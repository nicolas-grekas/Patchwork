<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_rot13
{
    static function php($string)
    {
        return str_rot13($string);
    }

    static function js()
    {
        ?>/*<script>*/

function($string)
{
    $string = str($string);

    var $result = '', $len = $string.length, $i = 0, $b;

    for(; $i < $len; ++$i)
    {
        $b = $string.charCodeAt($i);

        if ((64 < $b && $b < 78) || (96 < $b && $b < 110))
        {
            $b += 13;
        }
        else if ((77 < $b && $b < 91) || (109 < $b && $b < 123))
        {
            $b -= 13;
        }

        $result += String.fromCharCode($b);
    }

    return $result;
}

<?php   }
}
