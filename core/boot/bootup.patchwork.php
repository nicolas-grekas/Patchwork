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

/**/if (!isset($_SERVER['REQUEST_TIME_FLOAT']))
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

/**/boot::$manager->pushFile('bootup.override.php');

ini_set('unserialize_callback_func', /*<*/function_exists('__patchwork_spl_autoload_call') ? '__patchwork_spl_autoload_call' : 'spl_autoload_call'/*>*/);
ini_set('html_errors', false);
ini_set('display_errors', true);
error_reporting(/*<*/E_ALL | E_STRICT/*>*/);
setlocale(LC_ALL, 'C');

// Backport some usefull basic constants

/**/if (!defined('E_DEPRECATED'))
        define('E_DEPRECATED', E_NOTICE);
/**/if (!defined('E_USER_DEPRECATED'))
        define('E_USER_DEPRECATED', E_USER_NOTICE);

/**/if (function_exists('spl_autoload')) // spl_autoload() evades code preprocessing, do not use it
        Patchwork\FunctionOverride(spl_autoload, Patchwork\PHP\Override\SplAutoload, $class);

// Boolean version of ini_get()

function ini_get_bool($a)
{
    switch ($b = strtolower(ini_get($a)))
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
        Patchwork\FunctionOverride(getcwd, patchwork_getcwd);

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
/**/    boot::$manager->pushFile('bootup.realpath.php');
        Patchwork\FunctionOverride(realpath, patchwork_realpath, $path);
/**/}
/**/else
/**/{
        function patchwork_realpath($a) {return realpath($a);}
/**/}
