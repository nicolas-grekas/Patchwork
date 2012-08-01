<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_Autoloader extends Patchwork_Superloader
{
    protected static

    $pool = false,
    $preproc = false;


    static function loadClass($req)
    {
/**/    if (50300 <= PHP_VERSION_ID && PHP_VERSION_ID < 50303) // Workaround http://bugs.php.net/50731
            isset($req[0]) && '\\' === $req[0] && $req = substr($req, 1);

        $lc_req = strtolower(strtr($req, '\\', '_'));

        $amark = $GLOBALS["a\x9D"];
        $GLOBALS["a\x9D"] = false;
        $bmark = $GLOBALS["b\x9D"];


        // Step 1 - Get basic info

        $i = strrpos($req, '__');
        $level = false !== $i ? substr($req, $i+2) : false;
        $isTop = false === $level || '' !== trim($level, '0123456789');

        if ($isTop)
        {
            // Top class
            $top = $req;
            $lc_top = $lc_req;
            $level = PATCHWORK_PATH_LEVEL;
        }
        else
        {
            // Preprocessor renammed class
            $top = substr($req, 0, $i);
            $lc_top = substr($lc_req, 0, $i);
            $level = min(PATCHWORK_PATH_LEVEL, '00' === $level ? -1 : (int) $level);
        }

        self::$preproc || self::$preproc = 'patchwork_preprocessor' === $lc_top;


        // Step 2 - Get source file

        $src = '';

        if ($customSrc =& self::$prefix && $a = strlen($lc_top))
        {
            // Look for a registered prefix autoloader

            $i = 0;
            $cache = array();

            do
            {
                $code = ord($lc_top[$i]);
                if (isset($customSrc[$code]))
                {
                    $customSrc =& $customSrc[$code];
                    isset($customSrc[-1]) && $cache[] = $customSrc[-1];
                }
                else break;
            }
            while (++$i < $a);

            if ($cache) do
            {
                $src = array_pop($cache);
                $src = $i < $a || !is_string($src) || function_exists($src) ? call_user_func($src, $top) : $src;
            }
            while (!$src && $cache);
        }

        unset($customSrc);

        if ($customSrc = '' !== (string) $src) {}
        else if ('_' !== substr($top, -1))
        {
            $src = self::class2file($top);
            $src = trim($src, '/') === $src ? "class/{$src}.php" : '';
        }

        $src && $src = patchworkPath($src, $a, $level, 0);


        // Step 3 - Get parent class

        $src || $a = -1;
        $isTop && ++$level;

        if ($level > $a)
        {
            do $parent = $top . '__' . (0 <= --$level ? $level : '00');
            while (!($parent_exists = self::exists($parent, false)) && $level > $a);
        }
        else
        {
            $parent = 0 <= $level ? $top . '__' . (0 < $level ? $level - 1 : '00') : false;
            $parent_exists = false;
        }


        // Step 4 - Load class definition

        $cache = false;

        if ($src && !$parent_exists)
        {
            $cache = self::class2cache($top . '.php', $level);

            $current_pool = false;
            $parent_pool =& self::$pool;
            self::$pool =& $current_pool;

            if (!(file_exists($cache) && (self::$turbo || filemtime($cache) > filemtime($src))))
            {
                if (self::$preproc)
                {
                    file_exists($cache) && unlink($cache);
                    copy($src, $cache);
                }
                else Patchwork_Preprocessor::execute($src, $cache, $level, $top, $isTop, false);
            }

            $current_pool = array();

            try
            {
                // Force fatal errors to be always reported
                $src = error_reporting(error_reporting() | /*<*/E_PARSE | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR/*>*/);
                patchwork_include($cache);
                error_reporting($src);
            }
            catch (Exception $cache)
            {
                error_reporting($src);
                throw $cache;
            }

            if ($parent && self::exists($req, false)) $parent = false;
            if (false !== $parent_pool) $parent_pool[$parent ? $parent : $req] = $cache;
        }


        // Step 5 - Finalize class loading

        $code = '';

        if (  $parent
            ? $code = self::exists($parent, true)
            : (self::exists($req, false) && !isset($GLOBALS["c\x9D"][$lc_req]))  )
        {
            if (false !== $a = strrpos($req, '\\'))
            {
                $ns     = substr($req, 0, $a + 1);
                $req    = substr($req,    $a + 1);
                $parent = substr($parent, $a + 1);
                $lc_req = substr($lc_req, $a + 1);
                $lc_ns  = strtolower(strtr($ns, '\\', '_'));
            }
            else $ns = $lc_ns = '';

            if ($parent)
            {
                $code .= " {$req} extends {$parent}{}\$GLOBALS['c\x9D']['{$lc_ns}{$lc_req}']=1;";
                $parent = strtolower($parent);

                if ($ns && function_exists('class_alias'))
                {
                    $code .= "\\class_alias('{$ns}{$req}','{$lc_ns}{$lc_req}');";
                }

                if (isset(self::$abstracts[$lc_ns . $parent]))
                {
                    $code = 'abstract ' . $code;
                    self::$abstracts[$lc_ns . $lc_req] = 1;
                }
            }
            else $parent = $lc_req;

            if ($isTop && class_exists($ns . $parent, false))
            {
                $a = "{$ns}{$parent}::i\x9D";
                if (defined($a) ? $lc_req === constant($a) : method_exists($ns . $parent, '__init'))
                {
                    $code .= "{$parent}::__init();";
                }

                $a = "{$ns}{$parent}::f\x9D";
                if (defined($a) ? $lc_req === constant($a) : method_exists($ns . $parent, '__free'))
                {
                    $a = "\\Patchwork_ShutdownHandler::\$destructors[]='{$lc_ns}{$parent}';";
                    PHP_VERSION_ID < 50300 && $a[0] = ' ';
                    $code .= $a;
                }
            }

            if ($ns)
            {
                $req    = $ns . $req;
                $parent = $lc_ns . $parent;
                $lc_req = $lc_ns . $lc_req;

                $ns = substr($ns, 0, -1);
                $ns = "namespace {$ns};";
            }

            if ($code) eval($ns . $code);
        }

        if (!self::$turbo || self::$preproc)
        {
            self::$preproc && self::$preproc = 'patchwork_preprocessor' !== $lc_top;
            return;
        }

        if (class_exists('Patchwork_Preprocessor', false) && Patchwork_Preprocessor::isRunning()) return;

        if ($code && isset($GLOBALS["c\x9D"][$parent]))
        {
            // Include class declaration in its closest parent

            $src = self::parseMarker($GLOBALS["c\x9D"][$parent], "\$GLOBALS['c\x9D']['{$parent}']=%marker%;");

            list($src, $marker, $a) = $src;

            if (false !== $a)
            {
                if (!$isTop)
                {
                    $i = (string) mt_rand(1, mt_getrandmax());
                    $GLOBALS["c\x9D"][$parent] = $src . '*' . $i;
                    $code .= substr($marker, 0, strrpos($marker, '*') + 1) . $i . "';";
                }

                $a = str_replace($marker, $code, $a);
                ($cache === $src && $current_pool) || self::write($a, $src);
            }
        }
        else $a = false;

        if ($cache)
        {
            if ($current_pool)
            {
                // Add an include directive of parent's code in the derivated class

                $code = '<?php ?'.'>';
                $a || $a = file_get_contents($cache);
                if ('<?php ' != substr($a, 0, 6)) $a = '<?php ?'.'>' . $a;
                $a = explode("\n", $a, 2);
                isset($a[1]) || $a[1] = '';

                $i = '/^' . preg_replace('/__[0-9]+$/', '', $lc_req) . '__[0-9]+$/i';

                foreach ($current_pool as $parent => $src)
                {
                    if ($req instanceof $parent && false === strpos($a[0], $src))
                    {
                        $code = substr($code, 0, -2)
                            . (preg_match($i, $parent) ? 'include' : 'include_once')
                            . " '{$src}';?".">";
                    }
                }

                if ('<?php ?'.'>' !== $code)
                {
                    $a = substr($code, 0, -2) . substr($a[0], 6) . $a[1];
                    self::write($a, $cache);
                }
            }

            $cache = substr($cache, strlen(PATCHWORK_PROJECT_PATH) + 7, -11);

            if ($amark)
            {
                // Marker substitution

                list($src, $marker, $a) = self::parseMarker($amark, "\$a\x9D=%marker%");

                if (false !== $a)
                {
                    if ($amark != $bmark)
                    {
                        $GLOBALS["a\x9D"] = $bmark;
                        $marker = "isset(\$c\x9D['{$lc_req}'])||{$marker}";
                        $code = ".class_{$cache}.zcache.php";
                        $code = addslashes(PATCHWORK_PROJECT_PATH . $code);
                        $ns = empty($ns) ? '' : '\\';
                        $code = "isset(\$c\x9D['{$lc_req}'])||{$ns}patchwork_include('{$code}')||1";
                    }
                    else
                    {
                        $marker = "\$e\x9D=\$b\x9D={$marker}";
                        $i = (string) mt_rand(1, mt_getrandmax());
                        $GLOBALS["a\x9D"] = $GLOBALS["b\x9D"] = $src . '*' . $i;
                        $i = substr($marker, 0, strrpos($marker, '*') + 1) . $i . "'";
                        $marker = "({$marker})&&\$d\x9D&&";
                        $code = $customSrc ? "'{$cache}'" : ($level + count($GLOBALS['patchwork_path']) - PATCHWORK_PATH_LEVEL);
                        $code = "\$c\x9D['{$lc_req}']={$code}";
                        $code = "({$i})&&\$d\x9D&&({$code})&&";
                    }

                    $a = str_replace($marker, $code, $a);
                    self::write($a, $src);
                }
            }
        }
    }

    protected static function parseMarker($marker, $template)
    {
        $a = strrpos($marker, '*');
        $src = substr($marker, 0, $a);
        $marker = substr($marker, $a);
        $marker = str_replace('%marker%', "__FILE__.'{$marker}'", $template);

        if (!file_exists($src) || !($a = file_get_contents($src)) || false === strpos($a, $marker)) $a = false;

        return array($src, $marker, $a);
    }

    protected static function write($data, $to)
    {
        $a = PATCHWORK_PROJECT_PATH . '.~' . uniqid(mt_rand(), true);
        if (false !== file_put_contents($a, $data))
        {
            function_exists('apc_delete_file')
                ? touch($a, filemtime($to)    )
                : touch($a, filemtime($to) + 1); // +1 to notify the change to opcode caches

            if ('\\' === DIRECTORY_SEPARATOR)
            {
                file_exists($to) && unlink($to);
                rename($a, $to) || unlink($a);
            }
            else rename($a, $to);

            function_exists('apc_delete_file') && apc_delete_file($to);
        }
    }
}
