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

use Patchwork           as p;
use Patchwork\Exception as e;

class AntiCsrf extends p
{
    protected static $entitiesRx = "'&(nbsp|iexcl|cent|pound|curren|yen|euro|brvbar|sect|[AEIOUYaeiouy]?(?:uml|acute)|copy|ordf|laquo|not|shy|reg|macr|deg|plusmn|sup[123]|micro|para|middot|[Cc]?cedil|ordm|raquo|frac(?:14|12|34)|iquest|[AEIOUaeiou](?:grave|circ)|[ANOano]tilde|[Aa]ring|(?:AE|ae|sz)lig|ETH|times|[Oo]slash|THORN|eth|divide|thorn|quot|lt|gt|amp|[xX][0-9a-fA-F]+|[0-9]+);'";


    static function scriptAlert()
    {
        p::setMaxage(0);

        if (p::$catchMeta) p::$metaInfo[1] = array('private');

        if ('-' === strtr(self::$requestMode, '-tpax', '#----'))
        {
            $a = '';

            $cache = p::getContextualCachePath('agentArgs/' . p::$agentClass, 'txt');

            if (file_exists($cache))
            {
                $h = fopen($cache, 'r+b');
                if (!$a = fread($h, 1))
                {
                    rewind($h);
                    fwrite($h, $a = '1');

                    p::touch('public/templates/js');

                    p::updateAppId();
                }

                fclose($h);
            }

            throw new e\PrivateResource($a);
        }

        user_error('Potential JavaScript-Hijacking. Stopping !');

        p::disable(true);
    }

    static function postAlert()
    {
        user_error('Potential Cross Site Request Forgery. $_POST and $_FILES are not reliable. Erasing !');
    }

    static function appendToken($f)
    {
        $f = $f[0];

        // Anti-CSRF token is appended only to local application's form

        // Extract the action attribute
        if (1 < preg_match_all('#\saction\s*=\s*(["\']?)(.*?)\1([^>]*)>#iu', $f, $a, PREG_SET_ORDER)) return $f;

        if ($a)
        {
            $a = $a[0];
            $a = trim($a[1] ? $a[2] : ($a[2] . $a[3]));

            if (0 !== strncmp($a, p::$base, strlen(p::$base)))
            {
                // Decode html encoded chars
                if (false !== strpos($a, '&')) $a = preg_replace_callback(self::$entitiesRx, array(__CLASS__, 'translateHtmlEntities'), $a);

                // Build absolute URI
                if (preg_match("'^[^:/]*://([^/]*)'", $a, $host))
                {
                    $a = substr($a, strlen($host[0]));
                    $host = $host[1];
                }
                else
                {
                    $host = substr(p::$host, 7, -1);
                    0 === strncmp($host, '/', 1) && $host = substr($host, 1);

                    if (0 !== strncmp($a, '/', 1))
                    {
                        static $uri = false;

                        if (!$uri)
                        {
                            $uri = $_SERVER['REQUEST_URI'];

                            if (false !== ($b = strpos($uri, '?'))) $uri = substr($uri, 0, $b);

                            $uri = dirname($uri . ' ');

                            if (
                                   ''   === $uri
                                || '/'  === $uri
                                || '\\' === $uri
                            )    $uri  = '/';
                            else $uri .= '/';

                            $uri = preg_replace("'/[./]*(?:/|$)'", '/', $uri);
                        }

                        $a = $uri . $a;
                    }
                }

                if (false !== ($b = strpos($a, '?'))) $a = substr($a, 0, $b);
                if (false !== ($b = strpos($a, '#'))) $a = substr($a, 0, $b);

                // Resolve relative paths
                if (false !== strpos($a, '/.'))
                {
                    $b = explode('/', substr($a, 1));
                    $a = array();

                    foreach ($b as $b) switch ($b)
                    {
                    case '..': $a && array_pop($a);
                    case '.' : break;
                    default  : $a[] = $b;
                    }

                    $a = '/' . implode('/', $a);
                }

                '/' !== substr($a, -1) && $a .= '/';


                // Check if action is in our anti-CSRF cookie area

                if ($b = $CONFIG['session.cookie_domain'])
                {
                    0 === strncmp($b, '.', 1) && $b = substr($b, 1);
                    if ($host !== $b && substr($host, -1 - strlen($b)) !== '.' . $b) return $f;
                }
                else
                {
                    $b = substr(p::$host, 7, -1);

                    0 === strncmp($b, '/', 1) && $b = substr($b, 1);
                    if ($host !== $b) return $f;
                }

                if (!$b = $CONFIG['session.cookie_path'])
                {
                    $b = strpos(p::$base, '?');
                    $b = false === $b ? p::$base : substr(p::$base, 0, $b);
                    $b = substr($b, strlen(p::$host)-1);
                    $b = dirname($b . ' ');
                }

                '/' === substr($b, -1) || $b .= '/';

                if (0 !== strncmp($a, $b, strlen($b))) return $f;
            }
        }

        static $appendedHtml = false;

        if (!$appendedHtml)
        {
            $appendedHtml = !p::$binaryMode ? 'syncCSRF()' : '(function(){var d=document,f=d.forms;f=f[f.length-1].T$.value=d.cookie.match(/(^|; )T\\$=([-_0-9a-zA-Z]+)/)[2]})()';
            $appendedHtml = '<input type="hidden" name="T$" value="' . (empty($_COOKIE['JS']) ? self::$antiCsrfToken : '') . "\"><script>{$appendedHtml}</script>";
        }

        return $f . $appendedHtml;
    }

    protected static function translateHtmlEntities($c)
    {
        static $table = false;

        if (!$table) $table = array_flip(get_html_translation_table(HTML_ENTITIES));

        if (isset($table[$c[0]])) return utf8_encode($table[$c[0]]);

        $c = strtolower($c[1]);

        if ('x' == $c[0]) $c = hexdec(substr($c, 1));

        $c = sprintf('%08x', (int) $c);

        if (isset($c[8])) return '';

        $r = '';

        do
        {
            if (0 !== strncmp($c, '00', 2)) $r .= chr(hexdec(substr($c, 0, 2)));

            $c = substr($c, 2);
        }
        while ($c);

        return $r;
    }
}
