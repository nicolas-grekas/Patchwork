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


class pipe_replace
{
    static function php($string, $search, $replace, $caseInsensitive = false)
    {
        $search = preg_replace("/(?<!\\\\)((?:\\\\\\\\)*)@/", '$1\\@', (string) $search);
        $caseInsensitive = (string) $caseInsensitive ? 'i' : '';
        return preg_replace("@{$search}@su{$caseInsensitive}", $replace, $string);
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $search, $replace, $caseInsensitive)
{
    $search = new RegExp(str($search), 'g' + (str($caseInsensitive) ? 'i' : ''));
    return str($string).replace($search, str($replace));
}

<?php   }
}
