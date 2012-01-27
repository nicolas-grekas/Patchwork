<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
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
    static function php($min = 0, $max = false)
    {
        if (false === $max) $max = '32767';
        return mt_rand((string) $min, (string) $max);
    }

    static function js()
    {
        ?>/*<script>*/

function($min, $max)
{
    if (!t($max)) $max = 32767;

    $min = +$min || 0;
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
