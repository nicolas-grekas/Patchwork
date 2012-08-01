<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_rgb
{
    static function php($r, $g, $b)
    {
        $r = (string) $r - 0;
        $g = (string) $g - 0;
        $b = (string) $b - 0;

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    static function js()
    {
        ?>/*<script>*/

function($r, $g, $b)
{
    $r = (+$r || 0).toString(16);
    $g = (+$g || 0).toString(16);
    $b = (+$b || 0).toString(16);

    if ($r.length < 2) $r = '0' + $r;
    if ($g.length < 2) $g = '0' + $g;
    if ($b.length < 2) $b = '0' + $b;

    return '#' + $r + $g + $b;
}

<?php   }
}
