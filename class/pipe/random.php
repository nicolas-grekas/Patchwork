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


class pipe_random
{
    static function php($min = '', $max = '')
    {
        if ($max === '') $max = 32767;

        $min = (int) Patchwork::string($min);
        $max = (int) Patchwork::string($max);

        return mt_rand($min, $max);
    }

    static function js()
    {
        ?>/*<script>*/

function($min, $max)
{
    if (!t($max)) $max = 32767;

    $min = ($min-0) || 0;
    $max -= 0;

    if ($min > $max)
    {
        var $tmp = $min;
        $min = $max;
        $max = $tmp;
    }

    return $min + parseInt(Math.random() * ($max+1));
}

<?php   }
}
