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


$_patchwork_abstract = array();


// Autoload markers

/**/$GLOBALS["c\x9D"] = array();
/**/$GLOBALS["b\x9D"] = $GLOBALS["a\x9D"] = false;
/**//*<*/"\$c\x9D=array();\$d\x9D=1;(\$e\x9D=\$b\x9D=\$a\x9D=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "')&&\$d\x9D&&0;"/*>*/;


// Shutdown control

/**/boot::$manager->pushFile('class/Patchwork/ShutdownHandler.php');
/**/boot::$manager->pushFile('bootup.shutdown.php');


// Private use for the preprocessor

function &patchwork_autoload_marker($marker, &$ref) {return $ref;}

function patchwork_include_voicer($file, $voices)
{
    unset($file, $voices); // Cleanup the local scope

    if (null === func_get_arg(1)) return include func_get_arg(0);

    try
    {
        error_reporting(func_get_arg(1) | /*<*/(E_PARSE | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR)/*>*/);
        $e = include func_get_arg(0);
        error_reporting(func_get_arg(1));
        return $e;
    }
    catch (Exception $e)
    {
        error_reporting(func_get_arg(1));
        throw $e;
    }
}


// Utility functions

function patchwork_class2file($class)
{
    if (false !== $a = strrpos($class, '\\'))
    {
        $a += $b = strspn($class, '\\');
        $class =  strtr(substr($class, $b, $a), '\\', '/')
            .'/'. strtr(substr($class, $a+1  ), '_' , '/');
    }
    else
    {
        $class = strtr($class, '_', '/');
    }

    if (false !== strpos($class, '//x'))
    {
/**/    $a = "_/ /!/#/$/%/&/'/(/)/+/,/-/./;/=/@/[/]/^/`/{/}/~";
/**/    $a = array(array(), explode('/', $a));
/**/    foreach ($a[1] as $b) $a[0][] = '//x' . strtoupper(dechex(ord($b)));

        $class = str_replace(/*<*/$a[0]/*>*/, /*<*/$a[1]/*>*/, $class);
    }

    return $class;
}

function patchwork_file2class($file)
{
/**/$a = "_/ /!/#/$/%/&/'/(/)/+/,/-/./;/=/@/[/]/^/`/{/}/~";
/**/$a = array(explode('/', $a), array());
/**/foreach ($a[0] as $b) $a[1][] = '__x' . strtoupper(dechex(ord($b)));

    $file = str_replace(/*<*/$a[0]/*>*/, /*<*/$a[1]/*>*/, $file);
    $file = strtr($file, '/\\', '__');

    return $file;
}

function patchwork_class2cache($class, $level)
{
    if (false !== strpos($class, '__x'))
    {
        static $map = array(
            array('__x25', '__x2B', '__x2D', '__x2E', '__x3D', '__x7E'),
            array('%',     '+',     '-',     '.',     '=',     '~'    )
        );

        $class = str_replace($map[0], $map[1], $class);
    }

    $cache = defined('DEBUG') ? (int) DEBUG : 0;
    $cache .= 0 > $level ? -$level . '-' : $level;
    $cache = /*<*/PATCHWORK_PROJECT_PATH . '.class_'/*>*/ . strtr($class, '\\', '_') . ".{$cache}.zcache.php";

    return $cache;
}


// registerAutoloadPrefix()

$patchwork_autoload_prefix = array();

function registerAutoloadPrefix($class_prefix, $class_to_file_callback)
{
    if ($len = strlen($class_prefix))
    {
        $registry =& $GLOBALS['patchwork_autoload_prefix'];
        $class_prefix = strtolower($class_prefix);
        $i = 0;

        do
        {
            $c = ord($class_prefix[$i]);
            isset($registry[$c]) || $registry[$c] = array();
            $registry =& $registry[$c];
        }
        while (++$i < $len);

        $registry[-1] = $class_to_file_callback;
    }
}


// patchwork-specific include_path-like mechanism

