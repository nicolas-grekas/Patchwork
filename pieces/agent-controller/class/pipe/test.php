<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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


class pipe_test
{
    static function php($test, $ifData, $elseData = '')
    {
        return (string) $test ? $ifData : $elseData;
    }

    static function js()
    {
        ?>/*<script>*/

function($test, $ifData, $elseData)
{
    return num(str($test), 1) ? $ifData : $elseData;
}

<?php   }
}
