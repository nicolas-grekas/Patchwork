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

class Php532
{
    static function stream_resolve_include_path($file)
    {
        $exists = file_exists($file);

        if (null === $exists) return null;

        $file = (string) $file;

        // Check for stream wrappers

        if (1 < strpos($file, '://') && preg_match("'^[-+.a-zA-Z0-9]{2,}://'", $file)) return false;

        // Check for relative path

        if ('' === $file) return realpath('.');

        if ('.' === $file[0])
        {
            if (!isset($file[1])) return realpath('.');
            if ('/' === $file[1] || DIRECTORY_SEPARATOR === $file[1]) return $exists ? realpath($file) : false;
            if ('.' === $file[1])
            {
                if (!isset($file[2])) return realpath('..');
                if ('/' === $file[2] || DIRECTORY_SEPARATOR === $file[2]) return $exists ? realpath($file) : false;
            }
        }

        // Check for absolute path

/**/    if ('\\' === DIRECTORY_SEPARATOR)
/**/    {
            // This is how the native stream_resolve_include_path() behaves under Windows
            if (preg_match("'^(?:[a-zA-Z]:|[/\\\\]{2})'", $file, $m))
            {
                if (':' !== $m[0][1])
                {
                    $file = str_replace('\\', '/', $file);
                    $m = explode('/', substr($file, 2), 3);
                    if (!isset($m[2])) return strtoupper($file);
                }

                return $exists ? realpath($file) : false;
            }
/**/    }
/**/    else
/**/    {
            if ('/' === $file[0]) return $exists ? realpath($file) : false;
/**/    }

        // Finally check the include path

        foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir)
            if (file_exists($dir . DIRECTORY_SEPARATOR . $file))
                return realpath($dir . DIRECTORY_SEPARATOR . $file);

        return false;
    }
}
