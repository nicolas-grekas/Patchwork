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


class tool_url
{
    /**
     * Sends the request to the webserver but don't wait for the response.
     */
    static function touch($url)
    {
        $url = Patchwork::base($url, true);

        if (!preg_match("'^http(s?)://(.*?)((?::[0-9]+)?)(/.*)$'", $url, $h)) throw new Exception('Illegal URL');

        $url  = "GET {$h[4]} HTTP/1.0\r\n";
        $url .= "Host: {$h[2]}\r\n";
        $url .= "Connection: close\r\n\r\n";

        try
        {
            $h = patchwork_http_socket($h[2], substr($h[3], 1), $h[1], 5);

            socket_set_blocking($h, 0);

            do
            {
                $len = fwrite($h, $url);
                $url = substr($url, $len);
            }
            while (false !== $len && false !== $url);

            fclose($h);
        }
        catch (Exception $h)
        {
            W($h);
        }
    }
}
