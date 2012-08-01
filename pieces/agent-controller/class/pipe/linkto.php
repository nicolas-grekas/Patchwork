<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_linkto
{
    static function php($text, $url = '', $attributes = '')
    {
        $url  = (string) $url;

        $a = strpos($url, '#');
        if (false !== $a)
        {
            $hash = substr($url, $a);
            $url = substr($url, 0, $a);
        }
        else $hash = '';

        return $url == htmlspecialchars(substr(Patchwork::__HOST__() . substr($_SERVER['REQUEST_URI'], 1), strlen(Patchwork::__BASE__())))
            ? ('<b class="linkloop">' . $text . '</b>')
            : ('<a href="' . Patchwork::base($url, true) . $hash . '" ' . $attributes . '>' . $text . '</a>');
    }

    static function js()
    {
        ?>/*<script>*/

function($text, $url, $attributes)
{
    $text = str($text);
    $url = str($url);

    var $a = $url.indexOf('#'), $hash;
    if ($a >= 0)
    {
        $hash = $url.substr($a);
        $url = $url.substr(0, $a);
    }
    else $hash = '';

    return $url == esc(''+location).substr( base('', 1, 1).length )
            ? ('<b class="linkloop">' + $text + '</b>')
            : ('<a href="' + base($url, 1) + $hash + '" ' + str($attributes) + '>' + $text + '</a>');
}

<?php   }
}
