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


// Timezone settings

/**/if (!ini_get('date.timezone'))
    @date_default_timezone_set(date_default_timezone_get());


// Configure PCRE

/**/if (ini_get('pcre.backtrack_limit') < 5000000)
        ini_set('pcre.backtrack_limit', 5000000);

/**/if (ini_get('pcre.recursion_limit') < 10000)
        ini_set('pcre.recursion_limit', 10000);


// Helper functions

function strlencmp($a, $b) {return strlen($b) - strlen($a);}

function patchwork_http_socket($host, $port, $ssl, $timeout = 30)
{
    if ($port <= 0) $port = $ssl ? '443' : '80';
    $ssl = $ssl ? 'ssl' : 'tcp';

    if (false !== strpos($host, ':'))
    {
        // Workaround for http://bugs.php.net/48805

        if ('[]' !== substr($host, 0, 1) . substr($host, -1)) $host = '[' . $host . ']';
        $h = stream_context_create(array('socket' => array('bindto' => '[::]:0')));
        $h = stream_socket_client("{$ssl}://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $h);
    }
    else
    {
        strspn(substr($host, -1), '0123456789') || $host .= '.';
        $h = fsockopen("{$ssl}://{$host}", $port, $errno, $errstr, $timeout);
    }

    if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");

    return $h;
}


// Check HTTP validator

$patchwork_private = false;

/**/unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);

$a = isset($_SERVER['HTTP_IF_NONE_MATCH'])
    ? $_SERVER['HTTP_IF_NONE_MATCH']
    : isset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

if ($a)
{
    if (true === $a)
    {
        // Patch an IE<=6 bug when using ETag + compression
        $a = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $a = strtotime($a[0]);
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"' . dechex($a) . '"';
        $patchwork_private = true;
    }
    else if (27 === strlen($a) && 25 === strspn($a, '0123456789abcdef') && '""' === $a[0] . $a[26])
    {
        $b = PATCHWORK_ZCACHE . $a[1] .'/'. $a[2] .'/'. substr($a, 3, 6) .'.v.txt';
        if (file_exists($b) && substr(file_get_contents($b), 0, 8) === substr($a, 9, 8))
        {
            $private = substr($a, 17, 1);
            $maxage  = hexdec(substr($a, 18, 8));

            header('HTTP/1.1 304 Not Modified');
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $_SERVER['REQUEST_TIME'] + ($private || !$maxage ? 0 : $maxage)));
            header('Cache-Control: max-age=' . $maxage . ($private ? ',private,must' : ',public,proxy') . '-revalidate');
            exit;
        }
    }
}


// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with our own output compression.

/**/if (function_exists('apache_setenv'))
        apache_setenv('no-gzip','1');


/**/if (ini_get_bool('zlib.output_compression'))
        ini_set('zlib.output_compression', false);


// PHP session mechanism overloading

class sessionHandler implements ArrayAccess
{
    function offsetGet($k)     {$_SESSION = SESSION::getAll(); return $_SESSION[$k];}
    function offsetSet($k, $v) {$_SESSION = SESSION::getAll(); $_SESSION[$k] =& $v;}
    function offsetExists($k)  {$_SESSION = SESSION::getAll(); return isset($_SESSION[$k]);}
    function offsetUnset($k)   {$_SESSION = SESSION::getAll(); unset($_SESSION[$k]);}

    static $id;

    static function close()   {return true;}
    static function gc($life) {return true;}

    static function open($path, $name)
    {
        session_cache_limiter('');
        ini_set('session.use_only_cookies', true);
        ini_set('session.use_cookies', false);
        ini_set('session.use_trans_sid', false);
        return true;
    }

    static function read($id)
    {
        $_SESSION = SESSION::getAll();
        self::$id = $id;
        return '';
    }

    static function write($id, $data)
    {
        if (self::$id != $id) SESSION::regenerateId();
        return true;
    }

    static function destroy($id)
    {
        SESSION::regenerateId(true);
        return true;
    }
}

session_set_save_handler(
    array($k = 'sessionHandler', 'open'),
    array($k, 'close'),
    array($k, 'read'),
    array($k, 'write'),
    array($k, 'destroy'),
    array($k, 'gc')
);

$_SESSION = new sessionHandler;
