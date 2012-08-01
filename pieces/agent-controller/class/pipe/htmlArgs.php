<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_htmlArgs
{
    static function php($pool)
    {
        if (!$pool) return '';

        $except = func_get_args();
        $except = array_slice($except, 1);

        $result = '';
        foreach ($pool as $k => $v)
        {
            if ('_' !== substr($k, 0, 1) && 'iteratorPosition' !== $k && false === strpos($k, '$') && !in_array($k, $except))
            {
                $v = (string) $v;
                '' !== $v && $result .= $k . '="' . $v . '" ';
            }
        }

        return $result;
    }

    static function js()
    {
        ?>/*<script>*/

function($pool)
{
    if (!$pool) return '';
    var $result = '', $args = arguments, $i = $args.length, $except = [];

    while (--$i) $except[$i] = $args[$i];
    $except = new RegExp('^(|' + $except.join('|') + ')$');

    for ($i in $pool)
    {
        if ('_' != $i.substr(0, 1) && 'iteratorPosition' != $i && $i.indexOf('$') < 0 && $i.search($except))
        {
            $args = str($pool[$i]);
            if ('' != $args) $result += $i + '="' + $args + '" ';
        }
    }

    return $result;
}

<?php   }
}
