<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


function patchwork_realpath($a)
{
    do
    {
        if (isset($a[0]))
        {
/**/        if ('\\' === DIRECTORY_SEPARATOR)
/**/        {
                if ('/' === $a[0] || '\\' === $a[0])
                {
                    $a = 'C:' . $a;
                    break;
                }

                if (false !== strpos($a, ':')) break;
/**/        }
/**/        else
/**/        {
                if ('/' === $a[0]) break;
/**/        }
        }

/**/    if (function_exists('__patchwork_getcwd'))
            $cwd = __patchwork_getcwd();
/**/    else
            $cwd = getcwd();

        $a = $cwd . DIRECTORY_SEPARATOR . $a;

        break;
    }
    while (0);

    if (isset($cwd) && '.' === $cwd) $prefix = '.';
    else
    {
/**/    if ('\\' === DIRECTORY_SEPARATOR)
/**/    {
            $prefix = strtoupper($a[0]) . ':\\';
            $a = substr($a, 2);
/**/    }
/**/    else
/**/    {
            $prefix = '/';
/**/    }
    }

/**/if ('\\' === DIRECTORY_SEPARATOR)
        $a = strtr($a, '/', '\\');

    $a = explode(DIRECTORY_SEPARATOR, $a);
    $b = array();

    foreach ($a as $a)
    {
        if (!isset($a[0]) || '.' === $a) continue;
        if ('..' === $a) $b && array_pop($b);
        else $b[]= $a;
    }

    $a = $prefix . implode(DIRECTORY_SEPARATOR, $b);

/**/if ('\\' === DIRECTORY_SEPARATOR)
        $a = strtolower($a);

    return file_exists($a) ? $a : false;
}
