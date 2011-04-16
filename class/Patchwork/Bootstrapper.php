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

require dirname(__FILE__) . '/Bootstrapper/Bootstrapper.php';


class Patchwork_Bootstrapper
{
    static

    $pwd,
    $cwd,
    $paths,
    $zcache,
    $last,
    $appId;


    protected static

    $bootstrapper;


    static function initLock($caller, $cwd)
    {
        self::$cwd = empty($cwd) ? '.' : $cwd;
        self::$cwd = rtrim(self::$cwd, '/\\') . DIRECTORY_SEPARATOR;
        self::$pwd = dirname($caller) . DIRECTORY_SEPARATOR;
        self::$bootstrapper = new Patchwork_Bootstrapper_Bootstrapper(self::$cwd);

        return self::$bootstrapper->initLock($caller);
    }

    static function getBootstrapper()     {return self::free(self::$bootstrapper->getBootstrapper());}
    static function loadNextStep()        {return self::$bootstrapper->loadNextStep();}
    static function preprocessorPass1()   {return self::$bootstrapper->preprocessorPass1();}
    static function preprocessorPass2()   {return self::$bootstrapper->preprocessorPass2();}
    static function initConfig()          {return self::$bootstrapper->initConfig();}
    static function release()             {return self::free(self::$bootstrapper->release());}

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

    static function override($function, $override, $args, $return_ref = false)
    {
        return self::$bootstrapper->override($function, $override, $args, $return_ref);
    }

    protected static function free($return)
    {
        self::$pwd = self::$cwd = self::$paths = self::$zcache = self::$last = self::$appId = self::$bootstrapper = null;
        return $return;
    }
}
