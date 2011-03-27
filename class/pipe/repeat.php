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


class pipe_repeat
{
    static function php($string, $num)
    {
        return str_repeat(Patchwork::string($string), Patchwork::string($num));
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $num)
{
    var $str = '';
    $string = str($string);
    while (--$num>=0) $str += $string;
    return $str;
}

<?php   }
}
