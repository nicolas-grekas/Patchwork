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

class Php550
{
    static function boolval($val)
    {
        return !! $val;
    }

    static function json_encode($value, $options = 0, $depth = 512)
    {
/**/    if (PHP_VERSION_ID < 50300)
            return json_encode($value);
/**/    else
            return json_encode($value, $options);
    }

    static function json_last_error_msg()
    {
        switch (json_last_error())
        {
        case JSON_ERROR_NONE: return "No error";
        case JSON_ERROR_DEPTH: return "Maximum stack depth exceeded";
        case JSON_ERROR_STATE_MISMATCH: return "State mismatch (invalid or malformed JSON)";
        case JSON_ERROR_CTRL_CHAR: return "Control character error, possibly incorrectly encoded";
        case JSON_ERROR_SYNTAX: return "Syntax error";
        case JSON_ERROR_UTF8: return "Malformed UTF-8 characters, possibly incorrectly encoded"; // Since 5.3.3
        case JSON_ERROR_RECURSION: return "Recursion detected"; // Since 5.5.0
        case JSON_ERROR_INF_OR_NAN: return "Inf and NaN cannot be JSON encoded"; // Since 5.5.0
        case JSON_ERROR_UNSUPPORTED_TYPE: return "Type is not supported"; // Since 5.5.0
        default: return "Unknown error";
        }
    }

    static function opcache_invalidate($file, $force = false)
    {
        return static::opcache_reset();
    }

    static function opcache_reset()
    {
/**/    if (function_exists('accelerator_reset'))
            accelerator_reset();

/**/    if (function_exists('apc_clear_cache'))
            apc_clear_cache('opcode');

/**/    if (function_exists('eaccelerator_clear'))
            eaccelerator_clear();

/**/    if (function_exists('opcache_reset'))
            opcache_reset();

/**/    if (function_exists('wincache_refresh_if_changed'))
            wincache_refresh_if_changed();

        return true;
    }

    static function set_error_handler($error_handler, $error_types = -1)
    {
        if (null === $error_handler)
        {
            $h = set_error_handler('var_dump', 0);
            do restore_error_handler() && restore_error_handler();
            while (null !== set_error_handler('var_dump', 0));
            restore_error_handler();
            return $h;
        }

        return set_error_handler($error_handler, $error_types);
    }

    static function set_exception_handler($exception_handler)
    {
        if (null === $exception_handler)
        {
            $h = set_exception_handler('var_dump');
            restore_exception_handler();
            set_exception_handler(null);
            return $h;
        }

        return set_exception_handler($exception_handler);
    }
}
