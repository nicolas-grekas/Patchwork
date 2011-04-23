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


class Patchwork_Bootstrapper
{
    static

    $class   = __CLASS__,
    $manager = 'Manager';


    static function initLock($caller, $cwd)
    {
        $pwd = dirname($caller) . DIRECTORY_SEPARATOR;
        $cls = call_user_func(array(self::$class, 'load'), self::$manager, $pwd);
        self::$manager = new $cls(self::$class, $pwd, $cwd);
        return self::$manager->lock($caller);
    }

    static function getNextStep() {return self::$manager->getNextStep();}
    static function release()     {return self::$manager = self::$manager->release();}

    static function load($class, $dir)
    {
        $class = self::$class . '_' . $class;
        require $dir . 'class/' . strtr($class, '_', DIRECTORY_SEPARATOR) . '.php';
        return $class;
    }
}
