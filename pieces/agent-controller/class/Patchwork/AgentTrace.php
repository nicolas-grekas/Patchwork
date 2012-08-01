<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork;

use Patchwork as p;

class AgentTrace extends p
{
    static function resolve($agent)
    {
        static $cache = array();

        if (isset($cache[$agent])) return $cache[$agent];
        else $cache[$agent] =& $trace;

        $args = array();
        $BASE = p::__BASE__();

        $agent = rawurlencode($agent);
        $agent = str_replace(
            array('%21','%7E','%2A','%28','%29','%2C','%2F','%3A','%40','%24','%3B'),
            array('!',  '~',  '*',  '(',  ')',  ',',  '/',  ':',  '@',  '$',  ';'  ),
            $agent
        );

        $agent = p::base($agent, true);
        $agent = preg_replace("'^.*?://[^/]*'", '', $agent);

        $h = patchwork_http_socket($_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], isset($_SERVER['HTTPS']));

        $keys  = p::$lang;
        $keys  = "GET {$agent}?p:=k:{$keys} HTTP/1.0\r\n";
        $keys .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
        $keys .= "Connection: close\r\n\r\n";

        fwrite($h, $keys);
        $keys = array();
        while (false !== $a = fgets($h)) $keys[] = $a;
        fclose($h);

        $h = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';
        $h = "/w\.k\((-?[0-9]+),($h),($h),($h),\[((?:$h(?:,$h)*)?)\]\)/su";

        if (!preg_match($h, implode('', $keys), $keys))
        {
            user_error('Error while getting meta info data for ' . htmlspecialchars($agent));
            p::disable(true);
        }

        $appId = (int) $keys[1];
        $base = stripcslashes(substr($keys[2], 1, -1));
        $agent = stripcslashes(substr($keys[3], 1, -1));
        $a = stripcslashes(substr($keys[4], 1, -1));
        $keys = eval('return array(' . $keys[5] . ');');

        if ('' !== $a)
        {
            $args['__0__'] = $a;

            $i = 0;
            foreach (explode('/', $a) as $a) $args['__' . ++$i . '__'] = $a;
        }

        if ($base === $BASE) $appId = $base = false;
        else p::watch('foreignTrace');

        return $trace = array($appId, $base, $agent, $keys, $args);
    }

    static function send($agent)
    {
        header('Content-Type: text/javascript');
        p::setMaxage(-1);

        echo 'w.k(',
            p::$appId, ',',
            jsquote( p::$base ), ',',
            jsquote( 'agent_index' === $agent ? '' : p\Superloader::class2file(substr($agent, 6)) ), ',',
            jsquote( isset($_GET['__0__']) ? $_GET['__0__'] : '' ), ',',
            '[', implode(',', array_map('jsquote', p::agentArgs($agent))), ']',
        ')';
    }
}
