<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_Preprocessor
{
    static $constants = array('DEBUG');

    protected static

    $declaredClass = array('patchwork', 'patchwork_superloader', 'patchwork_shutdownhandler', 'patchwork_php_override_php530'),
    $recursivePool = array(),
    $parsers = array(
        'Normalizer'         => true,
        'ShortOpenEcho'      => -50400, // Load this only before 5.4.0
        'BracketWatcher'     => true,
        'ShortArray'         => -50400,
        'BinaryNumber'       => -50400,
        'ClassAutoname'      => true,
        'StringInfo'         => true,
        'Backport54Tokens'   => -50400,
        'Backport53Tokens'   => -50300,
        'NamespaceBracketer' => +50300, // Load this only for 5.3.0 and up
        'NamespaceInfo'      => true,
        'ScopeInfo'          => true,
        'DestructorCatcher'  => true,
        'ConstFuncDisabler'  => true,
        'ConstFuncResolver'  => true,
        'NamespaceResolver'  => -50300,
        'ConstantInliner'    => true,
        'ClassInfo'          => true,
        'NamespaceRemover'   => -50300,
        'ConstantExpression' => true,
        'SuperPositioner'    => true,
        'StaticInit'         => true,
        'Constructor4to5'    => true,
        'FunctionOverriding' => true,
        'Globalizer'         => true,
        'T'                  => true,
        'Marker'             => true,
        'StaticState'        => true,
    );


    static function __init()
    {
        foreach (get_declared_classes() as $v)
        {
            $v = strtolower($v);
            if (false !== strpos($v, 'patchwork')) continue;
            if ('p' === $v) break;
            self::$declaredClass[] = $v;
        }

        foreach (self::$parsers as $k => $v)
        {
            is_bool($v) || $v = self::$parsers[$k] = 0 > $v ? PHP_VERSION_ID < -$v : PHP_VERSION_ID >= $v;
            $v && class_exists('Patchwork_PHP_Parser_' . $k);
        }

        if (file_exists($v = PATCHWORK_PROJECT_PATH . ".patchwork.overrides.ser"))
        {
            $v = unserialize(file_get_contents($v));
            Patchwork_PHP_Parser_FunctionOverriding::loadOverrides($v[0]);
            Patchwork_PHP_Parser_ConstantInliner::loadConsts($v[1]);
        }
    }

    static function execute($source, $destination, $level, $class, $is_top, $lazy)
    {
        $source = patchwork_realpath($source);

        if (self::$recursivePool && $lazy)
        {
            $pool =& self::$recursivePool[count(self::$recursivePool)-1];
            $pool[$source] = array($destination, $level, $class, $is_top);
            return;
        }

        $pool = array($source => array($destination, $level, $class, $is_top));
        self::$recursivePool[] =& $pool;

        $tmpPool = array();

        while (list($source, list($destination, $level, $class, $is_top)) = each($pool))
        {
            $preproc = new self;

            $code = $preproc->preprocess($source, $level, $class, $is_top);

            $tmp = PATCHWORK_PROJECT_PATH . '.~' . uniqid(mt_rand(), true);
            if (false !== file_put_contents($tmp, $code)) $tmpPool[] = array($tmp, $destination);
        }

        while (list($tmp, $destination) = array_pop($tmpPool))
        {
/**/        if ('\\' === DIRECTORY_SEPARATOR)
                file_exists($destination) && @unlink($destination);

            rename($tmp, $destination) || unlink($tmp);
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
        $debug = defined('DEBUG') && DEBUG;

        foreach (self::$parsers as $c => $t)
        {
            if (!$t) continue;
            if (!class_exists($t = 'Patchwork_PHP_Parser_' . $c)) break;

            switch ($c)
            {
            case 'Normalizer':    $p = new $t; break;
            case 'Backport54Tokens':
            case 'ShortOpenEcho':
            case 'BinaryNumber':  $p = new $t($p); break;
            default:                   new $t($p); break;
            case 'StaticState':        if (0 <= $level) $p = new $t($p); break;
            case 'ClassAutoname':      if (0 <= $level && $class) new $t($p, $class); break;
            case 'ConstFuncDisabler':  if (0 <= $level) new $t($p); break;
            case 'Constructor4to5':    if (0 >  $level) new $t($p); break;
            case 'Globalizer':         if (0 <= $level) new $t($p, '$CONFIG'); break;
            case 'T':
            case 'Marker':             if (!$debug) new $t($p, self::$declaredClass); break;
            case 'ConstantInliner':    new $t($p, $source, self::$constants); break;
            case 'NamespaceRemover':   new $t($p, 'Patchwork_PHP_Override_Php530::add'); break;
            case 'SuperPositioner':    new $t($p, $level, $is_top ? $class : false); break;
            }
        }

        if (empty($p)) return file_get_contents($source);
        $t = $p->parse(file_get_contents($source));

        if ($c = $p->getErrors())
        {
            foreach ($c as $c)
            {
                switch ($c['type'])
                {
                case 0: continue 2;
                case E_USER_NOTICE;
                case E_USER_WARNING;
                case E_USER_DEPRECATED; break;
                default:
                case E_ERROR: $c['type'] = E_USER_ERROR; break;
                case E_NOTICE: $c['type'] = E_USER_NOTICE; break;
                case E_WARNING: $c['type'] = E_USER_WARNING; break;
                case E_DEPRECATED: $c['type'] = E_USER_DEPRECATED; break;
                }

                user_error("{$c['message']} in {$source}:{$c['line']} as parsed by {$c['parser']}", $c['type']);
            }
        }

        return $t;
    }
}
