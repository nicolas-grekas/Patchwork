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


class pipe_inArgs
{
    static function php($index)
    {
        $a = func_get_args();
        return isset($a[$index + 1]) ? $a[$index + 1] : '';
    }

    static function js()
    {
        ?>/*<script>*/

function($index)
{
    return arguments[$index + 1] || '';
}

<?php   }
}
