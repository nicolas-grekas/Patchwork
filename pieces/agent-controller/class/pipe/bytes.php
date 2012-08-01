<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_bytes
{
    static function php($byte)
    {
        $byte = (string) $byte;

        $suffix = ' Kio';

        if ($byte >= ($div=1073741824)) $suffix = ' Gio';
        else if ($byte >= ($div=1048576)) $suffix = ' Mio';
        else $div = 1024;

        $byte /= $div;
        $div = $byte < 10 ? 100 : 1;
        $byte = intval($div*$byte)/$div;

        return $byte . $suffix;
    }

    static function js()
    {
        ?>/*<script>*/

function($byte)
{
    $byte = str($byte);
    var $suffix = ' Kio', $div;

    if ($byte >= ($div=1073741824)) $suffix = ' Gio';
    else if ($byte >= ($div=1048576)) $suffix = ' Mio';
    else $div = 1024;

    $byte /= $div;
    $div = $byte < 10 ? 100 : 1;
    $byte = parseInt($div*$byte)/$div;

    return $byte + $suffix;
}

<?php   }
}
