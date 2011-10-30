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


    static function initialize($caller, $cwd)
    {
        $manager = self::$class . '_' . self::$manager;
        $pwd = implode(DIRECTORY_SEPARATOR, array(dirname($caller), 'core', 'boot', ''));
        self::$manager = new $manager(self::$class, $caller, $pwd, $cwd);
    }

    static function getNextStep()
    {
        return self::$manager->getNextStep();
    }
}
