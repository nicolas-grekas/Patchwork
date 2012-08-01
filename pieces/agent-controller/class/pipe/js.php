<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_js
{
    static function php($string, $forceString = false)
    {
        $string = (string) $string;

        false !== strpos($string, '&') && $string = str_replace(
            array('&#039;', '&quot;', '&gt;', '&lt;', '&amp;'),
            array("'"     , '"'     , '>'   , '<'   , '&'),
            $string
        );

        return jsquote($string);
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $forceString)
{
    $string = str($string);

    return $forceString || +$string + ''  != $string
        ? ("'" + $string.replace(
                /&#039;/g, "'").replace(
                /&quot;/g, '"').replace(
                /&gt;/g  , '>').replace(
                /&lt;/g  , '<').replace(
                /&amp;/g , '&').replace(
                /\\/g , '\\\\').replace(
                /'/g  , "\\'").replace(
                /\r/g , '\\r').replace(
                /\n/g , '\\n').replace(
                /<\//g, '<\\\/'
            ) + "'"
        )
        : +$string;
}

<?php   }
}
