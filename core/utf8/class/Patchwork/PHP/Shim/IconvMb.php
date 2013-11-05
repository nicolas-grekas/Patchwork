<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Shim;

class IconvMb
{
    static function iconv_mime_decode($str, $mode = 0, $charset = INF)
    {
        INF === $charset && $charset = Iconv::$internal_encoding;

        return mb_decode_mimeheader($str, $mode, $charset);
    }

    static function iconv_strlen($s, $encoding = INF)
    {
        INF === $encoding && $encoding = Iconv::$internal_encoding;

        return mb_strlen($s, $encoding);
    }

    static function iconv_strpos($haystack, $needle, $offset = 0, $encoding = INF)
    {
        INF === $encoding && $encoding = Iconv::$internal_encoding;

        return mb_strpos($haystack, $needle, $offset, $encoding);
    }

    static function iconv_strrpos($haystack, $needle, $encoding = INF)
    {
        INF === $encoding && $encoding = Iconv::$internal_encoding;

        return mb_strrpos($haystack, $needle, $encoding);
    }

    static function iconv_substr($s, $start, $length = 2147483647, $encoding = INF)
    {
        INF === $encoding && $encoding = Iconv::$internal_encoding;

        return mb_substr($s, $start, $length, $encoding);
    }
}
