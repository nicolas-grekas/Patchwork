<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
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


class pipe_urlappend
{
    static function php($url, $param, $value)
    {
        $url   = (string) $url;
        $param = (string) $param;
        $value = (string) $value;

        $url = preg_replace("/(?:(\?)|&(?:amp;)?){$param}=[^&]+/", '$1', $url);
        $param = rawurlencode($param) . '=' . rawurlencode($value);

        if ('?' !== substr($url, -1))
        {
            $param = (false !== strpos($url, '?') ? '&amp;' : '?') . $param;
        }

        return $url . $param;
    }

    static function js()
    {
        ?>/*<script>*/

function($url, $param, $value)
{
    $url   = str($url);
    $param = str($param);
    $value = str($value);

    $url = $url.replace(new RegExp("(?:(\?)|&(?:amp;)?)" + $param + "=[^&]+"), '$1');
    $param = eUC($param) + '=' + eUC($value);

    if ('?' != $url.substr($url, $url.length-1))
    {
        $param = (-1 != $url.indexOf('?') ? '&amp;' : '?') + $param;
    }

    return $url + $param;
}

<?php   }
}
