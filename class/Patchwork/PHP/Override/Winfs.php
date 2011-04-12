<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
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


// For MS-Windows only

class Patchwork_PHP_Override_Winfs
{
    static function file_exists($file)
    {
/**/    if (DEBUG)
/**/    {
            // In debug mode, check if character case is strict

            if (!file_exists($file) || !$realfile = realpath($file)) return false;

            $file = strtr($file, '/', '\\');

            $i = strlen($file);
            $j = strlen($realfile);

            while ($i-- && $j--)
            {
                if ($file[$i] != $realfile[$j])
                {
                    if (0 === strcasecmp($file[$i], $realfile[$j]) && !(0 === $i && ':' === substr($file, 1, 1)))
                    {
                        trigger_error("Character case mismatch between requested file and its real path ({$file} vs {$realfile})", E_USER_NOTICE);
                    }

                    break;
                }
            }

            return true;
/**/    }
/**/    else if (/*<*/PHP_VERSION_ID < 50200/*>*/)
/**/    {
            // Fix a bug with long file names
            return file_exists($file) && (!isset($file[99]) || realpath($file));
/**/    }
/**/    else
/**/    {
            return file_exists($file);
/**/    }
    }

    static function is_file($file)       {return self::file_exists($file) && is_file($file);}
    static function is_dir($file)        {return self::file_exists($file) && is_dir($file);}
    static function is_link($file)       {return self::file_exists($file) && is_link($file);}
    static function is_executable($file) {return self::file_exists($file) && is_executable($file);}
    static function is_readable($file)   {return self::file_exists($file) && is_readable($file);}
    static function is_writable($file)   {return self::file_exists($file) && is_writable($file);}
}
