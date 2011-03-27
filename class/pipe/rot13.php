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


class pipe_rot13
{
    static function php($string)
    {
        return str_rot13(Patchwork::string($string));
    }

    static function js()
    {
        ?>/*<script>*/

function($string)
{
    $string = str($string);

    var $result = '', $len = $string.length, $i = 0, $b;

    for(; $i < $len; ++$i)
    {
        $b = $string.charCodeAt($i);

        if ((64 < $b && $b < 78) || (96 < $b && $b < 110))
        {
            $b += 13;
        }
        else if ((77 < $b && $b < 91) || (109 < $b && $b < 123))
        {
            $b -= 13;
        }

        $result += String.fromCharCode($b);
    }

    return $result;
}

<?php   }
}
