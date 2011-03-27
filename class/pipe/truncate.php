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


class pipe_truncate
{
    static function php($string, $length = 80, $etc = '…', $break_words = false)
    {
        $string = Patchwork::string($string);
        $length = Patchwork::string($length);
        $etc = Patchwork::string($etc);
        $break_words = Patchwork::string($break_words);

        if (!$length) return '';

        if (mb_strlen($string) > $length)
        {
            $length -= mb_strlen($etc);
            if (!$break_words) $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1));

            return mb_substr($string, 0, $length) . $etc;
        }

        return $string;
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $length, $etc, $break_words)
{
    $string = str($string);
    $length = str($length, 80);
    $etc = str($etc, '…');

    if (!$length) return '';

    if ($string.length > $length)
    {
        $length -= $etc.length;
        if (!str($break_words)) $string = $string.substr(0, $length + 1).replace(/\s+?(\S+)?$/g, '');

        return $string.substr(0, $length) + $etc;
    }

    return $string;
}

<?php   }
}
