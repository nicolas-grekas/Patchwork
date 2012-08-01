<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_htmlArgsList
{
    static function php()
    {
        $a = func_get_args();
        count($a) % 2 && $a[] = '';
        $len = count($a);

        $result = '';
        for ($i = 0; $i < $len; $i += 2)
        {
            $v = (string) $a[$i+1];
            '' !== $v && $result .= (string) $a[$i] . '="' . $v . '" ';
        }

        return $result;
    }

    static function js()
    {
        ?>/*<script>*/

function()
{
    var $result = '', $a = arguments, $i = 0, $v;

    for ($i = 0; $i < $a.length; $i += 2)
    {
        $v = str($a[$i+1]);
        if ('' != $v) $result += str($a[$i]) + '="' + $v + '" ';
    }

    return $result;
}

<?php   }
}
