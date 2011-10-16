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

namespace Patchwork;

/**
 * Hook executed only once, when application is initialized
 */

class Setup
{
    protected static function execute()
    {
    }

    static function hook()
    {
        $G = $_GET; $P = $_POST; $C = $_COOKIE; $F = $_FILES;
        $_GET = $_POST = $_COOKIE = $_FILES = array();

        self::execute();

        $_GET = $G; $_POST = $P; $_COOKIE = $C; $_FILES = $F;
    }
}
