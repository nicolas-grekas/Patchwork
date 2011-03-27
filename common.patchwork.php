<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


// General pre-configuration

defined('PATCHWORK_MICROTIME') || define('PATCHWORK_MICROTIME', microtime(true));
define('IS_WINDOWS', /*<*/'\\' === DIRECTORY_SEPARATOR/*>*/);
error_reporting(E_ALL | E_STRICT);
setlocale(LC_ALL, 'C');

/**/if (!defined('PHP_VERSION_ID'))
/**/{
/**/    $a = array_map('intval', explode('.', PHP_VERSION, 3));
/**/    define('PHP_VERSION_ID',      (10000 * $a[0] + 100 * $a[1] + $a[2]));
/**/    define('PHP_MAJOR_VERSION',   $a[0]);
/**/    define('PHP_MINOR_VERSION',   $a[1]);
/**/    define('PHP_RELEASE_VERSION', $a[2]);
/**/    $a = substr(PHP_VERSION, strlen(implode('.', $a)));
/**/    define('PHP_EXTRA_VERSION', false !== $a ? $a : '');

        if (!defined('PHP_VERSION_ID'))
        {
            define('PHP_EXTRA_VERSION',   /*<*/PHP_EXTRA_VERSION  /*>*/);
            define('PHP_VERSION_ID',      /*<*/PHP_VERSION_ID     /*>*/);
            define('PHP_MAJOR_VERSION',   /*<*/PHP_MAJOR_VERSION  /*>*/);
            define('PHP_MINOR_VERSION',   /*<*/PHP_MINOR_VERSION  /*>*/);
            define('PHP_RELEASE_VERSION', /*<*/PHP_RELEASE_VERSION/*>*/);
        }
/**/}

/**/if (!defined('E_RECOVERABLE_ERROR'))
        define('E_RECOVERABLE_ERROR', 4096);
/**/if (!defined('E_DEPRECATED'))
        define('E_DEPRECATED',        8192);
/**/if (!defined('E_USER_DEPRECATED'))
        define('E_USER_DEPRECATED',  16384);


// Boolean version of ini_get()

function ini_get_bool($a)
{
    switch ($b = strtolower(@ini_get($a)))
    {
    case 'on':
    case 'yes':
    case 'true':
        return 'assert.active' !== $a;

    case 'stdout':
    case 'stderr':
        return 'display_errors' === $a;

    default:
        return (bool) (int) $b;
    }
}


// Runtime function aliasing: private use for the preprocessor

function patchwork_alias_resolve($c)
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

function patchwork_alias_resolve_ref($c, &$v)
{
    $v = patchwork_alias_resolve($c);
/**/if (PHP_VERSION_ID < 50203)
        is_array($v) && is_string($c) && $v = implode('', $v);
    return "\x9D";
}


// Set hidden flag on a file on MS-Windows

function win_hide_file($file)
{
/**/if ('\\' === DIRECTORY_SEPARATOR)
/**/{
        static $h;
        empty($h) && $h = new COM('Scripting.FileSystemObject');
        $h->GetFile($file)->Attributes |= 2; // Set hidden attribute
        return true;
/**/}
}


// If realpath() or getcwd() are bugged, enable a workaround

/**/$a = function_exists('realpath') ? @realpath('.') : false;
/**/if (!$a || '.' === $a)
/**/{
/**/    if (function_exists('getcwd') && @getcwd()) $a = true;
/**/    else
/**/    {
/**/        $a = function_exists('get_included_files') ? @get_included_files() : '';
/**/        $a = $a ? $a[0] : (!empty($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '.');
/**/        $a = dirname($a);
/**/    }
/**/}
/**/else $a = false;

/**/if (!function_exists('getcwd') || !@getcwd())
/**/    /*<*/Patchwork_Bootstrapper::alias('getcwd', 'patchwork_getcwd', array())/*>*/;

function patchwork_getcwd()
{
/**/if (function_exists('getcwd') && @getcwd())
/**/{
        return getcwd();
/**/}
/**/else if (false === $a)
/**/{
        return realpath('.');
/**/}
/**/else
/**/{
        return /*<*/$a/*>*/;
/**/}
}

/**/if (false !== $a)
/**/{
/**/    /*<*/Patchwork_Bootstrapper::alias('realpath', 'patchwork_realpath', array('$path'))/*>*/;

        function patchwork_realpath($a)
        {
            do
            {
                if (isset($a[0]))
                {
/**/                if ('\\' === DIRECTORY_SEPARATOR)
/**/                {
                        if ('/' === $a[0] || '\\' === $a[0])
                        {
                            $a = 'c:' . $a;
                            break;
                        }

                        if (false !== strpos($a, ':')) break;
/**/                }
/**/                else
/**/                {
                        if ('/' === $a[0]) break;
/**/                }
                }

/**/            if (true === $a)
                    $cwd = getcwd();
/**/            else
                    $cwd = /*<*/$a/*>*/;

                $a = $cwd . /*<*/DIRECTORY_SEPARATOR/*>*/ . $a;

                break;
            }
            while (0);

            if (isset($cwd) && '.' === $cwd) $prefix = '.';
            else
            {
/**/            if ('\\' === DIRECTORY_SEPARATOR)
/**/            {
                    $prefix = strtoupper($a[0]) . ':\\';
                    $a = substr($a, 2);
/**/            }
/**/            else
/**/            {
                    $prefix = '/';
/**/            }
            }

/**/        if ('\\' === DIRECTORY_SEPARATOR)
                $a = strtr($a, '/', '\\');

            $a = explode(/*<*/DIRECTORY_SEPARATOR/*>*/, $a);
            $b = array();

            foreach ($a as $a)
            {
                if (!isset($a[0]) || '.' === $a) continue;
                if ('..' === $a) $b && array_pop($b);
                else $b[]= $a;
            }

            $a = $prefix . implode(/*<*/DIRECTORY_SEPARATOR/*>*/, $b);

/**/        if ('\\' === DIRECTORY_SEPARATOR)
                $a = strtolower($a);

            return file_exists($a) ? $a : false;
        }
/**/}
/**/else
/**/{
        function patchwork_realpath($a) {return realpath($a);}

/**/    if ('\\' === DIRECTORY_SEPARATOR && PHP_VERSION_ID < 50200)
/**/    {
/**/        // Replace file_exists() on Windows to fix a bug with long file names
/**/
/**/        /*<*/Patchwork_Bootstrapper::alias('file_exists',   'Patchwork_PHP_Overlay_Winfs::file_exists',   array('$file'))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('is_file',       'Patchwork_PHP_Overlay_Winfs::is_file',       array('$file'))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('is_dir',        'Patchwork_PHP_Overlay_Winfs::is_dir',        array('$file'))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('is_link',       'Patchwork_PHP_Overlay_Winfs::is_link',       array('$file'))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('is_executable', 'Patchwork_PHP_Overlay_Winfs::is_executable', array('$file'))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('is_readable',   'Patchwork_PHP_Overlay_Winfs::is_readable',   array('$file'))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('is_writable',   'Patchwork_PHP_Overlay_Winfs::is_writable',   array('$file'))/*>*/;
/**/    }
/**/}
