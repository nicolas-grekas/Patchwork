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


class pipe_linkto
{
    static function php($text, $url = '', $attributes = '')
    {
        $text = Patchwork::string($text);
        $url  = Patchwork::string($url);

        $a = strpos($url, '#');
        if (false !== $a)
        {
            $hash = substr($url, $a);
            $url = substr($url, 0, $a);
        }
        else $hash = '';

        return $url == htmlspecialchars(substr(Patchwork::__HOST__() . substr($_SERVER['REQUEST_URI'], 1), strlen(Patchwork::__BASE__())))
            ? ('<b class="linkloop">' . $text . '</b>')
            : ('<a href="' . Patchwork::base($url, true) . $hash . '" ' . Patchwork::string($attributes) . '>' . $text . '</a>');
    }

    static function js()
    {
        ?>/*<script>*/

function($text, $url, $attributes)
{
    $text = str($text);
    $url = str($url);

    var $a = $url.indexOf('#'), $hash;
    if ($a >= 0)
    {
        $hash = $url.substr($a);
        $url = $url.substr(0, $a);
    }
    else $hash = '';

    return $url == esc(''+location).substr( base('', 1, 1).length )
            ? ('<b class="linkloop">' + $text + '</b>')
            : ('<a href="' + base($url, 1) + $hash + '" ' + str($attributes) + '>' + $text + '</a>');
}

<?php   }
}
