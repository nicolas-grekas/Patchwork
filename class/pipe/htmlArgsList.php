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


class pipe_htmlArgsList
{
    static function php()
    {
        $a = func_get_args();
        count($a) % 2 && $a[] = '';
        $len = count($a);

        $result = '';
        for ($i = 0; $i < $len; $i += 2)
        {
            $v = Patchwork::string($a[$i+1]);
            '' !== $v && $result .= Patchwork::string($a[$i]) . '="' . $v . '" ';
        }

        return $result;
    }

    static function js()
    {
        ?>/*<script>*/

function()
{
    var $result = '', $a = arguments, $i = 0, $v;

    for ($i = 0; $i < $a.length; $i += 2)
    {
        $v = str($a[$i+1]);
        if ('' != $v) $result += str($a[$i]) + '="' + $v + '" ';
    }

    return $result;
}

<?php   }
}
