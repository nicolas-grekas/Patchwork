<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


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
