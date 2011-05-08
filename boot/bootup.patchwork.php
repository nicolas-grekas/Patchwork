<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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


/**/boot::$manager->pushFile('bootup/spl-autoload.php');
/**/boot::$manager->pushFile('bootup/override.php');
defined('PATCHWORK_MICROTIME') || define('PATCHWORK_MICROTIME', microtime(true));
error_reporting(E_ALL | E_STRICT);
setlocale(LC_ALL, 'C');

// Backport some usefull basic constants

/**/if (!defined('PHP_VERSION_ID'))
/**/{
/**/    $a = array_map('intval', explode('.', PHP_VERSION, 3));
        define('PHP_VERSION_ID',      /*<*/(10000 * $a[0] + 100 * $a[1] + $a[2])     /*>*/);
        define('PHP_MAJOR_VERSION',   /*<*/$a[0]  /*>*/);
        define('PHP_MINOR_VERSION',   /*<*/$a[1]  /*>*/);
        define('PHP_RELEASE_VERSION', /*<*/$a[2]/*>*/);

/**/    $a = substr(PHP_VERSION, strlen(implode('.', $a)));
        define('PHP_EXTRA_VERSION',   /*<*/false !== $a ? $a : ''/*>*/);
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
/**/    /*<*/boot::$manager->override('getcwd', 'patchwork_getcwd', array())/*>*/;

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
/**/    boot::$manager->pushFile('bootup/realpath.php');
/**/    /*<*/boot::$manager->override('realpath', 'patchwork_realpath', array('$path'))/*>*/;
/**/}
/**/else
/**/{
        function patchwork_realpath($a) {return realpath($a);}
/**/}
