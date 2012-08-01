<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


$_REQUEST = $_ENV = array(); // Using $_REQUEST and $_ENV is a bad practice


// Fix some $_SERVER variables

$_SERVER['HTTPS'] = isset($_SERVER['HTTPS']) && ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']) ? 'on' : null;

if (!isset($_SERVER['HTTP_HOST']) || '' !== trim($_SERVER['HTTP_HOST'], 'eiasntroludcmpghv.fb:-q102yx9jk3548w67z'))
{
    die('Invalid HTTP/1.1 Host header');
}

/**/if ('\\' === DIRECTORY_SEPARATOR)
/**/{
        // IIS compatibility

/**/    if (!isset($_SERVER['REQUEST_URI']))
            $_SERVER['REQUEST_URI'] = isset($_SERVER['HTTP_X_REWRITE_URL']) ? $_SERVER['HTTP_X_REWRITE_URL'] : $_SERVER['URL'];

/**/    if (!isset($_SERVER['SERVER_ADDR']))
            $_SERVER['SERVER_ADDR'] = '127.0.0.1';

/**/    if (!isset($_SERVER['QUERY_STRING']))
/**/    {
            $a = $_SERVER['REQUEST_URI'];
            $b = strpos($a, '?');
            $_SERVER['QUERY_STRING'] = false !== $b++ && isset($a[$b]) ? substr($a, $b) : '';
/**/    }
/**/}


// Convert Windows-1252 URLs to UTF-8 ones

if (!preg_match('//u', urldecode($a = $_SERVER['REQUEST_URI'])))
{
    function url_enc_utf8_dec_callback($m) {return urlencode(utf8_encode(urldecode($m[0])));}

    $a = $a !== utf8_decode($a) ? '/' : preg_replace_callback('/(?:%[89A-F][0-9A-F])+/i', 'url_enc_utf8_dec_callback', $a);

    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $a);

    exit;
}

if (false !== strpos($a, '/.'))
{
    if (false === $a = strpos($_SERVER['REQUEST_URI'], '?')) $a = $_SERVER['REQUEST_URI'];
    else $a = substr($_SERVER['REQUEST_URI'], 0, $a);
    $a = rawurldecode($a) . '/';

    if (false !== strpos($a, '/./') || false !== strpos($a, '/../'))
        die("Please resolve references to '.' and '..' before issuing your request.");
}


// Ensure input is UTF-8 valid

$a = array(&$_GET, &$_POST, &$_COOKIE);
foreach ($_FILES as &$v) $a[] = array(&$v['name'], &$v['type']);

$k = count($a);
for ($i = 0; $i < $k; ++$i)
{
    foreach ($a[$i] as &$v)
    {
        if (is_array($v)) $a[$k++] =& $v;
        else
        {
/**/        if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
/**/        {
/**/            if (ini_get_bool('magic_quotes_sybase'))
                    $v = str_replace("''", "'", $v);
/**/            else
                    $v = stripslashes($v);
/**/        }

            preg_match('//u', $v) || $v = utf8_encode($v);
        }
    }

    reset($a[$i]);
    unset($a[$i]);
}

unset($a, $v);
