<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork as p;

class TRANSLATOR
{
    protected static

    $adapter,
    $cache;


    static function get($string, $lang, $usecache)
    {
        if ('' === $string || '__' == $lang) return $string;

        $hash = md5($string);
        $cache = '';

/**/    if (DEBUG)
            $usecache = false;

        if ($usecache && $id = p::$agentClass)
        {
            $id = p::getContextualCachePath('lang/' . substr($id, 6), 'ser');
            if (!isset(self::$cache[$id]))
            {
                if (file_exists($id)) $cache = unserialize(file_get_contents($id));

                self::$cache[$id] = $cache ? array(false, false, &$cache) : array(false, true, array());
            }

            $cache =& self::$cache[$id][$hash];

            if ('' !== (string) $cache) return $cache;
            else self::$cache[$id][0] = true;
        }

        $cache = self::$adapter->search($string, $lang);

        if ('' === (string) $cache) $cache = $string;

        return $cache;
    }


    static function __init()
    {
        self::$cache = array();

        $adapter = $CONFIG['translator.adapter'] ? 'adapter_translator_' . $CONFIG['translator.adapter'] : __CLASS__;
        self::$adapter = new $adapter($CONFIG['translator.options']);
        self::$adapter->open();
    }

    static function __free()
    {
        self::$adapter->close();

        foreach (self::$cache as $file => &$cache) if ($cache[0])
        {
            $data = serialize($cache[2]);

            p::writeFile($file, $data);
            if ($cache[1]) p::writeWatchTable('translator', $file, false);
        }
    }


    /* Adapter interface */

    function open() {}
    function search($string, $lang)
    {
/**/    if (DEBUG)
            return "‘{$string}’";
        return $string;
    }
    function close() {}
}
