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

require dirname(dirname(__FILE__)) . '/C3mro.php';


class Patchwork_Bootstrapper_Inheritance
{
    protected

    $c3mro,
    $rootPath,
    $topPath,
    $appId = 0;


    function linearizeGraph($root_path, $top_path)
    {
        $this->rootPath = $root_path;
        $this->topPath  = $top_path;

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

        try
        {
            $this->c3mro = new Patchwork_C3mro(array($this, 'getParentApps'));
            $a = $this->c3mro->linearize($root_path);
        }
        catch (Patchwork_C3mro_InconsistentHierarchyException $e)
        {
            die('Patchwork error: Inconsistent application hierarchy in ' . $e->getMessage() . 'config.patchwork.php');
        }

        $a = array_slice($a, 1);
        $a[] = $this->rootPath;

        $paths = array_diff($paths, $a, array('', patchwork_realpath('.')));
        $paths = array_merge($a, $paths);

        return array($paths, count($a) - 1, $this->appId);
    }

    function getParentApps($realpath)
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
                        foreach ($this->c3mro->linearize($source) as $a)
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

        $this->rootPath === $realpath && array_unshift($parent, $this->topPath);

        return $parent;
    }
}
