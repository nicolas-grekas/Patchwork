<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


// Autoloading

/**/boot::$manager->pushFile('class/Patchwork/Superloader.php');
/**/boot::$manager->pushFile('bootup.autoload.php');


// Shutdown control

/**/boot::$manager->pushFile('class/Patchwork/ShutdownHandler.php');
/**/boot::$manager->pushFile('bootup.shutdown.php');


// patchwork-specific include_path-like mechanism

function patchworkPath($file, &$last_level = false, $level = false, $base = false)
{
    if (false === $level)
    {
/**/    if ('\\' === DIRECTORY_SEPARATOR)
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

        if (file_exists($source))
        {
            $last_level = $level;

/**/        if ('\\' === DIRECTORY_SEPARATOR)
                return false !== strpos($source, '/') ? strtr($source, '/', '\\') : $source;
/**/        else
                return $source;
        }
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
                $db->buildPathCache($GLOBALS['patchwork_path'], PATCHWORK_PATH_LEVEL, PATCHWORK_PROJECT_PATH, PATCHWORK_ZCACHE);
                if (!$db = dba_popen(PATCHWORK_PROJECT_PATH . '.patchwork.paths.db', 'rd', /*<*/$a/*>*/)) exit;
            }
        }

        $base = dba_fetch($file, $db);
/**/}
/**/else
/**/{
        $base = md5($file) . /*<*/'.' . substr(md5(PATCHWORK_PROJECT_PATH), -6) . '.path.txt'/*>*/;
        $base = PATCHWORK_ZCACHE . $base[0] . '/' . $base[1] . '/' . $base;
        if (file_exists($base)) $base = file_get_contents($base);
        else $base = false;
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

// Private use for the preprocessor

function &patchwork_autoload_marker($marker, &$ref) {return $ref;}
