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


class pipe_substr
{
    static function php($string, $start, $length = null)
    {
        return null === $length
            ? mb_substr($string, (string) $start)
            : mb_substr($string, (string) $start, (string) $length);
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $start, $length)
{
    $string = str($string);

    $start -= 0;
    if ($start < 0) $start += $string.length;
    if ($start < 0) $start = 0;

    $length = t($length) ? +$length : $string.length;
    if ($length < 0) $length += $string.length - $start;

    return $string.substr($start, $length);
}

<?php   }
}
