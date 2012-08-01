<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_capitalize
{
    static function php($string)
    {
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }

    static function js()
    {
        ?>/*<script>*/

function($string)
{
    $string = str($string).split(/\b/g);

    var $i = $string.length, $b;
    while ($i--)
    {
        if ($i)
        {
            $b = $string[$i-1].substr(-1);
            $b = $b.toUpperCase() == $b.toLowerCase();
        }
        else $b = 1;

        if ($b)
        {
            $b = $string[$i].charAt(0).toUpperCase();
            if ($b != $string[$i].charAt(0)) $string[$i] = $b + $string[$i].substr(1);
        }
    }

    return $string.join('');
}

<?php   }
}
