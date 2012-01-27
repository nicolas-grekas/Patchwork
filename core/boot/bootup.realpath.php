<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


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
