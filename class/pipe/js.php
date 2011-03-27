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


class pipe_js
{
    static function php($string, $forceString = false)
    {
        $string = Patchwork::string($string);

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

    return $forceString || (''+$string/1 != $string)
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
        : $string/1;
}

<?php   }
}
