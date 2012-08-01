<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_random
{
    static function php($min = 0, $max = false)
    {
        if (false === $max) $max = '32767';
        return mt_rand((string) $min, (string) $max);
    }

    static function js()
    {
        ?>/*<script>*/

function($min, $max)
{
    if (!t($max)) $max = 32767;

    $min = +$min || 0;
    $max -= 0;

    if ($min > $max)
    {
        var $tmp = $min;
        $min = $max;
        $max = $tmp;
    }

    return $min + parseInt(Math.random() * ($max+1));
}

<?php   }
}
