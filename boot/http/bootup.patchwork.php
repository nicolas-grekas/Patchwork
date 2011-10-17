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


$_REQUEST = array(); // $_REQUEST is an open door to security problems.


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


// Convert ISO-8859-1 URLs to UTF-8 ones

function url_enc_utf8_dec_callback($m) {return urlencode(utf8_encode(urldecode($m[0])));}

if (!preg_match('//u', urldecode($a = $_SERVER['REQUEST_URI'])))
{
    $a = $a !== patchwork_utf8_decode($a) ? '/' : preg_replace_callback('/(?:%[89A-F][0-9A-F])+/i', 'url_enc_utf8_dec_callback', $a);

    patchwork_http_bad_request('Requested URL is not a valid urlencoded UTF-8 string.', $a);
}


// Helper function

function patchwork_http_bad_request($message, $url)
{
    if (in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD')))
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $url);
    }
    else
    {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: text/html; charset=utf-8');

        $message = htmlspecialchars($message);
        $url = htmlspecialchars($url);

        echo <<<EOHTML
<!doctype html>
<title>400 Bad Request</title>
<h1>400 Bad Request</h1>
<p>{$message}<br><br>Are you trying to reach <a href="{$url}">{$url}</a>&nbsp;?</p>
EOHTML;
    }

    exit;
}


// Input normalization

/**/$h = extension_loaded('mbstring') && ini_get_bool('mbstring.encoding_translation') && 'UTF-8' === strtoupper(ini_get('mbstring.http_input'));
/**/if (!$h || (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()))
/**/{
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
/**/                if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
/**/                {
/**/                    if (ini_get_bool('magic_quotes_sybase'))
                            $v = str_replace("''", "'", $v);
/**/                    else
                            $v = stripslashes($v);
/**/                }

/**/                if (!$h)
/**/                {
/**/                    if (extension_loaded('iconv') && '§' === @iconv('UTF-8', 'UTF-8//IGNORE', "§\xE0"))
/**/                    {
                            $v = @iconv('UTF-8', 'UTF-8//IGNORE', $v);
/**/                    }
/**/                    else
/**/                    {
                            // From http://www.w3.org/International/questions/qa-forms-utf-8
                            preg_match_all("/(?:[\\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}){1,50}/", $v, $b);
                            $v = implode('', $b[0]);
/**/                    }
/**/                }
                }
            }

            reset($a[$i]);
            unset($a[$i]);
        }

        unset($a, $v);
/**/}
