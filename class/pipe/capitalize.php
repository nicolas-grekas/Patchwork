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


class pipe_capitalize
{
    static function php($string)
    {
        return mb_convert_case(Patchwork::string($string), MB_CASE_TITLE, 'UTF-8');
    }

    static function js()
    {
        ?>/*<script>*/

function($string)
{
    $string = str($string).split(/\b/g);

    var $i = $string.length, $b;
    while ($i--)
    {
        if ($i)
        {
            $b = $string[$i-1].substr(-1);
            $b = $b.toUpperCase() == $b.toLowerCase();
        }
        else $b = 1;

        if ($b)
        {
            $b = $string[$i].charAt(0).toUpperCase();
            if ($b != $string[$i].charAt(0)) $string[$i] = $b + $string[$i].substr(1);
        }
    }

    return $string.join('');
}

<?php   }
}
