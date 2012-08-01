<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


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
