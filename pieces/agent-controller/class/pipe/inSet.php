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


class pipe_inSet
{
    static function php($index, $set)
    {
        $set = explode(mb_substr($set, 0, 1), $set);
        return isset($set[$index + 1]) ? $set[$index + 1] : '';
    }

    static function js()
    {
        ?>/*<script>*/

function($index, $set)
{
    $set = $set.split($set.charAt(0));
    return $set[$index + 1] || '';
}

<?php   }
}
