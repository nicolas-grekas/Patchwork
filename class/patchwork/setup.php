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


/**
 * Hook executed only once, when application is initialized
 */

class patchwork_setup
{
    protected static function execute()
    {
        patchwork::touch('appId');
    }


    static function hook()
    {
        $G = $_GET; $P = $_POST; $C = $_COOKIE; $F = $_FILES;
        $_GET = $_POST = $_COOKIE = $_FILES = array();

        self::execute();

        $_GET = $G; $_POST = $P; $_COOKIE = $C; $_FILES = $F;
    }


    static $isPathInfoSUpported = null;

    static function __constructStatic()
    {
        self::$isPathInfoSUpported = self::isPathInfoSupported();
    }

    protected static function isPathInfoSupported()
    {
        switch (true)
        {
        case isset($_SERVER['REDIRECT_PATCHWORK_REQUEST']):
        case isset($_SERVER['PATCHWORK_REQUEST']):
        case isset($_SERVER['ORIG_PATH_INFO']):
        case isset($_SERVER['PATH_INFO']): return true;
        }

        // Check if the webserver supports PATH_INFO

        $h = patchwork_http_socket($_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], isset($_SERVER['HTTPS']));

        $a = strpos($_SERVER['REQUEST_URI'], '?');
        $a = false === $a ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $a);
        '/' === substr($a, -1) && $a .= basename(isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : $_SERVER['SCRIPT_NAME']);

        $a  = "GET {$a}/:?p:=exit HTTP/1.0\r\n";
        $a .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
        $a .= "Connection: close\r\n\r\n";

        fwrite($h, $a);
        $a = fgets($h, 14);
        fclose($h);

        return (bool) strpos($a, ' 200');
    }
}
