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

class Language
{
    static function httpNegociate($lang_list)
    {
        static $vary = true;
        $vary && $vary = header('Vary: Accept-Language', false);
        $lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : false;

        $_SERVER['PATCHWORK_LANG'] = $lang = self::getBest(array_keys($lang_list), $lang);

        $lang = $lang_list[$lang];
        $lang = implode($lang, explode('__', $_SERVER['PATCHWORK_BASE'], 2));
        $lang .= str_replace('%2F', '/', rawurlencode($_SERVER['PATCHWORK_REQUEST']));

        if (!isset($_SERVER['REDIRECT_QUERY_STRING'])) empty($_SERVER['QUERY_STRING']) || $lang .= '?' . $_SERVER['QUERY_STRING'];
        else '' === $_SERVER['REDIRECT_QUERY_STRING'] || $lang .= '?' . $_SERVER['REDIRECT_QUERY_STRING'];

        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $lang);
        header('Expires: ' . gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $CONFIG['maxage']) . ' GMT');
        header('Cache-Control: max-age=' . $CONFIG['maxage'] .',' . ($GLOBALS['patchwork_private'] ? 'private' : 'public'));
        exit;
    }

    static function getBest($supported, $lang)
    {
        $candidates = array();

        if ($lang) foreach (explode(',', $lang) as $item)
        {
            $item = explode(';q=', $item);
            if ($item[0] = trim($item[0])) $candidates[ $item[0] ] = isset($item[1]) ? (double) trim($item[1]) : 1;
        }

        $lang = reset($supported);
        $qMax = 0;

        foreach ($candidates as $l => &$q) if (
            $q > $qMax
            && (
                in_array($l, $supported)
                || (
                    ($tiret = strpos($l, '-'))
                    && in_array($l = substr($l, 0, $tiret), $supported)
                )
            )
        )
        {
            $qMax = $q;
            $lang = $l;
        }

        return (string) $lang;
    }
}
