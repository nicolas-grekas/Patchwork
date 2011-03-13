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


class patchwork_preprocessor
{
    static

    $scream = false,
    $constants = array(
        'DEBUG', 'IS_WINDOWS', 'PATCHWORK_ZCACHE',
        'PATCHWORK_PATH_LEVEL', 'PATCHWORK_PATH_OFFSET', 'PATCHWORK_PROJECT_PATH',
    );


    protected static

    $alias,
    $declaredClass = array('p', 'patchwork'),
    $recursivePool = array(),
    $parsers       = array(
        'normalizer'         => true,
        'backport53'         => 50300, // Load this only _before_ 5.3.0
        'classAutoname'      => true,
        'stringInfo'         => true,
        'namespaceInfo'      => true,
        'scoper'             => true,
        'constFuncDisabler'  => true,
        'constFuncResolver'  => true,
        'namespaceResolver'  => 50300,
        'constantInliner'    => true,
        'classInfo'          => true,
        'namespaceRemover'   => 50300,
        'constantExpression' => true,
        'superPositioner'    => true,
        'constructorStatic'  => true,
        'constructor4to5'    => true,
        'functionAliasing'   => true,
        'globalizer'         => true,
        'scream'             => true,
        'T'                  => true,
        'marker'             => true,
        'staticState'        => true,
    );


    static function __constructStatic()
    {
        self::$alias =& $GLOBALS['patchwork_preprocessor_alias'];
        null === self::$alias && self::$alias = unserialize(file_get_contents(PATCHWORK_PROJECT_PATH . ".patchwork.alias.ser"));

        self::$scream = (defined('DEBUG') && DEBUG)
            && !empty($GLOBALS['CONFIG']['debug.scream'])
                || (defined('DEBUG_SCREAM') && DEBUG_SCREAM);

        foreach (get_declared_classes() as $v)
        {
            $v = strtolower($v);
            if (false !== strpos($v, 'patchwork')) continue;
            if ('p' === $v) break;
            self::$declaredClass[] = $v;
        }

        foreach (self::$parsers as $k => $v)
        {
            $v > 1 && $v = self::$parsers[$k] = PHP_VERSION_ID < $v;
            $v && class_exists('patchwork_PHP_Parser_' . $k, true);
        }
    }

    static function execute($source, $destination, $level, $class, $is_top, $lazy)
    {
        $source = patchwork_realpath($source);

        if (self::$recursivePool && $lazy)
        {
            $pool =& self::$recursivePool[count(self::$recursivePool)-1];
            $pool[] = array($source, $destination, $level, $class, $is_top);
            return;
        }

        $pool = array(array($source, $destination, $level, $class, $is_top));
        self::$recursivePool[] =& $pool;

        while (list($source, $destination, $level, $class, $is_top) = array_shift($pool))
        {
            $preproc = new patchwork_preprocessor;

            $code = $preproc->preprocess($source, $level, $class, $is_top);

            $tmp = PATCHWORK_PROJECT_PATH . '.~' . uniqid(mt_rand(), true);
            if (false !== file_put_contents($tmp, $code))
            {
                if (win_hide_file($tmp))
                {
                    file_exists($destination) && @unlink($destination);
                    @rename($tmp, $destination) || unlink($tmp);
                }
                else rename($tmp, $destination);
            }
        }

        array_pop(self::$recursivePool);
    }

    static function isRunning()
    {
        return count(self::$recursivePool);
    }

    protected function __construct() {}

    protected function preprocess($source, $level, $class, $is_top)
    {
        foreach (self::$parsers as $c => $t)
        {
            if (!$t) continue;
            if (!class_exists($t = 'patchwork_PHP_Parser_' . $c, true)) break;

            switch ($c)
            {
            case 'normalizer':  $p = new $t; break;
            default:                 new $t($p); break;
            case 'backport53':
            case 'staticState':       if (0 <= $level) $p = new $t($p); break;
            case 'classAutoname':     if (0 <= $level && $class) new $t($p, $class); break;
            case 'scream':            if (self::$scream) new $t($p); break;
            case 'constFuncDisabler': if (0 <= $level)   new $t($p); break;
            case 'constructor4to5':   if (0 > $level)    new $t($p); break;
            case 'globalizer':        if (0 <= $level)   new $t($p, '$CONFIG'); break;
            case 'T':                 if (DEBUG)         new $t($p); break;
            case 'marker':            if (!DEBUG)        new $t($p, self::$declaredClass); break;
            case 'constantInliner':   new $t($p, $source, self::$constants); break;
            case 'namespaceRemover':  new $t($p, 'patchwork_PHP_class::add'); break;
            case 'superPositioner':   new $t($p, $level, $is_top ? $class : false); break;
            case 'functionAliasing':  new $t($p, self::$alias); break;
            }
        }

        if (empty($p)) return file_get_contents($source);
        $t = $p->parse(file_get_contents($source));

        if ($c = $p->getErrors())
        {
            if (class_exists('patchwork_error', true))
            {
                foreach ($c as $c)
                    patchwork_error::handle($c[3], $c[0], $source, $c[1]);
            }
            else
            {
                echo "Early preprocessor error in {$source}:\n";
                print_r($c);
                echo "\n";
            }
        }

        return $t;
    }
}
