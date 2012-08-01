<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_spacify
{
    static function php($string, $spacify_char = ' ')
    {
        preg_match_all('/./u', $string, $string);
        return implode($spacify_char, $string[0]);
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $spacify_char)
{
    return str($string).split('').join(str($spacify_char, ' '));
}

<?php   }
}
