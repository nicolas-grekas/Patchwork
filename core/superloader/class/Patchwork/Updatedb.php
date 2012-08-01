<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

/**
 * Updatedb implements an mlocate like strategy to build a database of existing paths in a set of directories.
 */
class Patchwork_Updatedb
{
    function buildPathCache($paths, $last, $cwd, $zcache)
    {
        $parentPaths = array();
        $populated = array();
        $old_db_line = false;

        if (file_exists($cwd . '.patchwork.paths.txt'))
        {
            $old_db = @fopen($cwd . '.patchwork.paths.txt', 'rb');
            $old_db && $old_db_line = fgets($old_db);
        }
        else $old_db = false;

        $tmp = $cwd . '.~' . uniqid(mt_rand(), true);
        $db = fopen($tmp, 'wb');

        $paths = array_flip($paths);
        unset($paths[$cwd]);
        uksort($paths, array($this, 'dirCmp'));

        foreach ($paths as $h => $level)
        {
            $this->populatePathCache($populated, $old_db, $old_db_line, $db, $parentPaths, $paths, substr($h, 0, -1), $level, $last);
        }

        $db && fclose($db);
        $old_db && fclose($old_db);

        $h = $cwd . '.patchwork.paths.txt';
        '\\' === DIRECTORY_SEPARATOR && file_exists($h) && @unlink($h);
        rename($tmp, $h) || unlink($tmp);

        if (function_exists('dba_handlers'))
        {
            $h = array('cdb','db2','db3','db4','qdbm','gdbm','ndbm','dbm','flatfile','inifile');
            $h = array_intersect($h, dba_handlers());
            $h || $h = dba_handlers();
            if ($h) foreach ($h as $db) if ($h = @dba_open($tmp, 'nd', $db, 0600)) break;
        }
        else $h = false;

        if ($h)
        {
            foreach ($parentPaths as $paths => &$level)
            {
                sort($level);
                dba_insert($paths, implode(',', $level), $h);
            }

            dba_close($h);

            $h = $cwd . '.patchwork.paths.db';
            '\\' === DIRECTORY_SEPARATOR && file_exists($h) && @unlink($h);
            rename($tmp, $h) || unlink($tmp);
        }
        else
        {
            $db = false;

            foreach ($parentPaths as $paths => &$level)
            {
                sort($level);
                $paths = md5($paths) . /*<*/'.' . substr(md5($cwd), -6) . '.path.txt'/*>*/;
                $h = $zcache . $paths[0] . DIRECTORY_SEPARATOR . $paths[1] . DIRECTORY_SEPARATOR;
                file_exists($h) || mkdir($h, 0700, true);
                file_put_contents($h . $paths, implode(',', $level));
            }
        }

        return $db;
    }

    protected function populatePathCache(&$populated, &$old_db, &$old_db_line, &$db, &$parentPaths, &$paths, $root, $level, $last, $prefix = '', $subdir = '/')
    {
        // Kind of updatedb with mlocate strategy

        $dir = $root . ('\\' === DIRECTORY_SEPARATOR ? strtr($subdir, '/', '\\') : $subdir);

        if ('/' === $subdir)
        {
            if (isset($populated[$dir])) return;

            $populated[$dir] = true;

            if ($level > $last)
            {
                $prefix = '/class';
                $parentPaths['class'][] = $level;
            }
        }

        if (false !== $old_db_line)
        {
            do
            {
                $h = explode('*', $old_db_line, 2);
                false !== strpos($h[0], '%') && $h[0] = rawurldecode($h[0]);

                if (0 <= $h[0] = $this->dirCmp($h[0], $dir))
                {
                    if (0 === $h[0] && @max(filemtime($dir), filectime($dir)) === (int) $h[1])
                    {
                        if ('/' !== $subdir && false !== strpos($h[1], '/0config.patchwork.php/'))
                        {
                            if (isset($paths[$dir]))
                            {
                                $populated[$dir] = true;

                                $root   = substr($dir, 0, -1);
                                $subdir = '/';
                                $level  = $paths[$dir];

                                if ($level > $last)
                                {
                                    $prefix = '/class';
                                    $parentPaths['class'][] = $level;
                                }
                                else $prefix = '';
                            }
                            else break;
                        }

                        $db && fwrite($db, $old_db_line);

                        $h = explode('/', $h[1]);
                        unset($h[0], $h[count($h)]);

                        foreach ($h as $file)
                        {
                            $h = $file[0];

                            $file = $subdir . substr($file, 1);
                            $parentPaths[substr($prefix . $file, 1)][] = $level;

                            $h && $this->populatePathCache($populated, $old_db, $old_db_line, $db, $parentPaths, $paths, $root, $level, $last, $prefix, $file . '/');
                        }

                        return;
                    }

                    break;
                }
            }
            while (false !== $old_db_line = fgets($old_db));
        }

        if ($h = @opendir($dir))
        {
            static $now;
            isset($now) || $now = time() - 1;

            $files = array();
            $dirs  = array();

            while (false !== $file = readdir($h)) if ('.' !== $file[0] && 'zcache' !== $file)
            {
                if (@is_dir($dir . $file)) $dirs[] = $file;
                else
                {
                    $files[] = $file;

                    if ('config.patchwork.php' === $file && '/' !== $subdir)
                    {
                        if (isset($paths[$dir]))
                        {
                            $populated[$dir] = true;

                            $root   = substr($dir, 0, -1);
                            $subdir = '/';
                            $level  = $paths[$dir];

                            if ($level > $last)
                            {
                                $prefix = '/class';
                                $parentPaths['class'][] = $level;
                            }
                            else $prefix = '';
                        }
                        else
                        {
                            closedir($h);
                            return;
                        }
                    }
                }
            }

            closedir($h);

            $h = strtr($dir, array('%' => '%25', "\r" => '%0D', "\n" => '%0A', '*' => '%2A'))
                    . '*' . min($now, @max(filemtime($dir), filectime($dir))) . '/';

            foreach ($files as $file)
            {
                $h .= '0' . $file . '/';
                $parentPaths[substr($prefix . $subdir . $file, 1)][] = $level;
            }

            if ($dirs)
            {
                '\\' === DIRECTORY_SEPARATOR || sort($dirs, SORT_STRING);
                $h .= '1' . implode('/1', $dirs) . '/';
            }

            $db && fwrite($db, $h . "\n");

            foreach ($dirs as $file)
            {
                $file = $subdir . $file;
                $parentPaths[substr($prefix . $file, 1)][] = $level;
                $this->populatePathCache($populated, $old_db, $old_db_line, $db, $parentPaths, $paths, $root, $level, $last, $prefix, $file . '/');
            }
        }
    }

    protected function dirCmp($a, $b)
    {
        $len = min(strlen($a), strlen($b));

        if ('\\' === DIRECTORY_SEPARATOR)
        {
            $a = strtoupper(strtr($a, '\\', '/'));
            $b = strtoupper(strtr($b, '\\', '/'));
        }

        for ($i = 0; $i < $len; ++$i)
        {
            if ($a[$i] !== $b[$i])
            {
                if ('/' === $a[$i]) return -1;
                if ('/' === $b[$i]) return  1;
                return strcmp($a[$i], $b[$i]);
            }
        }

        return strlen($a) - strlen($b);
    }
}
