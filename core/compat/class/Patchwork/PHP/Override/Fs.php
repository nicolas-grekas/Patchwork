<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Override_Fs
{
    static function basename($path, $suffix = '')
    {
        $path = rtrim($path, /*<*/'/' . ('\\' === DIRECTORY_SEPARATOR ? '\\' : '')/*>*/);

/**/    if ('\\' === DIRECTORY_SEPARATOR)
            $r = strrpos(strtr($path, '\\', '/'), '/');
/**/    else
            $r = strrpos($path, '/');

        false !== $r && $path = substr($path, $r + 1);

        return substr(basename('.' . $path, $suffix), 1);
    }

    static function pathinfo($path, $option = -1)
    {
        $path = rawurlencode($path);
        $path = str_replace('%2F', '/' , $path);
        $path = str_replace('%5C', '\\', $path);
        $path = pathinfo($path, $option);

        return is_array($path)
            ? array_map('rawurldecode', $path)
            : rawurldecode($path);
    }
}
