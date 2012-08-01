<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_indent
{
    static function php($string, $chars = 4, $char = ' ')
    {
        $chars = str_repeat($char, (string) $chars);

        return $chars . str_replace("\n", "\n$chars", $string);
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $chars, $char)
{
    $string = str($string);
    $chars = str($chars, 4);
    $char = str($char, ' ');

    var $char_repeated = $char;
    while (--$chars) $char_repeated += $char;

    return $char_repeated + $string.replace(/\n/g, '\n' + $char_repeated);
}

<?php   }
}