function patchworkPath($file, &$last_level = false, $level = false, $base = false)
{
    if (false === $level)
    {
/**/if ('\\' === DIRECTORY_SEPARATOR)
        if (isset($file[0]) && ('\\' === $file[0] || false !== strpos($file, ':'))) return $file;
        if (isset($file[0]) &&  '/'  === $file[0]) return $file;

        $i = 0;
        $level = PATCHWORK_PATH_LEVEL;
    }
    else
    {
        0 <= $level && $base = 0;
        $i = PATCHWORK_PATH_LEVEL - $level - $base;
        0 > $i && $i = 0;
    }

/**/if ('\\' === DIRECTORY_SEPARATOR)
        false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

    if (0 === $i)
    {
        $source = PATCHWORK_PROJECT_PATH . $file;

/**/    if ('\\' === DIRECTORY_SEPARATOR)
/**/    {
            if (function_exists('patchwork_file_exists') ? patchwork_file_exists($source) : file_exists($source))
            {
                $last_level = $level;
                return false !== strpos($source, '/') ? strtr($source, '/', '\\') : $source;
            }
/**/    }
/**/    else
/**/    {
            if (file_exists($source))
            {
                $last_level = $level;
                return $source;
            }
/**/    }
    }

    if ($slash = '/' === substr($file, -1)) $file = substr($file, 0, -1);

/**/require boot::$manager->getCurrentDir() . 'class/Patchwork/Updatedb.php';
/**/$a = new Patchwork_Updatedb;
/**/$a = $a->buildPathCache($GLOBALS['patchwork_path'], PATCHWORK_PATH_LEVEL, PATCHWORK_PROJECT_PATH, PATCHWORK_ZCACHE);

/**/if ($a)
/**/{
        static $db;

        if (!isset($db))
        {
            if (!$db = @dba_popen(/*<*/PATCHWORK_PROJECT_PATH . '.patchwork.paths.db'/*>*/, 'rd', /*<*/$a/*>*/))
            {
                require /*<*/boot::$manager->getCurrentDir() . 'class/Patchwork/Updatedb.php'/*>*/;
                $db = new Patchwork_Updatedb;
                $db = $db->buildPathCache($GLOBALS['patchwork_path'], PATCHWORK_PATH_LEVEL, PATCHWORK_PROJECT_PATH, PATCHWORK_ZCACHE);
                if (!$db = dba_popen(PATCHWORK_PROJECT_PATH . '.patchwork.paths.db', 'rd', $db)) exit;
            }
        }

        $base = dba_fetch($file, $db);
/**/}
/**/else
/**/{
        $base = md5($file);
        $base = PATCHWORK_ZCACHE . $base[0] . '/' . $base[1] . '/' . substr($base, 2) . '.path.txt';
        $base = @file_get_contents($base);
/**/}

    if (false !== $base)
    {
        $base = explode(',', $base);
        do if (current($base) >= $i)
        {
            $base = (int) current($base);
            $last_level = $level - $base + $i;

/**/        if ('\\' === DIRECTORY_SEPARATOR)
                false !== strpos($file, '/') && $file = strtr($file, '/', '\\');

            return $GLOBALS['patchwork_path'][$base] . (0<=$last_level ? $file : substr($file, 6)) . ($slash ? DIRECTORY_SEPARATOR : '');
        }
        while (false !== next($base));
    }

    return false;
}


// Autoloading

/**/@unlink(PATCHWORK_PROJECT_PATH . '.patchwork.autoloader.php');
/**/copy(boot::$manager->getCurrentDir() . 'class/Patchwork/Autoloader.php', PATCHWORK_PROJECT_PATH . '.patchwork.autoloader.php');

/**/if (function_exists('class_alias'))
/**/{
        spl_autoload_register('patchwork_autoload_alias', true, true);

        function patchwork_autoload_alias($class)
        {
            if (strrpos($class, '\\'))
            {
/**/            if (50300 <= PHP_VERSION_ID && PHP_VERSION_ID < 50303) // Workaround http://bugs.php.net/50731
                    '\\' === $class[0] && $class = substr($class, 1);

                $c = strtr($class, '\\', '_');

                if (class_exists($c, false) || interface_exists($c, false) || trait_exists($c, false))
                {
                    class_alias($c, $class);
                    return true;
                }
            }

            return false;
        }
/**/}

spl_autoload_register('patchwork_autoload_autoloader');

function patchwork_autoload_autoloader($class)
{
    spl_autoload_unregister(__FUNCTION__);
    require /*<*/PATCHWORK_PROJECT_PATH . '.patchwork.autoloader.php'/*>*/;
    defined('TURBO') && Patchwork_Autoloader::$turbo = TURBO;
    spl_autoload_register(array('Patchwork_Autoloader', 'autoload'));
    Patchwork_Autoloader::autoload($class);
}

function patchwork_exists($class, $autoload)
{
    if (class_exists($class, $autoload) || interface_exists($class, false) || trait_exists($class, false)) return true;

/**/if (function_exists('class_alias'))
/**/{
        return patchwork_autoload_alias($class);
/**/}
/**/else
/**/{
        return false;
/**/}
}


// patchworkProcessedPath(): private use for the preprocessor (in files in the include_path)

function patchworkProcessedPath($file, $lazy = false)
{
/**/if ('\\' === DIRECTORY_SEPARATOR)
        false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

    if (false !== strpos('.' . $file, './') || (/*<*/'\\' === DIRECTORY_SEPARATOR/*>*/ && ':' === substr($file, 1, 1)))
    {
/**/if (function_exists('__patchwork_realpath'))
        if ($f = patchwork_realpath($file)) $file = $f;
/**/else
        if ($f = realpath($file)) $file = $f;

        $p = $GLOBALS['patchwork_path'];

        for ($i = /*<*/PATCHWORK_PATH_LEVEL + 1/*>*/; $i < /*<*/count($GLOBALS['patchwork_path'])/*>*/; ++$i)
        {
            if (0 === strncmp($file, $p[$i], strlen($p[$i])))
            {
                $file = substr($file, strlen($p[$i]));
                break;
            }
        }

        if (/*<*/count($GLOBALS['patchwork_path'])/*>*/ === $i) return $f;
    }

    $source = patchworkPath('class/' . $file, $level);

    if (false === $source) return false;

    $cache = patchwork_file2class($file);
    $cache = patchwork_class2cache($cache, $level);

    if (file_exists($cache) && (TURBO || filemtime($cache) > filemtime($source))) return $cache;

    Patchwork_Preprocessor::execute($source, $cache, $level, false, true, $lazy);

    return $cache;
}
