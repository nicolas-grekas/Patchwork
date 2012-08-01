<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_geo
{
    static function php($geo, $latlong)
    {
        if ($geo = (string) $geo)
        {
            $geo = round(100 * $geo);
            $geo = substr($geo, 0, -2) . '.' . substr($geo, -2);
        }
        else $geo = '0.00';

        return $geo;
    }

    static function js()
    {
        ?>/*<script>*/

function($geo, $latlong)
{
    if ($geo = str($geo))
    {
        $geo = '' + Math.round(100*$geo);
        $geo = $geo.substr(0, $geo.length - 2) + '.' + $geo.substr(-2);
    }
    else $geo = '0.00';

    return $geo;
}

<?php   }
}
