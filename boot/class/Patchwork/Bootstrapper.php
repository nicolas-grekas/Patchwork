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


class Patchwork_Bootstrapper
{
    static

    $class   = __CLASS__,
    $manager = 'Manager';


    static function initLock($caller, $cwd)
    {
        $pwd = dirname($caller) . DIRECTORY_SEPARATOR . 'boot' . DIRECTORY_SEPARATOR;
        $cls = self::$class . '_' . self::$manager;
        self::$manager = new $cls(self::$class, $pwd, $cwd);
        return self::$manager->lock($caller);
    }

    static function getNextStep() {return self::$manager->getNextStep();}
    static function release()     {return self::$manager = self::$manager->release();}
}
