<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_truncate
{
    static function php($string, $length = 80, $etc = '…', $break_words = false)
    {
        $string = (string) $string;
        $length = (string) $length;

        if (!$length) return '';

        if (mb_strlen($string) > $length)
        {
            $length -= mb_strlen($etc);
            if (!$break_words) $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1));

            return mb_substr($string, 0, $length) . $etc;
        }

        return $string;
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $length, $etc, $break_words)
{
    $string = str($string);
    $length = str($length, 80);
    $etc = str($etc, '…');

    if (!$length) return '';

    if ($string.length > $length)
    {
        $length -= $etc.length;
        if (!str($break_words)) $string = $string.substr(0, $length + 1).replace(/\s+?(\S+)?$/g, '');

        return $string.substr(0, $length) + $etc;
    }

    return $string;
}

<?php   }
}
