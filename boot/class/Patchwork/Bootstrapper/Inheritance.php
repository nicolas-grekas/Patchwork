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


class Patchwork_Bootstrapper_Inheritance
{
    public

    $rootPath,
    $appId = 0;

    protected static $cache;


    function linearizeGraph($root_path, $top_path)
    {
        // Get include_path

        $paths = array();

        foreach (explode(PATH_SEPARATOR, get_include_path()) as $a)
        {
            $a = patchwork_realpath($a);

            if ($a && @opendir($a))
            {
                closedir();
                $paths[] = rtrim($a, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }
        }


        // Linearize applications inheritance graph

        $this->rootPath = $root_path;
        self::$cache = array();
        $a = $this->c3mro($root_path, $top_path);
        $a = array_slice($a, 1);
        $a[] = $this->rootPath;


        $paths = array_diff($paths, $a, array('', patchwork_realpath('.')));
        $paths = array_merge($a, $paths);


        return array(&$paths, count($a) - 1, $this->appId);
    }

    // C3 Method Resolution Order (like in Python 2.3) for multiple application inheritance
    // See http://python.org/2.3/mro.html

    protected function c3mro($realpath, $top_path = false)
    {
        $resultSeq =& self::$cache[$realpath];

        // If result is cached, return it
        if (null !== $resultSeq) return $resultSeq;

        $parent = $this->getParentApps($realpath);

        // If no parent app, result is trival
        if (!$parent && !$top_path) return $resultSeq = array($realpath);

        if ($top_path) array_unshift($parent, $top_path);

        // Compute C3 MRO
        $seqs = array_merge(
            array(array($realpath)),
            array_map(array($this, 'c3mro'), $parent),
            array($parent)
        );
        $resultSeq = array();
        $parent = false;

        while (1)
        {
            if (!$seqs)
            {
                false !== $top_path && self::$cache = array();
                return $resultSeq;
            }

            unset($seq);
            $notHead = array();
            foreach ($seqs as $seq)
                foreach (array_slice($seq, 1) as $seq)
                    $notHead[$seq] = 1;

            foreach ($seqs as &$seq)
            {
                $parent = reset($seq);

                if (isset($notHead[$parent])) $parent = false;
                else break;
            }

            if (false === $parent)
            {
                die('Patchwork error: Inconsistent application hierarchy in ' . $realpath . 'config.patchwork.php');
            }

            $resultSeq[] = $parent;

            foreach ($seqs as $k => &$seq)
            {
                if ($parent === current($seq)) unset($seqs[$k][key($seq)]);
                if (!$seqs[$k]) unset($seqs[$k]);
            }
        }
    }

    protected function getParentApps($realpath)
    {
        $parent = array();
        $config = $realpath . 'config.patchwork.php';


        // Get config's source and clean it

        $this->appId += filemtime($config);

        $source = file_get_contents($config);

        if ($source = token_get_all($source))
        {
            $i = T_INLINE_HTML === $source[0][0] ? 2 : 1;
            $len = count($source);

            while ($i < $len)
            {
                $a = $source[$i++];

                if (in_array($a[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true))
                {
                    if (T_COMMENT === $a[0] && '#patchwork' === rtrim(substr($a[1], 0, 11), " \t"))
                    {
                        $parent[] = trim(substr($a[1], 11));
                    }
                }
                else break;
            }
        }


        // Parent's config file path is relative to the current application's directory

        $len = count($parent);
        for ($i = 0; $i < $len; ++$i)
        {
            $a = $parent[$i];

            if ('__patchwork__' == substr($a, 0, 13)) $a = dirname($this->rootPath) . DIRECTORY_SEPARATOR . substr($a, 13);

            if ('/' !== $a[0] && '\\' !== $a[0] && ':' !== $a[1]) $a = $realpath . $a;

            if ('/*' === substr(strtr($a, '\\', '/'), -2) && $a = patchwork_realpath(substr($a, 0, -2)))
            {
                $a = rtrim($a, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $source = array();

                $p = array($a);
                $parent[$i] = $a;

                $pLen = 1;
                for ($j = 0; $j < $pLen; ++$j)
                {
                    $d = $p[$j];

                    if (file_exists($d . 'config.patchwork.php')) $source[] = $d;
                    else if ($h = opendir($d))
                    {
                        while (false !== $file = readdir($h))
                        {
                            '.' !== $file[0] && is_dir($d . $file) && $p[$pLen++] = $d . $file . DIRECTORY_SEPARATOR;
                        }
                        closedir($h);
                    }

                    unset($p[$j]);
                }


                $p = array();

                foreach ($source as $source)
                {
                    if ($this->rootPath !== $source && $realpath !== $source)
                    {
                        foreach ($this->c3mro($source) as $a)
                        {
                            if (false !== $a = array_search($a, $p))
                            {
                                $p[$a] = $source;
                                $source = false;
                                break;
                            }
                        }

                        $source && $p[] = $source;
                    }
                }

                $a = count($p);

                array_splice($parent, $i, 1, $p);

                $i += --$a;
                $len += $a;
            }
            else
            {
                if (!file_exists($a . '/config.patchwork.php'))
                {
                    die('Patchwork error: Missing file ' . rtrim(strtr($parent[$i], '\\', '/'), '/') . '/config.patchwork.php in ' . $config);
                }

                $a = patchwork_realpath($a);
                $a = rtrim($a, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

                if ($this->rootPath === $a) unset($parent[$i]);
                else $parent[$i] = $a;
            }
        }

        return $parent;
    }
}
