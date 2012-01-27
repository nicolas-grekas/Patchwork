<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Override_Bug48805
{
    static function stream_socket_client($remote_socket, &$errno = null, &$errstr = null, $timeout = null, $flags = STREAM_CLIENT_CONNECT, $context = null)
    {
        if (false !== strpos($remote_socket, '['))
        {
            if (null === $context) $context = stream_context_create(array('socket' => array('bindto' => '[::]:0')));
            else
            {
                $o = stream_context_get_options();
                isset($o['socket']['bindto']) || stream_context_set_option($context, 'socket', 'bindto', '[::]:0');
            }
        }

        return stream_socket_client($remote_socket, $errno, $errstr, $timeout, $flags, $context);
    }

    static function fsockopen($hostname, $port = -1, &$errno = null, &$errstr = null, $timeout = null)
    {
        if (false !== strpos($hostname, '['))
        {
            -1 != $port && $hostname .= ':' . $port;
            $context = stream_context_create(array('socket' => array('bindto' => '[::]:0')));
            return stream_socket_client($hostname, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        }
        else return fsockopen($hostname, $port, $errno, $errstr, $timeout);
    }
}
