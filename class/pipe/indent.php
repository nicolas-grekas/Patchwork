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


class pipe_indent
{
    static function php($string, $chars = 4, $char = ' ')
    {
        $chars = str_repeat(Patchwork::string($char), Patchwork::string($chars));

        return $chars . str_replace("\n", "\n$chars", Patchwork::string($string));
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $chars, $char)
{
    $string = str($string);
    $chars = str($chars, 4);
    $char = str($char, ' ');

    var $char_repeated = $char;
    while (--$chars) $char_repeated += $char;

    return $char_repeated + $string.replace(/\n/g, '\n' + $char_repeated);
}

<?php   }
}
