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


class patchwork_bootstrapper
{
    static

    $pwd,
    $cwd,
    $paths,
    $zcache,
    $last,
    $appId;


    protected static

    $bootstrapper,
    $caller;


    static function initialize($caller, $cwd)
    {
        self::$cwd = empty($cwd) ? '.' : $cwd;
        self::$cwd = rtrim(self::$cwd, '/\\') . DIRECTORY_SEPARATOR;
        self::$pwd = dirname($caller) . DIRECTORY_SEPARATOR;
        self::$caller = $caller;

        require dirname(__FILE__) . '/PHP/Parser.php';
        require dirname(__FILE__) . '/PHP/Parser/normalizer.php';
        require dirname(__FILE__) . '/PHP/Parser/scream.php';
        require dirname(__FILE__) . '/PHP/Parser/staticState.php';
        require dirname(__FILE__) . '/bootstrapper/bootstrapper.php';

        self::$bootstrapper = new patchwork_bootstrapper_bootstrapper(self::$cwd);
    }

    static function getLock()             {return self::$bootstrapper->getLock(self::$caller);}
    static function isReleased()          {return self::$bootstrapper->isReleased();}
    static function isPathInfoSupported() {return self::$bootstrapper->isPathInfoSupported();}
    static function release()             {return self::$bootstrapper->release();}
    static function getCompiledFile()     {return self::$bootstrapper->getCompiledFile();}
    static function preprocessorPass1()   {return self::$bootstrapper->preprocessorPass1();}
    static function preprocessorPass2()   {return self::$bootstrapper->preprocessorPass2();}
    static function loadConfigFile($type) {return self::$bootstrapper->loadConfigFile($type);}
    static function initConfig()          {return self::$bootstrapper->initConfig();}

    static function initInheritance()
    {
        self::$cwd = rtrim(patchwork_realpath(self::$cwd), '/\\') . DIRECTORY_SEPARATOR;

        $a = self::$bootstrapper->getLinearizedInheritance(self::$pwd);

        self::$paths =& $a[0];
        self::$last  =  $a[1];
        self::$appId =  $a[2];
    }

    static function initZcache()
    {
        self::$zcache = self::$bootstrapper->getZcache(self::$paths, self::$last);
    }

    static function updatedb()
    {
        return self::$bootstrapper->updatedb(self::$paths, self::$last, self::$zcache);
    }

    static function alias($function, $alias, $args, $return_ref = false)
    {
        return self::$bootstrapper->alias($function, $alias, $args, $return_ref);
    }

    static function fixParentPaths($pwd)
    {
        self::$paths  = $GLOBALS['patchwork_path'];
        self::$last   = PATCHWORK_PATH_LEVEL;
        self::$zcache = PATCHWORK_ZCACHE;

        self::initialize($pwd . '-', PATCHWORK_PROJECT_PATH);

        $db = self::updatedb();
        $db = dba_popen(PATCHWORK_PROJECT_PATH . '.patchwork.paths.db', 'rd', $db);

        if (!$db) exit;

        return $db;
    }
}
