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

class Php540
{
    static function array_combine($k, $v)
    {
        if (array() === $k && $v === $k) return $k;

        return array_combine($k, $v);
    }

    function hex2bin($data)
    {
        $len = strlen($data);

        if (null === $len) return;
        else if ($len % 2)
        {
            trigger_error('hex2bin(): Hexadecimal input string must have an even length', E_USER_WARNING);
            return false;
        }

        $data = pack('H*', $data);

        if (false !== strpos($data, "\0")) return false;

        return $data;
    }

    static function json_decode($json, $assoc = false, $depth = 512, $options = 0)
    {
/**/    if (PHP_VERSION_ID < 50300)
            return json_decode($json, $assoc);
/**/    else if (PHP_VERSION_ID < 50400)
            return json_decode($json, $assoc, $depth);
/**/    else
            return json_decode($json, $assoc, $depth, $options);
    }

    static function number_format($number, $decimals = 0, $dec_point = '.', $thousands_sep = ',')
    {
        if (isset($thousands_sep[1]) || isset($dec_point[1]))
        {
            return str_replace(
                array('.', ','),
                array($dec_point, $thousands_sep),
                number_format($number, $decimals, '.', ',')
            );
        }

        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }
}
