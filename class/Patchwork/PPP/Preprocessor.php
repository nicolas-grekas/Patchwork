<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PPP;

use Patchwork\PHP\Parser;

class Preprocessor extends AbstractStreamProcessor
{
    protected

    $parserPrefix = 'Patchwork\PHP\Parser\\',
    $toStringCatcherCallback = 'Patchwork\PHP\ThrowingErrorHandler::handleToStringException',
    $compilerHaltOffset = 0,
    $constants = array(),
    $parsers = array(
        'PhpPreprocessor'    => true,
        'Normalizer'         => true,
        'ShortOpenEcho'      => -50400, // Load this only before 5.4.0
        'BracketWatcher'     => true,
        'ShortArray'         => -50400,
        'BinaryNumber'       => -50400,
        'StringInfo'         => true,
        'WorkaroundBug55156' => -50308,
        'Backport54Tokens'   => -50400,
        'NamespaceBracketer' => +50300, // Load this only for 5.3.0 and up
        'NamespaceInfo'      => true,
        'ScopeInfo'          => true,
        'ToStringCatcher'    => true,
        'DestructorCatcher'  => true,
        'ConstFuncDisabler'  => true,
        'ConstFuncResolver'  => true,
        'ConstantInliner'    => true,
        'ClassInfo'          => true,
        'ConstantExpression' => true,
        'FunctionShim'       => true,
        'StaticState'        => true,
    );

    protected static $code, $self;


    function __construct()
    {
        $this->loadClass('');
        $this->loadClass('HaltCompilerRemover');

        foreach ($this->parsers as $class => &$enabled)
            $enabled = $enabled
                && (0 > $enabled ? PHP_VERSION_ID < -$enabled : PHP_VERSION_ID >= $enabled)
                && $this->loadClass($class);
    }

    protected function loadClass($class)
    {
        $class = $class ? $this->parserPrefix . $class : substr($this->parserPrefix, 0, -1);
        if (class_exists($class, true)) return true;

        $dir = __DIR__ . '/../../' ;
        static::loadFile($dir . strtr($class, '\\', '/') . '.php');

        if (!class_exists($class, false)) return false;

        foreach ($class::$requiredClasses as $class)
            if (!class_exists($class, true))
                static::loadFile($dir . strtr($class, '\\', '/') . '.php');

        return true;
    }

    protected static function loadFile()
    {
        require func_get_arg(0);
    }


    function process($code)
    {
        self::$code = $code;
        self::$self = $this;
        return '<?php return eval(' . get_class($this) . '::selfProcess(__FILE__));';
    }

    static function selfProcess($uri)
    {
        $c = self::$code;
        $p = self::$self;
        $p->uri = $uri;
        self::$code = self::$self = null;
        return '?>' . $p->doProcess($c);
    }

    function doProcess($code)
    {
        $class = new Parser\HaltCompilerRemover;
        $code = $class->removeHaltCompiler($code, $this->compilerHaltOffset);

        foreach ($this->parsers as $class => $enabled)
            if ($enabled && !$this->buildParser($parser, $class))
                break;

        $this->compilerHaltOffset = 0;

        if (isset($parser))
        {
            $code = $parser->parse($code);

            foreach ($parser->getErrors() as $e)
                $this->handleError($e);
        }

        return $code;
    }

    protected function buildParser(&$parser, $class)
    {
        if (!class_exists($c = $this->parserPrefix . $class)) return false;

        switch ($class)
        {
        case 'Backport54Tokens':
        case 'ShortOpenEcho':
        case 'BinaryNumber':
        case 'StaticState':
        case 'Normalizer':  $parser = new $c($parser); break;
        case 'PhpPreprocessor':  $p = new $c($parser, $this->filterPrefix); break;
        case 'ConstantInliner':  $p = new $c($parser, $this->uri, $this->constants, $this->compilerHaltOffset); break;
        case 'ToStringCatcher':  $p = new $c($parser, $this->toStringCatcherCallback); break;
        default:                 $p = new $c($parser); break;
        }

        isset($parser) or $parser = $p;

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
