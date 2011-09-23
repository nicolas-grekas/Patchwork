<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Override_520
{
    static function substr_compare($main_str, $str, $offset, $length = INF, $case_insensitivity = false)
    {
        if (INF === $length) return substr_compare($main_str, $str, $offset);
        $main_str = substr($main_str, $offset, $length);
        return $case_insensitivity ? strcasecmp($main_str, $str) : strcmp($main_str, $str);
    }

    static function setcookie($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    {
        self::setrawcookie($name, urlencode($value), $expires, $path, $domain, $secure, $httponly);
    }

    static function setrawcookie($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    {
        if ($value !== strtr($value, ",; \t\r\n\013\014", '--------')) setrawcookie($name, $value, $expires, $path, $domain, $secure);
        else
        {
            ('' === (string) $value) && $expires = 1;

            header(
                "Set-Cookie: {$name}={$value}" .
                    ($expires  ? '; expires=' . date('D, d-M-Y H:i:s T', $expires) : '') .
                    ($path     ? '; path='   . $path   : '') .
                    ($domain   ? '; domain=' . $domain : '') .
                    ($secure   ? '; secure'   : '') .
                    ($httponly ? '; HttpOnly' : ''),
                false
            );
        }
    }

    static function spl_object_hash($o)
    {
        if (!is_object($o))
        {
            trigger_error("spl_object_hash() expects parameter 1 to be object, " . gettype($o) . " given", E_USER_WARNING);
            return null;
        }

        isset($o->__spl_object_hash__) || $o->__spl_object_hash__ = md5(mt_rand() . 'spl_object_hash');

        return $o->__spl_object_hash__;
    }
}
