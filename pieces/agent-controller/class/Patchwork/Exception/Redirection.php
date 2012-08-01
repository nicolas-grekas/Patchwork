<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\Exception;

use Patchwork as p;

class Redirection extends \Exception
{
    protected $url;

    function __construct($url)
    {
        $url = (string) $url;
        $url = '' === $url ? '' : (preg_match("'^([^:/]+:/|\.+)?/'", $url) ? $url : (p::__BASE__() . ('index' === $url ? '' : $url)));

        if ('.' === substr($url, 0, 1)) user_error('Current redirection behaviour with relative URLs may change in a future version of Patchwork. As long as this notice appears, using relative URLs is strongly discouraged.');

        $this->url = $url;
    }

    function redirect($javascript)
    {
        p::disable();

        $url = $this->url;

        if ($javascript)
        {
            $url = 'location.replace(' . ('' !== $url
                ? "'" . addslashes($url) . "'"
                : 'location')
            . ')';

            header('Content-Length: ' . strlen($url));
            echo $url;
        }
        else
        {
            header('HTTP/1.1 302 Found');
            header('Location: ' . ('' !== $url ? $url : $_SERVER['REQUEST_URI']));
        }
    }
}
