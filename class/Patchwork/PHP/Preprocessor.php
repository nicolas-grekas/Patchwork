<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

class Patchwork_PHP_Preprocessor extends Patchwork_AbstractStreamProcessor
{
    static

    $namespaceRemoverCallback = 'Patchwork_PHP_Shim_Php530::add',
    $toStringCatcherCallback = 'Patchwork\ErrorHandler::handleToStringException',

    $constants = array(),
    $parsers = array(
        'Normalizer'         => true,
        'ShortOpenEcho'      => -50400, // Load this only before 5.4.0
        'BracketWatcher'     => true,
        'ShortArray'         => -50400,
        'BinaryNumber'       => -50400,
        'StringInfo'         => true,
        'Backport54Tokens'   => -50400,
        'Backport53Tokens'   => -50300,
        'NamespaceBracketer' => +50300, // Load this only for 5.3.0 and up
        'NamespaceInfo'      => true,
        'ScopeInfo'          => true,
        'ToStringCatcher'    => true,
        'DestructorCatcher'  => true,
        'ConstFuncDisabler'  => true,
        'ConstFuncResolver'  => true,
        'NamespaceResolver'  => -50300,
        'ConstantInliner'    => true,
        'ClassInfo'          => true,
        'NamespaceRemover'   => -50300,
        'InvokeShim'         => -50300,
        'ClosureShim'        => true,
        'ConstantExpression' => true,
        'FunctionShim'       => true,
        'StaticState'        => true,
    );

    protected static

    $filterClass = __CLASS__,
    $filterName;

    protected $cs;


    static function register()
    {
        if (isset(self::$filterName)) return stream_filter_register(self::$filterName, self::$filterClass);

        self::$filterName = 'patchwork.' . sprintf('%010d', mt_rand());
        foreach (self::$parsers as $k => $v)
        {
            is_bool($v) || $v = self::$parsers[$k] = 0 > $v ? PHP_VERSION_ID < -$v : PHP_VERSION_ID >= $v;
            $v && class_exists('Patchwork_PHP_Parser_' . $k);
        }

        return stream_filter_register(self::$filterName, self::$filterClass);
    }

    static function getPrefix()
    {
        return 'php://filter/read=' . self::$filterName . '/resource=';
    }

    function process($code)
    {
        foreach (self::$parsers as $c => $t)
        {
            if (!$t) continue;
            if (!$this->buildParser($parser, $c)) break;
        }

        if (isset($parser))
        {
            $code = $parser->parse($code);

            if (isset($this->cs))
            {
                $code = $this->cs->finalizeClosures($code);
                $this->cs = null;
            }

            foreach ($parser->getErrors() as $e)
                $this->handleError($e);
        }

        return $code;
    }

    protected function buildParser(&$parser, $class)
    {
        if (!class_exists($c = 'Patchwork_PHP_Parser_' . $class)) return false;

        switch ($class)
        {
        case 'Normalizer':     $parser = new $c; break;
        case 'Backport54Tokens':
        case 'ShortOpenEcho':
        case 'StaticState':
        case 'BinaryNumber':   $parser = new $c($parser); break;
        case 'ConstantInliner':          new $c($parser, $this->uri, self::$constants); break;
        case 'NamespaceRemover':         new $c($parser, self::$namespaceRemoverCallback); break;
        case 'ToStringCatcher':          new $c($parser, self::$toStringCatcherCallback); break;
        case 'ClosureShim':  $this->cs = new $c($parser); break;
        default:                         new $c($parser); break;
        }

        return true;
    }

    protected function handleError($e)
    {
        switch ($e['type'])
        {
        case 0: continue 2;
        case E_USER_NOTICE:
        case E_USER_WARNING:
        case E_USER_DEPRECATED: break;
        default:
        case E_ERROR: $e['type'] = E_USER_ERROR; break;
        case E_NOTICE: $e['type'] = E_USER_NOTICE; break;
        case E_WARNING: $e['type'] = E_USER_WARNING; break;
        case E_DEPRECATED: $e['type'] = E_USER_DEPRECATED; break;
        }

        user_error("{$e['message']} in {$this->uri} on line {$e['line']} as parsed by {$e['parser']}", $e['type']);
    }
}
