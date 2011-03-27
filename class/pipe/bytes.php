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


class pipe_bytes
{
    static function php($byte)
    {
        $byte = Patchwork::string($byte);

        $suffix = ' Kio';

        if ($byte >= ($div=1073741824)) $suffix = ' Gio';
        else if ($byte >= ($div=1048576)) $suffix = ' Mio';
        else $div = 1024;

        $byte /= $div;
        $div = $byte < 10 ? 100 : 1;
        $byte = intval($div*$byte)/$div;

        return $byte . $suffix;
    }

    static function js()
    {
        ?>/*<script>*/

function($byte)
{
    $byte = str($byte);
    var $suffix = ' Kio', $div;

    if ($byte >= ($div=1073741824)) $suffix = ' Gio';
    else if ($byte >= ($div=1048576)) $suffix = ' Mio';
    else $div = 1024;

    $byte /= $div;
    $div = $byte < 10 ? 100 : 1;
    $byte = parseInt($div*$byte)/$div;

    return $byte + $suffix;
}

<?php   }
}
