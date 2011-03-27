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


// This pipe is duplicated in js/w

class pipe_base
{
    static function php($string = '', $noId = false)
    {
        return Patchwork::base(Patchwork::string($string), $noId);
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $noId)
{
    return base(str($string), $noId);
}

<?php   }
}
