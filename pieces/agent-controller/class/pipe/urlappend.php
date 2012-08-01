<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_urlappend
{
    static function php($url, $param, $value)
    {
        $url   = (string) $url;
        $param = (string) $param;
        $value = (string) $value;

        $url = preg_replace("/(?:(\?)|&(?:amp;)?){$param}=[^&]+/", '$1', $url);
        $param = rawurlencode($param) . '=' . rawurlencode($value);

        if ('?' !== substr($url, -1))
        {
            $param = (false !== strpos($url, '?') ? '&amp;' : '?') . $param;
        }

        return $url . $param;
    }

    static function js()
    {
        ?>/*<script>*/

function($url, $param, $value)
{
    $url   = str($url);
    $param = str($param);
    $value = str($value);

    $url = $url.replace(new RegExp("(?:(\?)|&(?:amp;)?)" + $param + "=[^&]+"), '$1');
    $param = eUC($param) + '=' + eUC($value);

    if ('?' != $url.substr($url, $url.length-1))
    {
        $param = (-1 != $url.indexOf('?') ? '&amp;' : '?') + $param;
    }

    return $url + $param;
}

<?php   }
}
