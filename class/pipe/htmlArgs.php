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


class pipe_htmlArgs
{
    static function php($pool)
    {
        if (!$pool) return '';

        $except = func_get_args();
        $except = array_slice($except, 1);

        $result = '';
        foreach ($pool as $k => $v)
        {
            if ('_' !== substr($k, 0, 1) && 'iteratorPosition' !== $k && false === strpos($k, '$') && !in_array($k, $except))
            {
                $v = Patchwork::string($v);
                '' !== $v && $result .= $k . '="' . $v . '" ';
            }
        }

        return $result;
    }

    static function js()
    {
        ?>/*<script>*/

function($pool)
{
    if (!$pool) return '';
    var $result = '', $args = arguments, $i = $args.length, $except = [];

    while (--$i) $except[$i] = $args[$i];
    $except = new RegExp('^(|' + $except.join('|') + ')$');

    for ($i in $pool)
    {
        if ('_' != $i.substr(0, 1) && 'iteratorPosition' != $i && $i.indexOf('$') < 0 && $i.search($except))
        {
            $args = str($pool[$i]);
            if ('' != $args) $result += $i + '="' + $args + '" ';
        }
    }

    return $result;
}

<?php   }
}
