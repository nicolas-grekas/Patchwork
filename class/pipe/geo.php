<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class pipe_geo
{
    static function php($geo, $latlong)
    {
        if ($geo = Patchwork::string($geo))
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
