<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Shim;

class Locale
{
    // todo: http://git.php.net/?p=php-src.git;a=commitdiff;h=3f7f72adb25786f51e7907e0d37f2e25bd5cf3dd

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

    static function pathinfo($path, $option = 15)
    {
        $path = self::encodeHighAscii($path);
        $path = pathinfo($path, $option);

        return is_array($path)
            ? array_map(array(__CLASS__, 'decodeHighAscii'), $path)
            : self::decodeHighAscii($path);
    }

    static function escapeshellarg($arg)
    {
        $arg = self::encodeHighAscii($arg);
        $arg = escapeshellarg($arg);
        return self::decodeHighAscii($arg);
    }

    static function escapeshellcmd($cmd)
    {
        $cmd = self::encodeHighAscii($cmd);
        $cmd = escapeshellcmd($cmd);
        return self::decodeHighAscii($cmd);
    }


    protected static function encodeHighAscii($s)
    {
        $s = preg_replace_callback("'[%\x80-\xFF]+'", array(__CLASS__, 'encodeCallback'), $s);
        return strtr(str_replace('_', '_5F', $s), '%', '_');
    }

    protected static function decodeHighAscii($s)
    {
        return rawurldecode(strtr($s, '_', '%'));
    }

    protected static function encodeCallback($m)
    {
        return rawurlencode($m[0]);
    }
}
