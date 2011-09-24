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


class pipe_allowhtml
{
    static function php($string, $urlInterpolation = false)
    {
        $string = (string) $string;

        false !== strpos($string, '&') && $string = str_replace(
            array('&#039;', '&quot;', '&gt;', '&lt;', '&amp;'),
            array("'"     , '"'     , '>'   , '<'   , '&'),
            $string
        );

        $urlInterpolation && false !== strpos($string, '{') && $string = str_replace(
            array('{/}'        , '{~}'),
            array(Patchwork::__HOST__(), Patchwork::__BASE__()),
            $string
        );

        return $string;
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $urlInterpolation)
{
    var $base = base();

    $string = str($string);

    return +$string + '' == $string
        ? +$string
        : (
            $urlInterpolation
            ? unesc($string).replace(/{\/}/g, $base.substr(0, $base.indexOf('/', 8)+1)).replace(/{~}/g, $base)
            : unesc($string)
        );
}

<?php   }
}
