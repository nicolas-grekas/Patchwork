<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

/**
 * Under Windows, checks if character case is strict
 */
class Patchwork_PHP_Override_WinfsCase
{
    static function file_exists($file)
    {
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
                    user_error("Character case mismatch between requested file and its real path ({$file} vs {$realfile})", E_USER_NOTICE);
                }

                break;
            }
        }

        return true;
    }

    static function is_file($file)       {return self::file_exists($file) && is_file($file);}
    static function is_dir($file)        {return self::file_exists($file) && is_dir($file);}
    static function is_link($file)       {return self::file_exists($file) && is_link($file);}
    static function is_executable($file) {return self::file_exists($file) && is_executable($file);}
    static function is_readable($file)   {return self::file_exists($file) && is_readable($file);}
    static function is_writable($file)   {return self::file_exists($file) && is_writable($file);}
}
