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


class Patchwork_Preprocessor
{
    static

    $constants = array(
        'DEBUG',
        'PATCHWORK_ZCACHE',
        'PATCHWORK_PATH_LEVEL',
        'PATCHWORK_PROJECT_PATH',
        // TODO: replace the following ugly fixed list by generic const declarations parsing (not define(), whose value may be dynamic)
        'E_DEPRECATED','E_USER_DEPRECATED','PHP_VERSION_ID','PHP_MAJOR_VERSION','PHP_MINOR_VERSION','PHP_RELEASE_VERSION','PHP_EXTRA_VERSION','MB_OVERLOAD_MAIL','MB_OVERLOAD_STRING','MB_OVERLOAD_REGEX','MB_CASE_UPPER','MB_CASE_LOWER','MB_CASE_TITLE','ICONV_IMPL','ICONV_VERSION','ICONV_MIME_DECODE_STRICT','ICONV_MIME_DECODE_CONTINUE_ON_ERROR','GRAPHEME_EXTR_COUNT','GRAPHEME_EXTR_MAXBYTES','GRAPHEME_EXTR_MAXCHARS',
    );


    protected static

    $declaredClass = array('patchwork', 'patchwork_superloader', 'patchwork_shutdownhandler', 'patchwork_php_override_php530'),
    $recursivePool = array(),
    $parsers       = array(
        'Normalizer'         => true,
        'ShortOpenEcho'      => -50400, // Load this only before 5.4.0
        'BracketBalancer'    => true,
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
            $v && class_exists('Patchwork_PHP_Parser_' . $k, true);
        }

        $v = PATCHWORK_PROJECT_PATH . ".patchwork.overrides.ser";
        file_exists($v) && Patchwork_PHP_Parser_FunctionOverriding::loadOverrides(unserialize(file_get_contents($v)));
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
            $preproc = new self;

            $code = $preproc->preprocess($source, $level, $class, $is_top);

            $tmp = PATCHWORK_PROJECT_PATH . '.~' . uniqid(mt_rand(), true);
            if (false !== file_put_contents($tmp, $code))
            {
/**/            if ('\\' === DIRECTORY_SEPARATOR)
/**/            {
                    file_exists($destination) && @unlink($destination);
                    @rename($tmp, $destination) || unlink($tmp);
/**/            }
/**/            else
/**/            {
                    rename($tmp, $destination);
/**/            }
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
        $debug = defined('DEBUG') && DEBUG;

        foreach (self::$parsers as $c => $t)
        {
            if (!$t) continue;
            if (!class_exists($t = 'Patchwork_PHP_Parser_' . $c, true)) break;

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