<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Override;

/**
 * Binary safe version of string functions overloaded when MB_OVERLOAD_STRING is enabled.
 */
class Mbstring8bit
{
    static function mail($to, $subject, $message, $additional_headers = '', $additional_parameters = '')
    {
        return mb_send_mail($to, $subject, $message, $additional_headers, $additional_parameters, '8bit');
    }

    static function strlen($s)
    {
        return mb_strlen($s, '8bit');
    }

    static function strpos($haystack, $needle, $offset = 0)
    {
        return mb_strpos($haystack, $needle, $offset, '8bit');
    }

    static function strrpos($haystack, $needle, $offset = 0)
    {
        return mb_strrpos($haystack, $needle, $offset, '8bit');
    }

    static function substr($string, $start, $length = 2147483647)
    {
        return mb_substr($string, $start, $length, '8bit');
    }

    static function stripos($s, $needle, $offset = 0)
    {
        return mb_stripos($s, $needle, $offset, '8bit');
    }

    static function stristr($s, $needle, $part = false)
    {
        return mb_stristr($s, $needle, $part, '8bit');
    }

    static function strrchr($s, $needle, $part = false)
    {
        return mb_strrchr($s, $needle, $part, '8bit');
    }

    static function strripos($s, $needle, $offset = 0)
    {
        return mb_strripos($s, $needle, $offset, '8bit');
    }

    static function strstr($s, $needle, $part = false)
    {
        return mb_strstr($s, $needle, $part, '8bit');
    }
}
