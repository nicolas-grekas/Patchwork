<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
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


    static function __constructStatic()
    {
        self::$cache = array();

        $adapter = $CONFIG['translator.adapter'] ? 'adapter_translator_' . $CONFIG['translator.adapter'] : __CLASS__;
        self::$adapter = new $adapter($CONFIG['translator.options']);
        self::$adapter->open();
    }

    static function __destructStatic()
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
