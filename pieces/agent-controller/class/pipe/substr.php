<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_substr
{
    static function php($string, $start, $length = null)
    {
        return null === $length
            ? mb_substr($string, (string) $start)
            : mb_substr($string, (string) $start, (string) $length);
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $start, $length)
{
    $string = str($string);

    $start -= 0;
    if ($start < 0) $start += $string.length;
    if ($start < 0) $start = 0;

    $length = t($length) ? +$length : $string.length;
    if ($length < 0) $length += $string.length - $start;

    return $string.substr($start, $length);
}

<?php   }
}
