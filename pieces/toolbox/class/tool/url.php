<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class tool_url
{
    /**
     * Sends the request to the webserver but don't wait for the response.
     */
    static function touch($url)
    {
        $url = Patchwork::base($url, true);

        if (!preg_match("'^http(s?)://([^:/]*)((?::[0-9]+)?)(/.*)$'", $url, $h)) throw new Exception('Illegal URL');

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
            user_error($h->getMessage());
        }
    }
}
