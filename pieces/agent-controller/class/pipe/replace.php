<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_replace
{
    static function php($string, $search, $replace, $caseInsensitive = false)
    {
        $search = preg_replace("/(?<!\\\\)((?:\\\\\\\\)*)@/", '$1\\@', (string) $search);
        $caseInsensitive = (string) $caseInsensitive ? 'i' : '';
        return preg_replace("@{$search}@su{$caseInsensitive}", $replace, $string);
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $search, $replace, $caseInsensitive)
{
    $search = new RegExp(str($search), 'g' + (str($caseInsensitive) ? 'i' : ''));
    return str($string).replace($search, str($replace));
}

<?php   }
}
