<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_Superloader
{
    static

    $turbo = false,
    $locations = array(),
    $abstracts = array();

    protected static $prefix = array();


    static function registerPrefix($class_prefix, $class_to_file_callback)
    {
        if ($len = strlen($class_prefix))
        {
            $registry =& self::$prefix;
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

    static function exists($class, $a)
    {
        if (    class_exists($class, $a   )) return 'class';
        if (interface_exists($class, false)) return 'interface';
        if (    trait_exists($class, false)) return 'trait';

        if ($a) return false;

/**/    if (function_exists('class_alias'))
            if ($a = self::loadAlias($class)) return $a;

        if ( false === ($a = strrpos($class, '__'))
          || !isset($class[$a+2])
          || '' !== rtrim(substr($class, $a+2), '0123456789') )
        {
            $class .= '__';

            static $map = /*<*/array_map('strval', array(-1 => '00') + range(0, PATCHWORK_PATH_LEVEL))/*>*/;

            foreach ($map as $a)
                if (class_exists($class . $a, false))
                    return 'class';
        }

        return false;
    }

    static function loadAlias($class)
    {
        if (strrpos($class, '\\'))
        {
/**/        if (50300 <= PHP_VERSION_ID && PHP_VERSION_ID < 50303) // Workaround http://bugs.php.net/50731
                '\\' === $class[0] && $class = substr($class, 1);

            $c = strtr($class, '\\', '_');

            if (    class_exists($c, false) && class_alias($c, $class)) return 'class';
            if (interface_exists($c, false) && class_alias($c, $class)) return 'interface';
            if (    trait_exists($c, false) && class_alias($c, $class)) return 'trait';
        }

        return false;
    }

    static function loadAutoloader($class)
    {
        spl_autoload_unregister(array(__CLASS__, __FUNCTION__));
        require /*<*/PATCHWORK_PROJECT_PATH . '.patchwork.autoloader.php'/*>*/;
        spl_autoload_register(array('Patchwork_Autoloader', 'loadClass'));
        Patchwork_Autoloader::loadClass($class);
    }

    static function loadTurbo($class)
    {
/**/    if (50300 <= PHP_VERSION_ID && PHP_VERSION_ID < 50303) // Workaround http://bugs.php.net/50731
            isset($class[0]) && '\\' === $class[0] && $class = substr($class, 1);

        if (empty(self::$locations[$a = strtolower(strtr($class, '\\', '_'))])) return;

        if (is_int($a =& self::$locations[$a]))
        {
            $b = $a;
            unset($a);
            $a = $b - /*<*/count($GLOBALS['patchwork_path']) - PATCHWORK_PATH_LEVEL/*>*/;

            $b = strtr($class, '\\', '_');
            $i = strrpos($b, '__');
            false !== $i && isset($b[$i+2]) && '' === trim(substr($b, $i+2), '0123456789') && $b = substr($b, 0, $i);

            $a = $b . '.php.' . DEBUG . (0>$a ? -$a . '-' : $a);
        }

        $a = /*<*/PATCHWORK_PROJECT_PATH  . '.class_'/*>*/ . $a . '.zcache.php';

        $GLOBALS["a\x9D"] = false;

        if (file_exists($a)) patchwork_include($a);
    }

    static function class2file($class)
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
/**/        $a = "_/ /!/#/$/%/&/'/(/)/+/,/-/./;/=/@/[/]/^/`/{/}/~";
/**/        $a = array(array(), explode('/', $a));
/**/        foreach ($a[1] as $b) $a[0][] = '//x' . strtoupper(dechex(ord($b)));

            $class = str_replace(/*<*/$a[0]/*>*/, /*<*/$a[1]/*>*/, $class);
        }

        return $class;
    }

    static function file2cache($file, $level)
    {
        return self::class2cache(self::file2class($file), $level);
    }

    static function file2class($file)
    {
/**/    $a = "_/ /!/#/$/%/&/'/(/)/+/,/-/./;/=/@/[/]/^/`/{/}/~";
/**/    $a = array(explode('/', $a), array());
/**/    foreach ($a[0] as $b) $a[1][] = '__x' . strtoupper(dechex(ord($b)));

        $file = str_replace(/*<*/$a[0]/*>*/, /*<*/$a[1]/*>*/, $file);
        $file = strtr($file, '/\\', '__');

        return $file;
    }

    static function class2cache($class, $level)
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

    // private use for the preprocessor (in files in the include_path)

    static function getProcessedPath($file, $lazy = false)
    {
/**/    if ('\\' === DIRECTORY_SEPARATOR)
            false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

        if (false !== strpos('.' . $file, './') || (/*<*/'\\' === DIRECTORY_SEPARATOR/*>*/ && ':' === substr($file, 1, 1)))
        {
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

        $cache = self::file2cache($file, $level);

        if (file_exists($cache) && (self::$turbo || filemtime($cache) > filemtime($source))) return $cache;

        Patchwork_Preprocessor::execute($source, $cache, $level, false, true, $lazy);

        return $cache;
    }

    static function get_parent_class($c)
    {
        // FIXME: using Patchwork_PHP_Override_Php530::get_parent_class() should be done automatically by FunctionOverrider

/**/    if (PHP_VERSION_ID < 50300)
            $c = Patchwork_PHP_Override_Php530::get_parent_class($c);
/**/    else
            $c = get_parent_class($c);

        if ( false !== $c
          && false !== ($i = strrpos($c, '__'))
          && isset($c[$i+2])
          && '' === rtrim(substr($c, $i+2), '0123456789') )
        {
            $top = substr($c, 0, $i) . '__';
            $i = strlen($top);

            do
            {
/**/            if (PHP_VERSION_ID < 50300)
                    $c = Patchwork_PHP_Override_Php530::get_parent_class($c);
/**/            else
                    $c = get_parent_class($c);
            }
            while (false !== $c && isset($c[$i]) && $top === rtrim($c, '0123456789'));
        }

        return $c;
    }
}
