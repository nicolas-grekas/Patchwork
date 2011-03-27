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


class pipe_now
{
    static function php($string)
    {
        Patchwork::setMaxage(1);
        Patchwork::setExpires('onmaxage');
        return $_SERVER['REQUEST_TIME'];
    }

    static function js()
    {
        ?>/*<script>*/

function()
{
    return parseInt(new Date/1000);
}

<?php   }
}
