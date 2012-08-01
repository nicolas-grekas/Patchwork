<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


// Runtime function overriding: private use for the preprocessor

function patchwork_override_resolve($c)
{
    if (is_string($c) && isset($c[0]))
    {
        if ('\\' === $c[0])
        {
            if (empty($c[1]) || '\\' === $c[1]) return $c;
            $c = substr($c, 1);
        }

        if (function_exists('__patchwork_' . strtr($c, '\\', '_')))
            return '__patchwork_' . strtr($c, '\\', '_');

/**/    if (PHP_VERSION_ID < 50300)
            $c = strtr($c, '\\', '_');

/**/    if (PHP_VERSION_ID < 50203)
            strpos($c, '::') && $c = explode('::', $c, 2);
    }
    else
    {
/**/    if (PHP_VERSION_ID < 50300)
/**/    {
            if (is_array($c) && isset($c[0]) && is_string($c[0]))
                $c[0] = strtr($c[0], '\\', '_');
/**/    }
    }

    return $c;
}

function patchwork_override_resolve_ref($c, &$v)
{
    $v = patchwork_override_resolve($c);
/**/if (PHP_VERSION_ID < 50203)
        is_array($v) && is_string($c) && $v = implode('', $v);
    return "\x9D";
}
