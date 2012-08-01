<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


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
